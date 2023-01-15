<?php

namespace App\Services\Support;

use Goutte\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class FetchIosDeviceScreenSizes
{
    protected array $resolutions = [];

    protected array $sizes = [];

    public static function make(): FetchIosDeviceScreenSizes
    {
        return new self();
    }

    public function getSizes(): array
    {
        if ($this->hasSizes() && !$this->needRefresh()) {
            return $this->allSizes();
        }

        $this->fetchNewSizes();

        return $this->allSizes();
    }

    protected function fetchNewSizes(): void
    {
        $html = $this->client()->request('GET', 'https://developer.apple.com/design/human-interface-guidelines/ios/visual-design/adaptivity-and-layout/#device-screen-sizes-and-orientations')->html();
        //$html = File::get(__DIR__ . '/index.html');

        $crawler = (new Crawler($html));

        $crawler
            ->filter('#device-screen-sizes-and-orientations + table > tbody > tr')
            ->each(function (Crawler $node, $i) {
                $slug = ($name = str($node->filter('td')->first()->text())->replace('.', '-'))->slug()->toString(); // 12.9" iPad Pro
                $text = str($node->filter('td')->last()->text())->between('(', ')'); // 1024x1366 pt (2048x2732 px @2x) -> 2048x2732 px @2x
                $dims = $text->before(' ')->explode('x')->toArray();
                $res = (int)$text->between('@', 'x')->toString();

                $this->resolutions[$slug . '-portrait'] = [
                    'name'   => $slug,
                    'type'   => 'portrait',
                    'width'  => (int)$dims[0],
                    'height' => (int)$dims[1],
                    'res'    => $res
                ];

                $this->resolutions[$slug . '-landscape'] = [
                    'name'   => $slug,
                    'type'   => 'landscape',
                    'width'  => (int)$dims[1],
                    'height' => (int)$dims[0],
                    'res'    => $res
                ];
            });

        $this->ensureDirExist();

        File::put($this->sizesLocation(), collect([
            'timestamp' => now()->utc()->unix(),
            'sizes'     => $this->resolutions,
        ])->toJson());

        $this->readSizes();
    }

    protected function client(): Client
    {
        return app(Client::class);
    }

    protected function sizesLocation(): string
    {
        File::ensureDirectoryExists(Path::homeDir('.icon-maker/data'));

        return Path::homeDir('.icon-maker/data/ios-screen-sizes.json');
    }

    protected function ensureDirExist(): void
    {
        File::ensureDirectoryExists(__DIR__ . '/data');
    }

    protected function hasSizes(): bool
    {
        if (!File::isFile($this->sizesLocation())) return false;

        $this->readSizes();

        return Arr::has($this->sizes, 'timestamp');
    }

    protected function readSizes(): void
    {
        $this->sizes = json_decode(File::get($this->sizesLocation()), true);
    }

    protected function allSizes(): array
    {
        return $this->sizes['sizes'];
    }

    protected function needRefresh(): bool
    {
        if ($timestamp = Arr::get($this->sizes, 'timestamp')) {
            return now()->diffInMonths(Carbon::createFromTimestamp($timestamp)) > 2;
        }
        return true;
    }
}
