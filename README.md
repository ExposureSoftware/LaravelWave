# LaravelWave [![Build Status](https://travis-ci.com/ExposureSoftware/LaravelWave.svg?branch=master)](https://travis-ci.com/ExposureSoftware/LaravelWave) 
Provides a Laravel package to integrate with an existing [Z-Way](https://z-wave.me/z-way/) server.

## Installation
Installation is performed via [Composer](https://getcomposer.org).

#### Install Composer
See the Composer [Getting Started](https://getcomposer.org/doc/00-intro.md) documentation for
how to install Composer on your platform.

#### Require LaravelWave in your project
The simplest way to do this is by running this command:

    composer require exposuresoftware/laravel-zway
    
Alternatively, you may add the requirement directly by adding 
`"exposuresoftware/laravel-zway": "^0.0.1"`
to your `require` section of `composer.json`.

#### Publish the package to your project
Run `php artisan vendor:publish`.

This will add `laravelwave.php` to your config directory.

#### Configure the package
The package is configurable via environment variables. To configure in this way add the following
lines to your `.env`, changing the values to match your configuration:

```dotenv
# The host URI for your Z-Way server.
# Defaults to http://localhost
# Include the schema and do not provide a trailing slash
ZWAY_HOST=http://localhost
# The port on which your Z-Way server is listening.
# Defaults to Z-Way default port 8083.
ZWAY_PORT=8083
# API User
# Defaults to the Z-Way default of admin
ZWAY_USER=admin
# API User's password
ZWAY_PASSWORD=
```

You may also edit the `laravelwave.php` file in your config directory.

***Note:** It is **not** recommended to store your credentials in the configuration file if 
committed to a publicly accessible repository (like Github).* 

## Usage
There are a number of methods of employing the SDK in your project. Each provides access
to all the methods made accessible, a full list of which is available in this document.

All communication with the Z-Way server, except logging in, require authentication when 
accessed via this SDK at this time. Once you have successfully logged into the server once
a token will be optionally stored for you. If you choose not to store the token it will
only be available for the life of the current SDK instance.  

#### Via provided Facade
```php
<?php

use ExposureSoftware\LaravelWave\Facades\ZwaveFacade as Zwave;

// ...

Zwave::login();
```

#### Via Laravel container
```php
<?php

use ExposureSoftware\LaravelWave\Zwave\Zwave;

// ...

/** @var Zwave $zwave */
$zwave = App::make(Zwave::class);
$zwave->login();
```

Note that the SDK can be injected as a dependcy.

```php
<?php

use ExposureSoftware\LaravelWave\Zwave\Zwave;

class Foo
{
    /** @var Zwave */
    private $zwave;
    
    public function __construct(Zwave $zwave) {
        $this->zwave = $zwave;
    }
    
    public function myMethod()
    {
        $this->zwave->login();
        // ...
    }
}
```

#### Via instantiation directly
```php
<?php

use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Client;

// ...

$zwave = new Zwave(new Client());
```

## Commands
Commands are provided to interact with the Z-Way API. These can also be scheduled via the
Laravel [task scheduling](https://laravel.com/docs/5.8/scheduling).

For full details of each command please see the `help` command of Artisan.

```
php artisan help zwave:fetch-devices
```

Signature | Description
----|----
zway:fetch-devices | Retrieve devices via the API.

## Available Methods
Parameters listed are examples and those in _italics_ are optional.

Method | Returns | Description
-------------------------------------------|---------------------------------|----
hasToken() | `bool` | Returns `true` or `false` depending on if the current instance has a token.
login(_'admin'_, _'secret'_, _true_) | `bool` | Logs in with the given credentials. If none are provided the credentials from package configuration are used. The last parameter represents whether or not to store the token.
listDevices(_true_) | `Illuminate\Support\Collection` | Returns a collection of all the devices known to the server. If passed `false` these will not be stored in the database.
update(_device_) | `ExposureSoftware\LaravelWave\Device` | Returns the `Device` with updated attributes to reflect current state.
command(_device_, _command_, _parameters_) | `bool` | Runs a command on the given device with the provided parameters. See the "Virtual Device Types" section of the [documentation](https://zwayhomeautomation.docs.apiary.io/#reference/devices/virtual-device) for commands supported per device*.
\* Only `switchBinary` is supported in this version. 

## Events
Events are dispatched during different operations so that actions may be taken in response.

All events are in the `ExposureSoftware\LaravelWave\Events` namespace.

Event | Cause | Available Properties
----------|----------|----------
`CommandSent` | Set after a command has been sent to the Z-Way Server. | `string` _command_,<br>`ExposureSoftware\LaravelWave\Models\Device` _device_,<br>`bool` _successful_,<br>`array` _parameters_  


# Sponsorship
If you would like to provide funding for this project please use any of the methods listed below.

## Tidelift
[Tidelift](https://tidelift.com/subscription/pkg/packagist-exposuresoftware-laravel-zway?utm_source=packagist-exposuresoftware-laravel-zway&utm_medium=referral&utm_campaign=readme) gives software development teams a single source for purchasing and maintaining their software, with professional grade assurances from the experts who know it best, while seamlessly integrating with existing tools.

# Security Vulnerabilities

 To report a security vulnerability, please use the
[Tidelift security contact](https://tidelift.com/security).
Tidelift will coordinate the fix and disclosure.
