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

trait ServerDebugTrait
{

    /**
     * Enable\Disable debug mode
     *
     * @param bool $flag
     */
    public function setDebug($flag)
    {
        $this->debug = (bool)$flag;
    }

    /**
     * Get debug mode
     *
     * @return bool
     */
    public function getDebug()
    {
        return (bool)$this->debug;
    }

    /**
     * Console log
     *
     * @param string $message
     * @param string $type
     */
    public function log($message, $type = 'info')
    {
        echo date('Y-m-d H:i:s') . ' [' . ($type ? $type : 'error') . '] ' . $message . PHP_EOL;
    }

}

?>