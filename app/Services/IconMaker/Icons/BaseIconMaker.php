<?php

namespace App\Services\IconMaker\Icons;

use App\Services\IconMaker\IconMaker;
use Intervention\Image\ImageManager;

abstract class BaseIconMaker
{
    protected IconMaker $maker;

    protected \Intervention\Image\Image $image;

    protected \LaravelZero\Framework\Commands\Command $command;

    public function handle(IconMaker $maker, \Closure $next)
    {
        $this->maker = $maker;

        $this->command = $this->maker->command;

        $this->process();

        return $next($this->maker);
    }

    abstract protected function process(): void;

    protected function loadImage($src): void
    {
        $this->image = (new ImageManager(['driver' => 'imagick']))->make("{$this->maker->sourceDir}/{$src}");
    }

    protected function removePublicPath(string $iconPaths): string
    {
        return str($iconPaths)->after('public/')->prepend('/')->toString();
    }
}
