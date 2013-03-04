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

namespace Carbon\Core\Traits;

use Carbon\Core\Helpers\DataTrigger;

trait ServerTriggerTrait
{

    public function addDataTrigger(DataTrigger $trigger_callback, $expected_key, $expected_value = null)
    {
        $this->current_trigger = $expected_key;
        $this->triggers[$this->current_route][$expected_key] = array(
            /*
             * add more options here for the triggers as we come across the need for them
             */
            'expected_value'        =>  $expected_value,
            'callback'              =>  $trigger_callback);
    }

    public function hasTriggers()
    {
        return (!empty($this->triggers)) ? true : false;
    }

    public function hasDataTrigger($key_name)
    {
        return !!array_key_exists($key_name, $this->triggers[$this->current_route]);
    }

    public function assertTriggerValue($key_name, $assert_value)
    {
        return (is_null($this->triggers[$this->current_route][$key_name]['expected_value'])) ?
            true :
            !!($this->triggers[$this->current_route][$key_name]['expected_value'] == $assert_value);
    }

    public function getDataTrigger($key_name)
    {
        if ($this->hasDataTrigger($key_name)) {
            return $this->triggers[$this->current_route][$key_name];
        }

        return false;
    }

}

?>