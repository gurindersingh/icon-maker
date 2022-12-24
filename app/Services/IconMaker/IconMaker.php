<?php

namespace App\Services\IconMaker;

use App\Services\Support\Path;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use LaravelZero\Framework\Commands\Command;
use function Termwind\{render, terminal};

class IconMaker
{
    protected string $sourceDir = 'art/images/icon-maker';

    protected string $destinationDir = 'public/assets/icons';

    protected array $regularFiles = [
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

    protected Command $command;

    protected string $hash;

    protected array $html = [];

    protected array $iconsConfig = [];

    protected array $manifestConfig = [
        'theme_color' => '#ffffff',
    ];

    protected array $iconPaths = [];

    protected array $msapplication = [];

    public function setCommand(Command $command): IconMaker
    {
        $this->command = $command;
        return $this;
    }

    public static function make(Command $command): IconMaker
    {
        return (new self())->setCommand($command);
    }

    public function makeIcons()
    {
        $this->setHash();

        $this->ensureRequiredSourceFilesExist();

        $this->cleanDestinationDir();

        Path::ensureDirExist($this->destinationDir);

        $this->processFavicons();

        $this->processIcons();

        $this->processMaskableIcons();

        $this->processManifest();
    }

    protected function setHash(): void
    {
        $this->hash = hash('crc32', now()->getTimestamp(), FALSE);
    }

    protected function ensureRequiredSourceFilesExist(): void
    {
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
            $this->command->table(['Filename', 'Status', 'Description'], $files->map(fn($arr) => [$arr['filename'], $arr['status'], $arr['description']]));
            exit(0);
        }
    }

    protected function cleanDestinationDir(): void
    {
        Path::cleanDir(
            Path::currentDirectory($this->destinationDir)
        );
    }

    protected function processFavicons(): void
    {
        $this->command->comment('--- Favicons: ico & png images');
        $image = $this->loadImage(explode('|', $this->regularFiles['favicon'])[0]);
        $image->backup();
        $i = $icoName = "favicon-{$this->hash}.ico";
        $p = $pngName = "favicon-{$this->hash}.png";

        $pngName = "{$this->destinationDir}/{$pngName}";
        $icoName = "{$this->destinationDir}/{$icoName}";

        $image->fit(32, 32)
            ->save(Path::currentDirectory($pngName), 100)
            ->encode('ico')
            ->save(Path::currentDirectory($icoName), 100);

        $this->command->comment('<comment>' . $p . ' created</comment>');
        $this->command->comment('<comment>' . $i . ' created</comment>');
        $this->iconPaths['favicon-png'] = $pngName = str($pngName)->after('public/')->toString();
        $this->iconPaths['favicon-ico'] = $icoName = str($icoName)->after('public/')->toString();

        $this->html[] = "<link rel=\"shortcut icon\" href=\"{{ asset('{$icoName}') }}\" type=\"image/x-icon\">";
        $this->html[] = "<link rel=\"icon\" sizes=\"32x32\" href=\"{{ asset('{$pngName}') }}\" type=\"image/png\">";
        $image->destroy();
    }

    protected function processIcons(): void
    {
        $this->command->comment('--- Icons');
        $image = $this->loadImage(explode('|', $this->regularFiles['icon'])[0]);
        $image->backup();

        foreach ([512, 192] as $dim) {
            $name = "icon-{$dim}-{$this->hash}.png";
            $image->fit($dim, $dim)->save($path = "{$this->destinationDir}/{$name}", 100);
            $image->reset();
            $this->command->comment('<comment>' . $name . ' created</comment>');
            $this->iconsConfig["icon-{$dim}"] = str($path)->after('public/')->toString();
            $this->manifestConfig['icons'][] = [
                "src"   => $src = str("{$this->destinationDir}/{$name}")->after('public/')->toString(),
                "sizes" => "{$dim}x{$dim}",
                "type"  => "image/png"
            ];
            $this->iconPaths['icon-' . $dim] = $src;
        }
        $image->destroy();
    }

