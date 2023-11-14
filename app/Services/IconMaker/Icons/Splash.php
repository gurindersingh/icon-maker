<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\FetchIosDeviceScreenSizes;
use Illuminate\Support\Arr;
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
        if ($this->isMakingSubIcons()) {
            $this->loadSubIconImage(explode('|', $this->maker->regularFiles['favicon'])[0]);
        } else {
            $this->loadImage(explode('|', $this->maker->regularFiles['splash'])[0]);
        }

        $this->image->backup();

        $sizes = FetchIosDeviceScreenSizes::make()->getSizes();

        //$sizes = collect(FetchIosDeviceScreenSizes::make()->getSizes())->chunk(1)->first()->toArray();

        $dest = $this->isMakingSubIcons() ? $this->maker->subIconConfig['destPath'] : $this->maker->destinationDir;
        $configPrefix = $this->isMakingSubIcons() ? 'subIcons.' . $this->maker->subIconConfig['iconName'] : null;

        foreach ($sizes as $size) {
            $w = $size['width'] * $size['res'];

            $h = $size['height'] * $size['res'];

            $name = "splash-{$w}-{$h}-{$size['type']}.{$this->image->extension}";

            $this->command->task("Creating splash screen: {$name}", function () use ($size, $w, $h, $name, $dest, $configPrefix) {

                $this->image->fit($w, $h)->save($path = "{$dest}/{$name}");

                $this->image->reset();

                $path = str($path)->after('public/')->toString();

                if ($configPrefix) {
                    $this->maker->html['subIcons'][$this->maker->subIconConfig['iconName']]["splash-{$w}-{$h}-{$size['type']}"] = $path;
                    Arr::set($this->maker->iconsConfig, $configPrefix . "splash-{$w}-{$h}-{$size['type']}", $path);
                } else {
                    $this->maker->html[] = "<link rel=\"apple-touch-startup-image\" media=\"screen and (device-width: {$size['width']}) and (device-height: {$size['height']}) and (-webkit-device-pixel-ratio: {$size['res']}) and (orientation: {$size['type']})\" href=\"{{ asset('{$path}') }}\" />";
                    $this->maker->iconsConfig["splash-{$w}-{$h}-{$size['type']}"] = $path;
                }
            }, 'processing..');
        }

        $this->image->destroy();
    }
}
