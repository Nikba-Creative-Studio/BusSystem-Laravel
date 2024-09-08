<?php
namespace Nikba\BusSystem\Facades;

use Illuminate\Support\Facades\Facade;

class BusApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'busapi';
    }
}
