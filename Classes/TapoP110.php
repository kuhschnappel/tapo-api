<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

class TapoP110 extends Tapo
{

    /**
     * @var Object $energyUsage
     */
    private $energyUsage = null;

    /**
     * get energy consumption for this and the last 2 month
     *
     * @return array
     */
    function getEnergyData() : array
    {
        $now = new \DateTime();
        $timeZone = new \DateTimeZone('UTC');
        $now->setTimezone($timeZone);
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
     * @return object
     */
    function getEnergyUsage() : object
    {
        if ($this->energyUsage == null) {
            $data = $this->sendCommand('get_energy_usage');
            $this->energyUsage = $data->result;
        }
        return $this->energyUsage;
    }

    /**
     * get current Energy Usage in Watt
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function getEnergyCurrentPower() : int
    {
        $energyUsage = $this->getEnergyUsage();
        return (int) round($energyUsage->current_power/1000);
    }

}