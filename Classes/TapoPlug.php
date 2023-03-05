<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

trait TapoPlug
{
    
    /**
     * @return bool|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isDevicePowerOn() : ?bool
    {
        if ($this->getDeviceType() != self::INTERNAL_DEVICE_TYPE_TAPOPLUG)
            return null;

        $deviceInfo = $this->getDeviceInfo();
        return (bool)$deviceInfo->device_on;
    }

    /**
     * @return bool|null state was set successfully, null if operation is not allowed on device
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function devicePowerOn() : ?bool
    {
        if ($this->getDeviceType() != self::INTERNAL_DEVICE_TYPE_TAPOPLUG)
            return null;

        return $this->setDevicePower(true);
    }

    /**
     * @return bool|null state was set successfully, null if operation is not allowed on device
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function devicePowerOff() : ?bool
    {
        if ($this->getDeviceType() != self::INTERNAL_DEVICE_TYPE_TAPOPLUG)
            return null;

        return $this->setDevicePower(false);
    }

    /**
     * @param bool|null $state
     * @return bool state was set successfully, null if operation is not allowed on device
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setDevicePower(bool $state) : ?bool
    {
        if ($this->getDeviceType() != self::INTERNAL_DEVICE_TYPE_TAPOPLUG)
            return null;

        $params = [
            'device_on' => $state
        ];
        $data = $this->sendCommand('set_device_info', $params);

        if ($data->error_code === 0) {
            $deviceInfo = $this->getDeviceInfo();
            $deviceInfo->device_on = $state;
            $this->setDeviceInfo($deviceInfo);
            return true;
        } else {
            return false;
        }
    }

}