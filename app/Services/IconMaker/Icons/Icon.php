<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;

class Icon extends BaseIconMaker implements IconMakerContract
{
    protected array $iconDimensions = [512, 192];

    protected array $maskableIconDimensions = [1024, 512, 310, 270, 192, 144, 150, 70];

    protected function process(): void
    {
        $this->command->task('Processing icons', function () {

            $this->saveRegularIcons();

            $this->saveMaskableIcons();

        }, 'Processing...');
    }

    protected function saveRegularIcons()
    {
        $this->loadImage(
            explode('|', $this->maker->regularFiles['icon'])[0]
        );

        $this->image->backup();

        $this->saveForRequiredDimensions();

        $this->image->destroy();
    }

    protected function saveForRequiredDimensions()
    {
        foreach ($this->iconDimensions as $dim) {
            //$name = "icon-{$dim}-{$this->maker->hash}.png";
            $name = "icon-{$dim}.png";
            $this->image->fit($dim, $dim)->save($path = "{$this->maker->destinationDir}/{$name}", 100);
            $this->image->reset();
            $this->maker->iconsConfig["icon-{$dim}"] = str($path)->after('public/')->toString();
            $this->maker->manifestConfig['icons'][] = [
                "src"   => $src = str("{$this->maker->destinationDir}/{$name}")->after('public/')->toString(),
                "sizes" => "{$dim}x{$dim}",
                "type"  => "image/png"
            ];
            $this->maker->iconPaths['icon-' . $dim] = $src;
            $this->maker->laravelConfig['icon-' . $dim] = $src;
        }
    }

    protected function saveMaskableIcons()
    {
        $this->loadImage(
            explode('|', $this->maker->regularFiles['icon-maskable'])[0]
        );

        $this->image->backup();

        $this->saveForRequiredMaskableDimensions();

        $this->image->destroy();
    }

    protected function saveForRequiredMaskableDimensions()
    {
        foreach ($this->maskableIconDimensions as $dim) {

            $name = "maskable-icon-{$dim}.png";

            $this->image->fit($dim, $dim)->save($path = "{$this->maker->destinationDir}/{$name}", 100);

            $this->image->reset();

            $path = str($path)->after('public/')->toString();

            $this->maker->iconPaths['icon-' . $dim] = $path;

            if (in_array($dim, [512, 192])) {
                $this->maker->iconsConfig["icon-maskable-{$dim}"] = $path;
                $this->maker->laravelConfig['icon-maskable-' . $dim] = $path;
            }

            $this->maker->msapplication[] = "<meta name=\"msapplication-square{$dim}x{$dim}logo\" content=\"{{ asset('{$path}') }}\" />";

            if ($dim == 144) {
                $this->maker->msapplication[] = "<meta name=\"msapplication-TileImage\" content=\"{{ asset('{$path}') }}\" />";
            }

            if ($dim == 1024) {
                $this->maker->html[] = "<link rel=\"apple-touch-icon\" sizes=\"192x192\" href=\"{{ asset('{$path}') }}\" />";
            }

            if (in_array($dim, [192, 512])) {
                $this->maker->manifestConfig['icons'][] = [
                    "src"     => str("{$this->maker->destinationDir}/{$name}")->after('public/')->toString(),
                    "sizes"   => "{$dim}x{$dim}",
                    "type"    => "image/png",
                    "purpose" => "maskable",
                ];
            }
        }
    }

}
