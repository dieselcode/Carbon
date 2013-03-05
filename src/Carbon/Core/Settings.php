<?php

/**
 * Carbon WebSocket Server
 *
 * Copyright (c) 2013, Andrew Heebner, All rights reserved.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.
 */

namespace Carbon\Core;

class Settings
{

    public static $settings = array();
    public static $origSettings = array();

    private function __construct() {}

    public static function load($config_file)
    {
        if (file_exists($config_file)) {
            self::$settings = include $config_file;
            self::$origSettings = self::$settings;

            return true;
        }

        return false;
    }

    // we only need to capture the group here because we can dereference the array
    public static function get($group)
    {
        if (array_key_exists($group, self::$settings)) {
            return self::$settings[$group];
        }

        return null;
    }

    public static function set($group, $key, $val)
    {
        self::$settings[$group][$key] = $val;
    }

    public static function restore($group, $key)
    {
        self::$settings[$group][$key] = self::$origSettings[$group][$key];
    }

}

?>