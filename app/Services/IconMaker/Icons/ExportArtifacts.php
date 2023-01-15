<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ExportArtifacts extends BaseIconMaker implements IconMakerContract
{

    protected string $manifestIcons = "";

    protected function process(): void
    {
        $this->command->task('Exporting artifacts', function () {
            $this->createManifest();
        });
    }

    protected function createManifest()
    {
        $this->manifestIcons = "<?php \n\nreturn [\n";
        $this->manifestIcons .= "'theme-color' => '{$this->maker->manifestConfig['theme_color']}',\n";

        $this->copyImageFiles();

        $this->addToHtml();

        $this->writeHeadHtml();
    }

    protected function copyImageFiles()
    {
        $allFiles = [
            'svg' => ['logo-vector', 'logo-sm-vector', 'apple-tab-icon'],
            'png' => ['logo', 'logo-sm'],
        ];

        foreach ($allFiles as $type => $files) {
            foreach ($files as $name) {
                $logoName = "{$name}.{$type}";

                copy(
                    Path::currentDirectory($this->maker->sourceDir . '/' . explode('|', $this->maker->regularFiles[$name])[0]),
                    $path = $this->maker->destinationDir . '/' . $logoName
                );

                $this->maker->laravelConfig["{$name}-{$type}"] = $this->removePublicPath($path);

                $this->maker->iconPaths[$name] = $this->removePublicPath($path);
                $this->manifestIcons .= "'{$name}' => '{$this->maker->iconPaths[$name]}',\n";
            }
        }
    }

    protected function addToHtml()
    {
        $this->maker->html[] = "<link rel=\"mask-icon\" sizes=\"any\" href=\"{{ asset('{$this->maker->iconPaths['apple-tab-icon']}') }}\" color=\"{{ config('app.theme.theme-color') }}\">";
        $this->maker->html[] = "<meta name=\"mobile-web-app-capable\" content=\"yes\" />";
        $this->maker->html[] = "<meta name=\"apple-mobile-web-app-title\" content=\"{{ config('app.name') }}\" />";
        $this->maker->html[] = "<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"white\" />";
        $this->maker->html[] = "<meta name=\"theme-color\" content=\"{{ config('app.theme.theme-color') }}\" />";
        $this->maker->html[] = "<link rel=\"manifest\" href=\"/manifest.json\" crossOrigin=\"use-credentials\" />";
        $this->maker->msapplication[] = "<meta name=\"msapplication-TileColor\" content=\"white\">";

        foreach ($this->maker->iconsConfig as $k => $v) {
            $this->manifestIcons .= "'{$k}' => '{$v}',\n";
        }
        $this->manifestIcons .= "];\n";
    }

    protected function writeHeadHtml()
    {
        if (Arr::get($this->maker->config, 'headHtmlLocation') !== false) {

            $headHtmlLocationPath = Arr::get($this->maker->config, 'headHtmlLocation', $this->maker->destinationDir . "/head.html");

            File::ensureDirectoryExists(pathinfo($headHtmlLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($headHtmlLocationPath),
                implode("\n", array_merge($this->maker->html, $this->maker->msapplication))
            );

        }

        if (Arr::get($this->maker->config, 'headHtmlBladeLocation') !== false) {

            $headHtmlBladeLocationPath = Arr::get($this->maker->config, 'headHtmlBladeLocation', 'resources/views/head-icons.blade.php');

            File::ensureDirectoryExists(pathinfo($headHtmlBladeLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($headHtmlBladeLocationPath),
                implode("\n", array_merge($this->maker->html, $this->maker->msapplication))
            );

        }

        if (Arr::get($this->maker->config, 'configThemeLocation') !== false) {

            $configThemeLocationPath = Arr::get($this->maker->config, 'configThemeLocation', 'config/theme.php');

            File::ensureDirectoryExists(pathinfo($configThemeLocationPath)['dirname']);

            File::put(
                Path::currentDirectory($configThemeLocationPath),
                $this->manifestIcons
            );

        }

        if (Arr::get($this->maker->config, 'webManifestLocation') !== false) {
            $this->writeManifest('webManifestLocation', 'public/manifest.webmanifest');
        }

        if (Arr::get($this->maker->config, 'manifestJsonLocation') !== false) {
            $this->writeManifest('manifestJsonLocation', 'public/manifest.webmanifest');
        }
    }

    protected function writeManifest(string $configValue, string $defaultPath)
    {
        $path = Arr::get($this->maker->config, $configValue, $defaultPath);

        File::ensureDirectoryExists(pathinfo($path)['dirname']);

        File::put(
            Path::currentDirectory($path),
            json_encode($this->maker->manifestConfig, JSON_UNESCAPED_SLASHES)
        );
    }
}
