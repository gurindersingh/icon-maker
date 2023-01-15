<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;

class Favicon extends BaseIconMaker implements IconMakerContract
{
    protected function process(): void
    {
        $this->command->task('Processing favicon', function () {

            $this->loadImage(
                explode('|', $this->maker->regularFiles['favicon'])[0]
            );

            $this->image->backup();

            $this->savePng();

            $this->saveIco();

            $this->image->destroy();

        }, 'Processing...');
    }

    private function savePng()
    {
        //$pngName = "favicon-{$this->maker->hash}.png";
        $pngName = "favicon.png";

        $pngName = "{$this->maker->destinationDir}/{$pngName}";

        $this->image->fit(32, 32)->save(Path::currentDirectory($pngName), 100);

        $this->maker->laravelConfig['favicon-png'] = $this->maker->iconPaths['favicon-png'] = $pngName = str($pngName)->after('public/')->toString();

        $this->maker->html[] = "<link rel=\"icon\" sizes=\"32x32\" href=\"{{ asset('{$pngName}') }}\" type=\"image/png\">";
    }

    protected function saveIco()
    {
        //$icoName = "favicon-{$this->maker->hash}.ico";
        $icoName = "favicon.ico";

        $icoName = "{$this->maker->destinationDir}/{$icoName}";

        $this->image->fit(32, 32)->encode('ico')->save(Path::currentDirectory($icoName), 100);

        $this->maker->laravelConfig['favicon-ico'] = $this->maker->iconPaths['favicon-ico'] = $icoName = str($icoName)->after('public/')->toString();

        $this->maker->html[] = "<link rel=\"shortcut icon\" href=\"{{ asset('{$icoName}') }}\" type=\"image/x-icon\">";
    }
}
