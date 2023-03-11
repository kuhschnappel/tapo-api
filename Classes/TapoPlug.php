<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

trait TapoPlug
{

    /**
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setPowerOn() : self
    {
        return $this->setPower(true);
    }

    /**
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setPowerOff() : self
    {
        return $this->setPower(false);
    }

    /**
     * @return bool|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPower() : ?bool
    {
        if ($this->getType() != self::INTERNAL_TYPE_TAPOPLUG)
            return null;

        return (bool)$this->getInfo()->device_on;
    }

    /**
     * @param bool $state
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setPower(bool $state) : self
    {
        if ($this->getType() == self::INTERNAL_TYPE_TAPOPLUG)
            $this->addPendingChanges('device_on', $state);

        return $this;
    }

}