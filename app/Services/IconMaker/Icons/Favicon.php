<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;
use Illuminate\Support\Arr;

class Favicon extends BaseIconMaker implements IconMakerContract
{
    protected function process(): void
    {
        $this->command->task('Processing favicon', function () {

            if ($this->isMakingSubIcons()) {
                $this->loadSubIconImage(explode('|', $this->maker->regularFiles['favicon'])[0]);
                $this->image->backup();
                $this->savePng($this->maker->subIconConfig['destPath'], 'subIcons.' . $this->maker->subIconConfig['iconName'] . '.');
                $this->saveIco($this->maker->subIconConfig['destPath'], 'subIcons.' . $this->maker->subIconConfig['iconName'] . '.');
            } else {
                $this->loadImage(
                    explode('|', $this->maker->regularFiles['favicon'])[0]
                );
                $this->image->backup();
                $this->savePng($this->maker->destinationDir);
                $this->saveIco($this->maker->destinationDir);
            }
            $this->image->destroy();
        }, 'Processing...');
    }

    protected function savePng($destPath, $configPrefix = null)
    {
        $pngName = "favicon.png";

        $pngName = "{$destPath}/{$pngName}";

        $this->image->fit(32, 32)->save(Path::currentDirectory($pngName), 100);

        $pngName = str($pngName)->after('public/')->toString();

        Arr::set($this->maker->laravelConfig, $configPrefix . "favicon-png", $pngName);
        Arr::set($this->maker->iconPaths, $configPrefix . "favicon-png", '/' . $pngName);
        Arr::set($this->maker->iconsConfig, $configPrefix . "favicon-png", '/' . $pngName);

        if(empty($configPrefix)) {
            $this->maker->html[] = "<link rel=\"icon\" sizes=\"32x32\" href=\"{{ asset('{$pngName}') }}\" type=\"image/png\">";
        } else {
            $html = Arr::get($this->maker->html, $configPrefix . 'html', []);
            $html[] = "<link rel=\"icon\" sizes=\"32x32\" href=\"{{ asset('{$pngName}') }}\" type=\"image/png\">";
            Arr::set($this->maker->html, $configPrefix . 'html', $html);
        }
    }

    protected function saveIco($dest, $configPrefix = '')
    {
        $icoName = "favicon.ico";

        $icoName = "{$dest}/{$icoName}";

        $this->image->fit(32, 32)->encode('ico')->save(Path::currentDirectory($icoName), 100);

        $icoName = str($icoName)->after('public/')->toString();
        Arr::set($this->maker->laravelConfig, $configPrefix . "favicon-ico", $icoName);
        Arr::set($this->maker->iconPaths, $configPrefix . "favicon-ico", $icoName);
        Arr::set($this->maker->iconsConfig, $configPrefix . "favicon-ico", '/' . $icoName);

        if(empty($configPrefix)) {
            $this->maker->html[] = "<link rel=\"shortcut icon\" href=\"{{ asset('{$icoName}') }}\" type=\"image/x-icon\">";
        } else {
            $html = Arr::get($this->maker->html, $configPrefix . 'html', []);
            $html[] = "<link rel=\"shortcut icon\" href=\"{{ asset('{$icoName}') }}\" type=\"image/x-icon\">";
            Arr::set($this->maker->html, $configPrefix . 'html', $html);
        }
    }

    protected function saveSubIconIco()
    {
        $icoName = "favicon.ico";
        $icoName = "{$this->maker->subIconConfig['destPath']}/{$icoName}";
        $this->image->fit(32, 32)->encode('ico')->save(Path::currentDirectory($icoName), 100);
        $iconName = $this->maker->subIconConfig['iconName'];
        $icoName = str($icoName)->after('public/')->toString();
        Arr::set($this->maker->laravelConfig, "{$iconName}.favicon-ico", $icoName);
        Arr::set($this->maker->iconPaths, "{$iconName}.favicon-ico", $icoName);
        Arr::set($this->maker->iconsConfig, "{$iconName}.favicon-ico", '/' . $icoName);
        $this->maker->html[] = "<link rel=\"shortcut icon\" href=\"{{ asset('{$icoName}') }}\" type=\"image/x-icon\">";
    }
}
