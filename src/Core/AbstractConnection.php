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

abstract class AbstractConnection
{

    public function getId()
    {
        return $this->id;
    }

    public function updateActivity()
    {
        $this->last_activity = time();
    }

    public function getActivity()
    {
        return $this->last_activity;
    }

    public function getConnectionTime()
    {
        return $this->connection_time;
    }

    public function setAlias($alias)
    {
        $this->log(sprintf('Renamed "%s" to "%s"', $this->id, $alias));
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

}

?>