    protected function processMaskableIcons(): void
    {
        $this->command->comment('--- Maskable Icons');
        $image = $this->loadImage(explode('|', $this->regularFiles['icon-maskable'])[0]);
        $image->backup();

        foreach ([1024, 512, 310, 270, 192, 144, 150, 70] as $dim) {
            //$name = $this->randomName('png');
            $name = "maskable-icon-{$dim}-{$this->hash}.png";
            $image->fit($dim, $dim)->save($path = "{$this->destinationDir}/{$name}", 100);
            $image->reset();
            $this->command->comment('<comment>' . $name . ' created</comment>');
            $path = str($path)->after('public/')->toString();

            $this->iconPaths['icon-' . $dim] = $path;

            if (in_array($dim, [512, 192])) {
                $this->iconsConfig["icon-maskable-{$dim}"] = $path;
            }

            $this->msapplication[] = "<meta name=\"msapplication-square{$dim}x{$dim}logo\" content=\"{{ asset('{$path}') }}\" />";

            if ($dim == 144) {
                $this->msapplication[] = "<meta name=\"msapplication-TileImage\" content=\"{{ asset('{$path}') }}\" />";
            }

            if ($dim == 1024) {
                $this->html[] = "<link rel=\"apple-touch-icon\" sizes=\"192x192\" href=\"{{ asset('{$path}') }}\" />";
            }

            if (in_array($dim, [192, 512])) {
                $this->manifestConfig['icons'][] = [
                    "src"     => str("{$this->destinationDir}/{$name}")->after('public/')->toString(),
                    "sizes"   => "{$dim}x{$dim}",
                    "type"    => "image/png",
                    "purpose" => "maskable",
                ];
            }
        }
    }

    protected function processManifest(): void
    {
        $this->command->comment('--- Creating Manifest');
        $manifestIcons = "<?php \n\nreturn [\n";
        //$manifestIcons = "<!-------\n";
        $manifestIcons .= "'theme-color' => '{$this->manifestConfig['theme_color']}',\n";

        foreach (['logo-vector', 'logo-sm-vector', 'apple-tab-icon'] as $name) {
            $logoName = "{$name}-{$this->hash}.svg";  //$this->randomName('svg');

            copy(
                Path::currentDirectory($this->sourceDir . '/' . explode('|', $this->regularFiles[$name])[0]),
                $path = $this->destinationDir . '/' . $logoName
            );
            $this->iconPaths[$name] = $this->removePublicPath($path);
            $manifestIcons .= "'{$name}' => '{$this->iconPaths[$name]}',\n";
        }

        foreach (['logo', 'logo-sm'] as $name) {
            $logoName = "{$name}-{$this->hash}.png";  //$this->randomName('svg');

            copy(
                Path::currentDirectory($this->sourceDir . '/' . explode('|', $this->regularFiles[$name])[0]),
                $path = $this->destinationDir . '/' . $logoName
            );
            $this->iconPaths[$name] = $this->removePublicPath($path);
            $manifestIcons .= "'{$name}' => '{$this->iconPaths[$name]}',\n";
        }

        $this->html[] = "<link rel=\"mask-icon\" sizes=\"any\" href=\"{{ asset('{$this->iconPaths['apple-tab-icon']}') }}\" color=\"{{ config('app.theme.theme-color') }}\">";
        $this->html[] = "<meta name=\"mobile-web-app-capable\" content=\"yes\" />";
        $this->html[] = "<meta name=\"apple-mobile-web-app-title\" content=\"{{ config('app.name') }}\" />";
        $this->html[] = "<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"white\" />";
        $this->html[] = "<meta name=\"theme-color\" content=\"{{ config('app.theme.theme-color') }}\" />";
        $this->html[] = "<link rel=\"manifest\" href=\"/manifest.json\" crossOrigin=\"use-credentials\" />";
        $this->msapplication[] = "<meta name=\"msapplication-TileColor\" content=\"white\">";

        foreach ($this->iconsConfig as $k => $v) {
            $manifestIcons .= "'{$k}' => '{$v}',\n";
        }
        $manifestIcons .= "];\n";

        File::put(
            Path::currentDirectory($this->destinationDir . "/head.html"),
            implode("\n", array_merge($this->html, $this->msapplication))
        );

        File::put(
            Path::currentDirectory('resources/views/head-icons.blade.php'),
            implode("\n", array_merge($this->html, $this->msapplication))
        );

        File::put(
            Path::currentDirectory('config/theme.php'),
            $manifestIcons
        );

        $this->generateManifest();
    }

    protected function generateManifest(): void
    {
        File::put($this->manifestLocation(), json_encode($this->manifestConfig, JSON_UNESCAPED_SLASHES));
        File::put($this->manifestLocation(true), json_encode($this->manifestConfig, JSON_UNESCAPED_SLASHES));
    }

    protected function manifestLocation($webmanifest = false): string
    {
        return $webmanifest ?
            Path::currentDirectory("public/manifest.webmanifest") :
            Path::currentDirectory("public/manifest.json");
    }

    protected function loadImage($src): \Intervention\Image\Image
    {
        return (new ImageManager(['driver' => 'imagick']))->make("{$this->sourceDir}/{$src}");
    }

    protected function removePublicPath(string $iconPaths): string
    {
        return str($iconPaths)->after('public/')->prepend('/')->toString();
    }
}
