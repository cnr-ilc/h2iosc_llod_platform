<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SemanticTurkey extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'semanticTurkey';
    }
}
