<?php

namespace App\Services\IconMaker\Icons;

use App\Services\IconMaker\IconMaker;
use App\Services\IconMaker\SubIconMaker;
use Intervention\Image\ImageManager;

abstract class BaseIconMaker
{
    protected IconMaker|SubIconMaker $maker;

    protected \Intervention\Image\Image $image;

    protected \LaravelZero\Framework\Commands\Command $command;

    public function handle(IconMaker|SubIconMaker $maker, \Closure $next)
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

    protected function loadSubIconImage($src): void
    {
        $this->image = (new ImageManager(['driver' => 'imagick']))->make("{$this->maker->subIconConfig['srcPath']}/{$src}");
    }

    protected function removePublicPath(string $iconPaths): string
    {
        return str($iconPaths)->after('public/')->prepend('/')->toString();
    }

    protected function isMakingSubIcons(): bool
    {
        return $this->maker instanceof SubIconMaker;
    }

    protected function getCurrentSubIconName(): bool
    {
        return $this->maker instanceof SubIconMaker;
    }
}
