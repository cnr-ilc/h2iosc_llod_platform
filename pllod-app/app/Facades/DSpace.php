<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DSpace extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dspace';
    }
}
