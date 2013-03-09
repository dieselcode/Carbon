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

namespace Carbon\Tools;


/**
 * Just a generic Data container.  Helps for passing things around
 * in an orderly manner.
 */

class DataContainer
{
    private $_data = array();

    public function __construct()
    {
    }

    public function __get($type)
    {
        return $this->getData($type);
    }

    public function __set($type, $data)
    {
        $this->setData($type, $data);
    }

    public function setData($type, $data)
    {
        $this->_data[$type] = $data;
    }

    public function getData($type = '')
    {
        if (!empty($type)) {
            if (array_key_exists($type, $this->_data)) {
                return $this->_data[$type];
            }
        } else {
            return (object)$this->_data;
        }

        return null;
    }

    public function getDecoded()
    {
        return $this->getData('decoded');
    }

    public function getRaw()
    {
        return $this->getData('raw');
    }

}

?>