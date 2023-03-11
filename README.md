# Kuhschnappel TapoApi for P100 & P110 Devices

This interface was developed primarily to pick up consumption data with PHP from the TP-Link TapoP110 sockets.

## Usage

### Communicatio with all Tapo Devices

#### create an new connection
``` 
$device = new Tapo('email', 'password', 'http://192.168.xxx.xxx');
$device = new Tapo('email', 'password', 'http://192.168.xxx.xxx:port');
$device = new Tapo('email', 'password', 'http://dyndns.tld:port');
```

#### Send multiple changes to device with one command
```
$device->sendChangedSettings();
```

#### device name
``` 
$device->getName();
$device->setName('Gerätename')
``` 


#### device location
``` 
$device->getLongitude()
$device->getLatitude()
$device->setLongitude(12.00000)
$device->setLatitude(50.000000)
``` 

#### get device informations
``` 
$device->getInfo();
``` 
=>
``` 
stdClass Object (
    [device_id] => 8022DFC785E6EC92987142C17FD2E06420996XXX
    [fw_ver] => 1.1.6 Build 221114 Rel.203339
    [hw_ver] => 1.0
    [type] => SMART.TAPOPLUG
    [model] => P110
    [mac] => AC-15-A2-E4-4B-13
    [hw_id] => 2FB30EF5BF920C44099401D396C6BXXX
    [fw_id] => 00000000000000000000000000000000
    [oem_id] => 18BDC6C734AF8407B3EF871EACFCECXXX
    [ip] => 192.168.5.103
    [time_diff] => 60
    [ssid] => XXX (base64 encoded string)
    [rssi] => -50
    [signal_level] => 2
    [latitude] => 0
    [longitude] => 0
    [lang] => de_DE
    [avatar] => plug
    [region] => Europe/Berlin
    [specs] => 
    [nickname] => XXX (base64 encoded string)
    [has_set_location_info] => 
    [device_on] => 1
    [on_time] => 3390611
    [default_states] => stdClass Object (
        [type] => last_states
        [state] => stdClass Object (
        )
    )
    [overheated] => 
    [power_protection_status] => normal
)
``` 


### TapoPlug Devices

#### Power On/Off
```
$device->getPower() => false / true;
$device->setPowerOn();
$device->setPowerOff();
$device->setPower(false / true);
```

### Change some settings & send it to your Tapo Device

```
$device->setAvatar(TAPO::AVATAR_PLUG)
       ->setLongitude(12.00000)
       ->setLatitude(12.00000)
       ->setPowerOn()
       ->sendChangedSettings();
```
or 
```
$device->setAvatar(TAPO::AVATAR_PLUG)
$device->setLongitude(12.00000);
$device->setLatitude(12.00000);
$device->setPowerOn();
$device->sendChangedSettings();
```

### TapoPlug P110 Devices

#### Energy usage
```
$device->getEnergyUsage();
```

=> 
``` Object (
    [today_runtime] => 1002
    [month_runtime] => 37002
    [today_energy] => 1224
    [month_energy] => 39851
    [local_time] => 2023-02-26 16:42:17
    [electricity_charge] => Array (
        [0] => 0
        [1] => 6422
        [2] => 11945
    )
    [current_power] => 118314 // in milliwat
)
```

 
#### Energy usage (get an array with timestamp and consumptions for last days)
```
$device->getEnergyData();
```
=> 
```
Array
(
    [1672527600] => 1233
    [1672614000] => 232
    [1672700400] => 0
    ...
    [1680040800] => 9999
    [1680127200] => 0
    [1680213600] => 0
)
```

## Support

If you would like to support the project, please consider buying me a coffee.

<a href="https://www.buymeacoffee.com/kuhschnappel" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>
