<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ExportSubIconsArtifacts extends BaseIconMaker implements IconMakerContract
{

    protected string $manifestIcons = "";

    protected string $iconName;

    protected function process(): void
    {
        $this->command->task('Exporting sub icons artifacts', function () {
            $this->createManifest();
        });
    }

    protected function createManifest()
    {
        $this->iconName = $this->maker->subIconConfig['iconName'];

        $this->manifestIcons = "<?php \n\nreturn [\n";
        $this->manifestIcons .= "'theme-color' => '{$this->maker->manifestConfig['subIcons'][$this->iconName]['theme_color']}',\n";

        $this->copyImageFiles();

        $this->addToHtml();

        $this->writeHeadHtml();

        // dd(
        //     $this->manifestIcons,
        //     $this->maker->subIconConfig,
        //     $this->maker->manifestConfig,
        // );
    }

    protected function copyImageFiles()
    {
        $allFiles = [
            'svg' => ['logo-vector', 'logo-sm-vector', 'apple-tab-icon'],
            'png' => ['logo', 'logo-sm'],
        ];

        $srcDir = $this->maker->subIconConfig['relativeSrcPath'];
        $destPath = $this->maker->subIconConfig['destPath'];

        foreach ($allFiles as $type => $files) {
            foreach ($files as $name) {
                $logoName = "{$name}.{$type}";

                copy(
                    Path::currentDirectory($srcDir . '/' . explode('|', $this->maker->regularFiles[$name])[0]),
                    $path = $destPath . '/' . $logoName
                );
                $this->maker->laravelConfig['subIcons'][$this->iconName]["{$name}-{$type}"] = $this->removePublicPath($path);

                $this->maker->iconPaths['subIcons'][$this->iconName][$name] = $this->removePublicPath($path);
                $this->manifestIcons .= "'{$name}' => '{$this->maker->iconPaths['subIcons'][$this->iconName][$name]}',\n";
            }
        }
    }

    protected function addToHtml()
    {
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<link rel=\"mask-icon\" sizes=\"any\" href=\"{{ asset('{$this->maker->iconPaths['subIcons'][$this->iconName]['apple-tab-icon']}') }}\" color=\"{{ subicon_config('" . $this->iconName . ".theme-color') }}\">";
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<meta name=\"mobile-web-app-capable\" content=\"yes\" />";
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<meta name=\"apple-mobile-web-app-title\" content=\"{{ config('app.name') }}\" />";
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"white\" />";
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<meta name=\"theme-color\" content=\"{{ subicon_config('" . $this->iconName . ".theme-color') }}\" />";
        $this->maker->html['subIcons'][$this->iconName]['html'][] = "<link rel=\"manifest\" href=\"/manifest.json\" crossOrigin=\"use-credentials\" />";
        $this->maker->msapplication['subIcons'][$this->iconName][] = "<meta name=\"msapplication-TileColor\" content=\"white\">";

        foreach ($this->maker->iconsConfig['subIcons'][$this->iconName] as $k => $v) {
            $this->manifestIcons .= "'{$k}' => '{$v}',\n";
        }
        $this->manifestIcons .= "];\n";
    }

    protected function writeHeadHtml()
    {
        if (Arr::get($this->maker->config, 'headHtmlLocation') !== false) {

            $headHtmlLocationPath = $this->maker->subIconConfig['destPath'] . '/head.html';

            File::ensureDirectoryExists(pathinfo($headHtmlLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($headHtmlLocationPath),
                implode("\n", array_merge($this->maker->html['subIcons'][$this->iconName]['html'], $this->maker->msapplication['subIcons'][$this->iconName]))
            );
        }

        if (Arr::get($this->maker->config, 'headHtmlBladeLocation') !== false) {

            $headHtmlBladeLocationPath = 'resources/views/components/sub-icons/' . $this->iconName . '/head-icons.blade.php';

            File::ensureDirectoryExists(pathinfo($headHtmlBladeLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($headHtmlBladeLocationPath),
                implode("\n", array_merge($this->maker->html['subIcons'][$this->iconName]['html'], $this->maker->msapplication['subIcons'][$this->iconName]))
            );
        }

        if (Arr::get($this->maker->config, 'configThemeLocation') !== false) {

            // $configThemeLocationPath = Arr::get($this->maker->config, 'configThemeLocation', 'config/theme.php');
            $configThemeLocationPath = 'resources/views/sub-icons/themes/' . $this->iconName . '/theme.php';

            File::ensureDirectoryExists(pathinfo($configThemeLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($configThemeLocationPath),
                $this->manifestIcons
            );
        }

        if (Arr::get($this->maker->config, 'webManifestLocation') !== false) {
            $this->writeManifest('webManifestLocation', 'resources/views/sub-icons/themes/' . $this->iconName .'/manifest.webmanifest');
        }

        if (Arr::get($this->maker->config, 'manifestJsonLocation') !== false) {
            $this->writeManifest('manifestJsonLocation', 'resources/views/sub-icons/themes/' . $this->iconName .'/manifest.json');
        }
    }

    protected function writeManifest(string $configValue, string $defaultPath)
    {
        $path = Arr::get($this->maker->config, $configValue, $defaultPath);

        File::ensureDirectoryExists(pathinfo($path)['dirname']);

        // dd($this->maker->manifestConfig);

        File::put(
            Path::currentDirectory($path),
            json_encode($this->maker->manifestConfig['subIcons'][$this->iconName], JSON_UNESCAPED_SLASHES)
        );
    }
}
