<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;
use Illuminate\Support\Arr;

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
        $imageLoadMethod = $this->isMakingSubIcons() ? 'loadSubIconImage' : 'loadImage';

        $this->{$imageLoadMethod}(explode('|', $this->maker->regularFiles['icon'])[0]);

        $this->image->backup();

        $destPath = $this->isMakingSubIcons() ? $this->maker->subIconConfig['destPath'] : $this->maker->destinationDir;

        $configPrefix = $this->isMakingSubIcons() ? 'subIcons.' . $this->maker->subIconConfig['iconName'] . '.' : '';

        $this->saveForRequiredDimensions($destPath, $configPrefix);

        $this->image->destroy();

    }

    protected function saveForRequiredDimensions(string $destDir, string $configPrefix = '')
    {
        foreach ($this->iconDimensions as $dim) {
            $name = "icon-{$dim}.png";
            $this->image->fit($dim, $dim)->save($path = "{$destDir}/{$name}", 100);
            $this->image->reset();
            // Arr::set($this->maker->iconsConfig, $configPrefix . "icon-{$dim}", str($path)->after('public/')->toString());
            // Arr::set($this->maker->manifestConfig, $configPrefix . "icon-{$dim}", str($path)->after('public/')->toString());

            $manifestConfig = Arr::get($this->maker->manifestConfig, $configPrefix . 'icons', []);
            $manifestConfig[] = [
                "src"   => $src = str("{$destDir}/{$name}")->after('public/')->toString(),
                "sizes" => "{$dim}x{$dim}",
                "type"  => "image/png"
            ];

            Arr::set($this->maker->manifestConfig, $configPrefix . 'icons', $manifestConfig);
            Arr::set($this->maker->iconPaths, $configPrefix . 'icon-' . $dim, $src);
            Arr::set($this->maker->laravelConfig, $configPrefix . 'icon-' . $dim, $src);
        }

        // dd($this->maker->manifestConfig);
    }

    protected function saveMaskableIcons()
    {
        $imageLoadMethod = $this->isMakingSubIcons() ? 'loadSubIconImage' : 'loadImage';

        $this->{$imageLoadMethod}(
            explode('|', $this->maker->regularFiles['icon-maskable'])[0]
        );

        $this->image->backup();

        $destPath = $this->isMakingSubIcons() ? $this->maker->subIconConfig['destPath'] : $this->maker->destinationDir;

        $configPrefix = $this->isMakingSubIcons() ? 'subIcons.' . $this->maker->subIconConfig['iconName'] . '.' : '';

        $this->saveForRequiredMaskableDimensions($destPath, $configPrefix);

        $this->image->destroy();
    }

    protected function saveForRequiredMaskableDimensions($destPath, $configPrefix = '')
    {
        $iconName = $this->maker->subIconConfig['iconName'] ?? null;

        foreach ($this->maskableIconDimensions as $dim) {

            $name = "maskable-icon-{$dim}.png";

            $this->image->fit($dim, $dim)->save($path = "{$destPath}/{$name}", 100);

            $this->image->reset();

            $path = str($path)->after('public/')->toString();

            $this->maker->iconPaths['icon-' . $dim] = $path;

            if (in_array($dim, [512, 192])) {
                Arr::set($this->maker->iconsConfig, $configPrefix . "icon-maskable-{$dim}", $path);
                Arr::set($this->maker->laravelConfig, $configPrefix . "icon-maskable-{$dim}", $path);
                // $this->maker->iconsConfig["icon-maskable-{$dim}"] = $path;
                // $this->maker->laravelConfig['icon-maskable-' . $dim] = $path;
            }

            if ($this->isMakingSubIcons()) {
                $this->maker->msapplication['subIcons'][$iconName][] = "<meta name=\"msapplication-square{$dim}x{$dim}logo\" content=\"{{ asset('{$path}') }}\" />";

                if ($dim == 144) {
                    $this->maker->msapplication['subIcons'][$iconName][] = "<meta name=\"msapplication-TileImage\" content=\"{{ asset('{$path}') }}\" />";
                }

                if ($dim == 1024) {
                    $this->maker->html['subIcons'][$iconName]['html'][] = "<link rel=\"apple-touch-icon\" sizes=\"192x192\" href=\"{{ asset('{$path}') }}\" />";
                }

                if (in_array($dim, [192, 512])) {
                    $this->maker->manifestConfig['subIcons'][$iconName]['icons'][] = [
                        "src"     => str("{$destPath}/{$name}")->after('public/')->toString(),
                        "sizes"   => "{$dim}x{$dim}",
                        "type"    => "image/png",
                        "purpose" => "maskable",
                    ];
                }
            } else {
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
}
