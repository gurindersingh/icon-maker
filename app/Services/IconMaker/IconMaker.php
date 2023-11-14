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

class IconMaker
{
    public array $config = [];

    public string $sourceDir = 'art/images/icon-maker';

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

    public function setCommand(Command $command): IconMaker
    {
        $this->command = $command;
        return $this;
    }

    public static function make(Command $command): IconMaker
    {
        return (new self())->setCommand($command);
    }

    public function makeIcons(): void
    {
        $this->setHash();

        $this->readConfig();

        $this->ensureRequiredSourceFilesExist();

        $this->cleanDestinationDir('public/assets/icons');

        Path::ensureDirExist($this->destinationDir);

        $this->laravelConfig['theme-color'] = $this->manifestConfig['theme_color'] = $this->command->ask('Theme color?', '#ffffff');

        /** @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        $pipes = [
            Favicon::class,
            Icon::class,
            Splash::class,
            ExportArtifacts::class,
            OptimizeIcons::class,
        ];

        terminal()->clear();

        render('<p class="bg-white text-gray-700 px-2 py-0.5">Creating icons</p>');

        $pipeline
            ->via('handle')
            ->send($this)
            ->through($pipes)
            ->then(function (IconMaker $maker) {
                render('<p class="bg-green-700 text-white px-2 py-0.5">Finished</p>');
            });
    }

    protected function setHash(): void
    {
        $this->hash = hash('crc32', now()->getTimestamp(), FALSE);
        $this->iconsConfig['hash'] = $this->hash;
    }

    protected function ensureRequiredSourceFilesExist(): void
    {
        if (Splash::enabled($this->command)) {
            $this->regularFiles = array_merge($this->regularFiles, $this->splashFiles);
        }

        $files = collect($this->regularFiles)
            ->map(function ($name) {
                [$fileName, $fileDescription] = explode('|', $name);
                return Path::isFile($this->sourceDir . '/' . $fileName) ? null : [
                    'filename'    => Path::currentDirectory("{$this->sourceDir}/{$fileName}"),
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

    protected function cleanDestinationDir($path): void
    {
        Path::cleanDir(
            Path::currentDirectory($path)
        );
    }

    protected function readConfig(): void
    {
        if (File::isFile(Path::currentDirectory('icon-maker.json'))) {
            $this->config = json_decode(File::get(Path::currentDirectory('icon-maker.json')), true);
        }

        $this->destinationDir = Arr::has($this->config ?? [], 'destinationDirectory')
            ? Arr::get($this->config, 'destinationDirectory', 'public/assets/icons') . '/' . $this->hash
            : 'public/assets/icons/' . $this->hash;
    }
}
