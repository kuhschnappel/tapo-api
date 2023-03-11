<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Response;
use phpseclib3\Crypt\AES;

class Tapo
{
    use TapoP110, TapoPlug, TapoCommunication;

    const INTERNAL_TYPE_TAPOPLUG_P110 = 'SMART.TAPOPLUG.P110';
    const INTERNAL_TYPE_TAPOPLUG = 'SMART.TAPOPLUG';

    const AVATAR_PLUG = 'plug';
    const AVATAR_FAN = 'fan';
    const AVATAR_TABLE_LAMP = 'table_lamp';
    const AVATAR_CEILING_LAMP = 'ceiling_lamp';
    const AVATAR_TAPE_LIGHTS = 'tape_lights';
    const AVATAR_WALL_LAMP = 'wall_lamp';
    const AVATAR_SOUND = 'sound';
    const AVATAR_RADIO = 'radio';
    const AVATAR_HUMIDIFIER = 'humidifier';
    const AVATAR_KETTLE = 'kettle';
    const AVATAR_COFFEE_MAKER = 'coffee_maker';
    const AVATAR_JUICER = 'juicer';
    const AVATAR_EGG_BOILER = 'egg_boiler';
    const AVATAR_BREAD_MAKER = 'bread_maker';
    const AVATAR_HOUSE = 'house';

    /**
     * @var Array $pendingChanges
     */
    private $pendingChanges = null;

    /**
     * @var Object $info
     */
    private $info = null;

    /**
     * @param string $user
     * @param string $password
     * @param string $host
     * @return void
     */
    public function __construct(string $user, string $password, string $host)
    {
        $this->initCommunication($user, $password, $host);
    }

    /**
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInfo() : object
    {
        if ($this->info == null) {
            $this->loadInfo();
        }
        return $this->info;
    }

    /**
     * @param object $info
     * @return void
     */
    private function setInfo(object $info)
    {
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return base64_decode($this->getInfo()->nickname);
    }

    /**
     * @param string $name
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setName(string $name) : self
    {
        $this->addPendingChanges('nickname', base64_encode($name));

        return $this;
    }

    /**
     * @return string
     */
    public function getFirmwareVersion() : string
    {
        return $this->getInfo()->fw_ver;
    }
    
    /**
     * @return string
     */
    public function getDeviceId() : string
    {
        return $this->getInfo()->device_id;
    }

    /**
     * @return string
     */
    public function getSsid() : string
    {
        return base64_decode($this->getInfo()->ssid);
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getType() : string
    {
        return $this->getInfo()->type;
    }
    
    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getModel() : string
    {
        return $this->getInfo()->model;
    }


    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRegion() : string
    {
        return $this->getInfo()->region;
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTypeModel() : string
    {
        return implode('.', [$this->getType(), $this->getModel()]);
    }

    /**
     * @return \DateTimeZone
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTimeZone() : \DateTimeZone
    {
        return new \DateTimeZone($this->getRegion());
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function loadInfo()
    {
        $data = $this->sendCommand('get_device_info');
        $this->setInfo($data->result);
    }

    /**
     * @return bool
     */
    public function sendChangedSettings() : bool
    {
        if (!$this->hasPendingChanges()) {
            return true;
        }

        $data = $this->sendCommand('set_device_info', $this->pendingChanges);
        if ($data->error_code === 0) {
            $this->pendingChanges = null;
            $this->loadInfo();
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasPendingChanges() : bool
    {
        if ($this->pendingChanges === null) {
            return false;
        }
        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addPendingChanges( string $key, mixed $value) : void
    {
        if ($this->pendingChanges === null) {
            $this->pendingChanges = [];
        }
        $this->pendingChanges[$key] = $value;
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAvatar() : string
    {
        return $this->getInfo()->avatar;
    }

    /**
     * @param string $avatar
     * @return $this
     */
    public function setAvatar( string $avatar) : self
    {
        $this->addPendingChanges('avatar', $avatar);

        return $this;
    }



    /**
     * @return float
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLongitude() : float
    {
        return $this->getInfo()->latitude / 10000;
    }

    /**
     * @param float $longitude
     * @return $this
     */
    public function setLongitude( float $longitude) : self
    {
        $this->addPendingChanges('longitude', round($longitude * 10000,0));

        return $this;
    }

    /**
     * @return float
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLatitude() : float
    {
        return $this->getInfo()->longitude / 10000;
    }

    /**
     * @param float $latitude
     * @return $this
     */
    public function setLatitude( float $latitude) : self
    {
        $this->addPendingChanges('latitude', round($latitude * 10000,0));

        return $this;
    }

}