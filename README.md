# LarageoPlugin (Laravel 4 Package)

A Laravel package that uses [geoPlugin](http://www.geoplugin.com/webservices/json) web service to fetch information from an IP. It will store in cache the IP information and it will expire in 1 week.

----------------------


### Installation

Install this package through Composer. To your composer.json file, add:

```js
    "unnutz/larageo-plugin": "dev-master"
```

Next, run the Composer update comand

    $ composer update



Add the service provider to app/config/app.php, within the providers array.

```php
    'providers' => array(
        // ...
        'Fuhrmann\LarageoPlugin\ServiceProvider',
    ),
```

In the same file `config/app.php` add the alias:

```php
    'aliases' => array(
        //...
        'LarageoPlugin'   => 'Fuhrmann\LarageoPlugin\Facade',
    ),
```

### Usage

You can specify an IP:

```php
    $info = LarageoPlugin::getInfo('177.34.13.248'); // get info from a IP
    var_dump($info);
```

Or use it without any param:

```php
    $info = LarageoPlugin::getInfo(); // get info from the IP of the user acessing the page
    var_dump($info);
```

This is the output:

```php
    object(stdClass)[155]
      public 'geoplugin_request' => string '177.34.13.248' (length=13)
      public 'geoplugin_status' => int 200
      public 'geoplugin_credit' => string 'Some of the returned data includes GeoLite data created by MaxMind, available from <a href=\'http://www.maxmind.com\'>http://www.maxmind.com</a>.' (length=145)
      public 'geoplugin_city' => string 'Campo Grande' (length=12)
      public 'geoplugin_region' => string 'Mato Grosso do Sul' (length=18)
      public 'geoplugin_areaCode' => string '0' (length=1)
      public 'geoplugin_dmaCode' => string '0' (length=1)
      public 'geoplugin_countryCode' => string 'BR' (length=2)
      public 'geoplugin_countryName' => string 'Brazil' (length=6)
      public 'geoplugin_continentCode' => string 'SA' (length=2)
      public 'geoplugin_latitude' => string '-20.450001' (length=10)
      public 'geoplugin_longitude' => string '-54.616699' (length=10)
      public 'geoplugin_regionCode' => string '11' (length=2)
      public 'geoplugin_regionName' => string 'Mato Grosso do Sul' (length=18)
      public 'geoplugin_currencyCode' => string 'BRL' (length=3)
      public 'geoplugin_currencySymbol' => string '&#82;&#36;' (length=10)
      public 'geoplugin_currencySymbol_UTF8' => string 'R$' (length=2)
      public 'geoplugin_currencyConverter' => float 2.383
```

Another useful example: You can also just return one field, e.g. city from in one call:

```php
    $userCity = LarageoPlugin::getInfo()->geoplugin_city; // get the city from the user IP
    var_dump($userCity);
```

Output:

```php
    string 'Campo Grande' (length=12)
```

### More info

If you want more info about the geoPlugin web service, [click here](http://www.geoplugin.com/webservices).
