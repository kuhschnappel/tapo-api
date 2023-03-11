<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

trait TapoP110
{
    
    /**
     * @var Object $energyUsage
     */
    private $energyUsage = null;

    /**
     * get energy consumption for this and the last 2 month
     *
     * @return array|null
     */
    public function getEnergyData() : ?array
    {
        if ($this->getTypeModel() != self::INTERNAL_TYPE_TAPOPLUG_P110)
            return null;
        
        $now = new \DateTime();
        $now->setTimezone($this->getTimeZone());
        $now->setDate((int)$now->format('Y'),(int)($now->format('m')),1);
        $now->setTime(0,0,0);
        $now->modify('-2 month');

        $params = [
            'start_timestamp' => $now->getTimestamp(),
            'end_timestamp' => $now->getTimestamp(), // can be the same as the start date, but must be transferred
            'interval' => 1440 // 60 je stunde // 1440 je tag // in minuten
        ];

        $data = $this->sendCommand('get_energy_data', $params);
        
        $res = [];
        foreach($data->result->data as $key => $consumption) {
            $res[$now->getTimestamp()] = $consumption;
            $now->modify('1 day');
        }

        return $res;
    }

    /**
     * get energy usage stat object
     *
     * @return object|null
     */
    public function getEnergyUsage() : ?object
    {
        if ($this->getTypeModel() != self::INTERNAL_TYPE_TAPOPLUG_P110)
            return null;
        
        if ($this->energyUsage == null) {
            $data = $this->sendCommand('get_energy_usage');
            $this->energyUsage = $data->result;
        }
        return $this->energyUsage;
    }

    /**
     * get current Energy Usage in Watt
     *
     * @return float|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEnergyCurrentPower() : ?float
    {
        if ($this->getTypeModel() != self::INTERNAL_TYPE_TAPOPLUG_P110)
            return null;
        
        $energyUsage = $this->getEnergyUsage();
        return (float) $energyUsage->current_power/1000;
    }

    /**
     * get today Energy Usage in Watt Hours
     *
     * @return float|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEnergyToday() : ?float
    {
        if ($this->getTypeModel() != self::INTERNAL_TYPE_TAPOPLUG_P110)
            return null;

        $energyUsage = $this->getEnergyUsage();
        return (float) $energyUsage->today_energy;
    }

}