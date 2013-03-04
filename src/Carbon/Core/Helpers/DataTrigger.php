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

namespace Carbon\Core\Helpers;

use \Carbon\Exception\TriggerException;

class DataTrigger
{

    private $trigger        = null;


    public function __construct(\Closure $trigger_callback)
    {
        $this->trigger = $trigger_callback;
    }

    public function getCallback()
    {
        return $this->trigger;
    }

    public function call() // variable argument list
    {
        $return = call_user_func_array($this->trigger, func_get_args());
        if ($return === false) {
            throw new TriggerException('Trigger failed to execute; check supplied arguments');
        }

        return $return;
    }

}

?>
