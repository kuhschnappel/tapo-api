# Kuhschnappel TapoApi P110

This interface was developed primarily to pick up consumption data with PHP from the TP-Link TapoP110 sockets.


## Usage

``` 
// connect, handshake, login and get token to get data from device
$device = new Tapo('email', 'password', 'http://ip');
```

``` 
// device name
$device->getDeviceName();
``` 

``` 
// device infos
$device->getDeviceInfo();

=> stdClass Object (
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

``` 
// energy usage
$device->getEnergyUsage();

=> Object (
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

``` 
// energy usage
$device->getEnergyData();
=> get an date array with consumptions for last days
```


## Support

If you would like to support the project, please consider buying me a coffee.

<a href="https://www.buymeacoffee.com/kuhschnappel" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>
