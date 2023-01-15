<?php

namespace App\Services\IconMaker\Icons;

use App\Services\IconMaker\IconMaker;

interface IconMakerContract
{
    public function handle(IconMaker $maker, \Closure $next);
}
