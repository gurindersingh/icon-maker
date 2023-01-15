<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\FetchIosDeviceScreenSizes;
use LaravelZero\Framework\Commands\Command;
use function Termwind\{render, terminal};

class Splash extends BaseIconMaker implements IconMakerContract
{

    protected function process(): void
    {
        if (!static::enabled($this->command)) return;

        $this->makeSplashScreens();

        //        $this->command->task('Process splash screens', function () {
        //
        //            $this->makeSplashScreens();
        //
        //        }, 'Processing...');
    }

    public static function enabled(Command $command): bool
    {
        return !!$command->option('splash');
    }

    protected function makeSplashScreens()
    {
        $this->loadImage(explode('|', $this->maker->regularFiles['splash'])[0]);

        $this->image->backup();

        $sizes = FetchIosDeviceScreenSizes::make()->getSizes();

        //$sizes = collect(FetchIosDeviceScreenSizes::make()->getSizes())->chunk(1)->first()->toArray();

        foreach ($sizes as $size) {
            $w = $size['width'] * $size['res'];

            $h = $size['height'] * $size['res'];

            $name = "splash-{$w}-{$h}-{$size['type']}.{$this->image->extension}";

            $this->command->task("Creating splash screen: {$name}", function () use ($size, $w, $h, $name) {

                $this->image->fit($w, $h)->save($path = "{$this->maker->destinationDir}/{$name}");

                $this->image->reset();

                $path = str($path)->after('public/')->toString();

                $this->maker->html[] = "<link rel=\"apple-touch-startup-image\" media=\"screen and (device-width: {$size['width']}) and (device-height: {$size['height']}) and (-webkit-device-pixel-ratio: {$size['res']}) and (orientation: {$size['type']})\" href=\"{{ asset('{$path}') }}\" />";

                $this->maker->iconsConfig["splash-{$w}-{$h}-{$size['type']}"] = $path;
            }, 'processing..');
        }

        $this->image->destroy();
    }
}
