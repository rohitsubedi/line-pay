<?php

namespace Rohit\LinePay\Facades;

use Illuminate\Support\Facades\Facade;

class LinePay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'line-pay';
    }
}
