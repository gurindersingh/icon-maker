<?php

namespace App\Services\IconMaker;

use Illuminate\Support\Arr;
use App\Services\Support\Path;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\File;
use App\Services\IconMaker\Icons\Icon;
use App\Services\IconMaker\Icons\Splash;
use App\Services\IconMaker\Icons\Favicon;
use function Termwind\{render, terminal};
use LaravelZero\Framework\Commands\Command;
use App\Services\IconMaker\Icons\OptimizeIcons;
use App\Services\IconMaker\Icons\ExportArtifacts;
use App\Services\IconMaker\Icons\ExportSubIconsArtifacts;

class SubIconMaker
{
    public array $subIconConfig = [
        'srcPath' => null,
        'iconName' => null,
        'relativeSrcPath' => null,
        'destPath' => null,
    ];

    public array $config = [];

    public string $sourceDir = 'art/sub-icons';

    public string $destinationDir;

    public array $regularFiles = [
        'favicon'        => 'favicon.png|required size 1024x1024',
        'icon'           => 'icon.png|required size 1024x1024', // 1024
        'icon-maskable'  => 'icon-maskable.png|required size 1024x1024', // 1024
        'logo'           => 'logo.png|PNG format Logo',
        'logo-vector'    => 'logo.svg|SVG Logo',
        'logo-sm'        => 'logo-sm.png|PNG format Logo small - just icon only',
        'logo-sm-vector' => 'logo-sm.svg|SVG Logo small - just icon only',
        'apple-tab-icon' => 'apple-tab-icon.svg|SVG Logo',
    ];

    protected array $splashFiles = [
        'splash' => 'splash.png|required size 8064 x 8064. We will resize it for all splash screens',
    ];

    public Command $command;

    public string $hash;

    public array $html = [];

    public array $iconsConfig = [];

    public array $laravelConfig = [];

    public array $manifestConfig = [
        'theme_color' => '#ffffff',
    ];

    public array $iconPaths = [];

    public array $msapplication = [];

    public function setCommand(Command $command): static
    {
        $this->command = $command;
        return $this;
    }

    public static function make(Command $command): static
    {
        return (new self())->setCommand($command);
    }

    public function makeIcons(): void
    {
        $this->setHash();

        $this->cleanDestinationDir('public/assets/sub-icons');

        foreach ($dirs = File::directories(Path::currentDirectory($this->sourceDir)) as $iconDir) {
            $this->ensureRequiredSourceFilesExist($iconDir);
        }

        foreach ($dirs as $srcPath) {
            $this->makeIconsFor($srcPath);
        }
    }

    protected function setHash(): void
    {
        $this->hash = hash('crc32', now()->getTimestamp(), FALSE);
        $this->iconsConfig['hash'] = $this->hash;
    }

    protected function ensureRequiredSourceFilesExist($path): void
    {
        if (Splash::enabled($this->command)) {
            $this->regularFiles = array_merge($this->regularFiles, $this->splashFiles);
        }

        $files = collect($this->regularFiles)
            ->map(function ($name) use ($path) {
                [$fileName, $fileDescription] = explode('|', $name);
                return Path::isFile($path . '/' . $fileName) ? null : [
                    'filename'    => Path::currentDirectory("{$path}/{$fileName}"),
                    'status'      => "missing",
                    'description' => $fileDescription,
                ];
            })
            ->filter();

        if ($files->isNotEmpty()) {
            render('<p class="bg-red-500 p-1 text-white">Missing following files</p>');
            $this->command->table(['Filename', 'Status', 'Description'], $files->map(fn ($arr) => [$arr['filename'], $arr['status'], $arr['description']]));
            exit(0);
        }
    }

    protected function cleanDestinationDir($dir): void
    {
        Path::cleanDir(
            Path::currentDirectory($dir)
        );
    }

    protected function makeIconsFor($srcPath)
    {
        $srcPath = str($srcPath);
        $iconName = $srcPath->basename()->slug()->toString();
        $iconNameLabel = $srcPath->basename()->slug()->headline()->toString();
        $relativeSrcPath = $srcPath->after(Path::currentDirectory() . '/');
        $destPath = $relativeSrcPath->basename()->slug()->prepend('public/assets/sub-icons/')->append('/' . $this->hash);

        File::ensureDirectoryExists(
            Path::currentDirectory($destPath)
        );

        $this->subIconConfig['srcPath'] = $srcPath->toString();
        $this->subIconConfig['iconName'] = $iconName;
        $this->subIconConfig['relativeSrcPath'] = $relativeSrcPath->toString();
        $this->subIconConfig['destPath'] = $destPath->toString();

        $this->laravelConfig['subIcons'][$iconName]['theme-color']
            = $this->manifestConfig['subIcons'][$iconName]['theme_color']
            = $this->command->ask("Theme color for {$iconNameLabel}?", '#ffffff');

        $pipeline = app(Pipeline::class);

        $pipes = [
            Favicon::class,
            Icon::class,
            Splash::class,
            ExportSubIconsArtifacts::class,
            // OptimizeIcons::class,
        ];

        terminal()->clear();

        render("<p class='bg-white text-gray-700 px-2 py-0.5'>Creating icons for {$iconName}</p>");

        $pipeline
            ->via('handle')
            ->send($this)
            ->through($pipes)
            ->then(function ($maker) {
                render('<p class="bg-green-700 text-white px-2 py-0.5">Finished</p>');
                // dd(
                //     'laravelConfig',
                //     $maker->laravelConfig,
                //     'manifestConfig',
                //     $maker->manifestConfig,
                //     'iconPaths',
                //     $maker->iconPaths,
                //     'iconsConfig',
                //     $maker->iconsConfig
                // );
            });
    }
}
