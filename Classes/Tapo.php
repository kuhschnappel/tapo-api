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
    use TapoP110, TapoCommunication;

    const INTERNAL_DEVICE_TYPE_TAPO_P110 = 'SMART.TAPOPLUG.P110';

    /**
     * @var Object $deviceInfo
     */
    private $deviceInfo = null;

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
    public function getDeviceInfo() : object
    {
        if ($this->deviceInfo == null) {
            $data = $this->sendCommand('get_device_info');
            $this->deviceInfo = $data->result;
        }
        return $this->deviceInfo;
    }

    /**
     * @return string
     */
    public function getDeviceName() : string
    {
        $deviceInfo = $this->getDeviceInfo();
        return base64_decode($deviceInfo->nickname);
    }

    /**
     * @return string
     */
    public function getDeviceSsid() : string
    {
        $deviceInfo = $this->getDeviceInfo();
        return base64_decode($deviceInfo->ssid);
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDeviceType() : string
    {
        $deviceInfo = $this->getDeviceInfo();
        return $deviceInfo->type;
    }
    
    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDeviceModel() : string
    {
        $deviceInfo = $this->getDeviceInfo();
        return $deviceInfo->model;
    }


    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDeviceRegion() : string
    {
        $deviceInfo = $this->getDeviceInfo();
        return $deviceInfo->region;
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDeviceTypeModel() : string
    {
        return implode('.', [$this->getDeviceType(), $this->getDeviceModel()]);
    }

    /**
     * @return \DateTimeZone
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTimeZone() : \DateTimeZone
    {
        return new \DateTimeZone($this->getDeviceRegion());
    }
}