<?php

namespace smart\exception;

class RouteException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Route Not Found');
    }
}
