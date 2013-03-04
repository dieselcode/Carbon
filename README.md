![Carbon](http://php-oop.net/~andrew/carbon-head.png)
======

[![Build Status](https://drone.io/github.com/dieselcode/Carbon/status.png)](https://drone.io/github.com/dieselcode/Carbon/latest)

#### A WebSocket Server for PHP 5.4+ ####

Carbon was created out of necessity for a simple, yet powerful WebSocket server.

Prerequisites
-----
 - PHP 5.4+
 - Working knowledge of closures (anonymous functions)
 - Socket extension
 - OpenSSL support (optional) (currently not working due to PHP bug)
 - Composer

Installation
-----
Until a stable release becomes available, please download a package through Github, and run the following command via composer in the main Carbon directory:
```console
php composer.phar dumpautoload
```

Your autoloader will now reside at `<carbon>/vendor/autoload.php`.

When the Carbon package becomes stable, you'll be able to install by adding the following lines to your `composer.json` file:
```json
{
    "require": {
        "dieselcode/Carbon": "~1.0"
    }
}
```


Usage
-----
Please see the example servers in the `examples` directory.  Take note of the apprpriate *.ini files for each example.
 - `test_generic.php` shows most basic features of what Carbon is capable of
 - `test_upload.php` is an example of a file upload system.  Use `test_upload.html` as a client example.

A more complete and inclusive source of documentation will become available as the library becomes more stable and features are set in stone.
 
Changelog
-----
###0.1###
 - Still in production on `master`
 
Roadmap
-----
All releases will be branched from `master` into their appropriate version numbers upon stable release.  The main `master` branch is to be considered *unstable* and development quality code at best.  

Todo
-----
 - Implement connection and message throttling
 - Get OpenSSL support functioning properly
 - Create an error and logging container
 - Write more unit tests

Credits
------
Many sources we used in patching this project together, including Wrench, phpws, PHP-WebSocket, and php-websocket-server.  Sources will be cited in code as soon as the library becomes stable and documentation is added.  Thanks to all those who unknowingly helped contribute.

All other code is &copy; 2013 Andrew Heebner unless otherwise noted.  Please see `docs/LICENSE` for license details.
