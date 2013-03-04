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

trait ServerRoutingTrait
{

    public function route($route, \Closure $callback)
    {
        $this->current_route = ltrim($route, '/');
        // activate the callback and execute internal callbacks
        $callback($this);

        return $this; // allow chaining
    }

    public function on($action, \Closure $callback)
    {
        if (!is_null($this->current_route)) {
            $this->current_action                        = $action;
            $this->routes[$this->current_route][$action] = $callback;
        }

        return $this; // allow chaining
    }

    public function addRoutingGroup($route) {
        if (!array_key_exists($route, $this->groups)) {
            $this->groups[$route] = array();
        }
    }

    public function addRouteConnection($route, $socket, $connection) {
        @$this->groups[$route]['connections'][$socket] = $connection;
    }

    public function getRouteConnections($route) {
        return $this->groups[$route]['connections'];
    }

    public function hasRoute($route)
    {
        return !!array_key_exists($route, $this->routes);
    }

    public function getRoute($route)
    {
        return ($this->hasRoute($route)) ? $this->routes[$route] : null;
    }

    public function hasCallback($route, $callback) {
        if ($this->hasRoute($route)) {
            $route = $this->getRoute($route);
            if (array_key_exists($callback, $route)) {
                return true;
            }
        }

        return false;
    }

}

?>