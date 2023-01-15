<?php

namespace App\Services\IconMaker\Icons;

use App\Services\Support\Path;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\Finder\SplFileInfo;

class OptimizeIcons extends BaseIconMaker implements IconMakerContract
{

    protected \Spatie\ImageOptimizer\OptimizerChain $optimizer;

    protected array $result = [];

    protected function process(): void
    {
        if (!$this->enabled()) return;

        $this->optimizer = OptimizerChainFactory::create();

        $files = File::allFiles(
            $this->maker->destinationDir
        );

        collect($files)->each(function (SplFileInfo $file) {
            $this->optimizeFile($file);
        });

        $this->command->table(['Filename', 'Before size', 'Optimized Size', '%age reduction'], $this->result);
    }

    protected function enabled(): bool
    {
        return !!$this->command->option('optimize');
    }

    protected function optimizeFile(SplFileInfo $file)
    {
        $this->command->task("Optimization: optimizing " . $file->getBasename(), function () use ($file) {
            $previousSize = $file->getSize();

            $this->optimizer->optimize(
                $file->getRealPath()
            );

            $newSize = File::size($file->getRealPath());
            $percentage = round(((($previousSize - $newSize)/$previousSize) * 100), 1);


            $this->result[] = [
                $file->getBasename(),
                $this->size($previousSize),
                $this->size($newSize),
                $percentage <> 0 ? "<info>" . '-'.$percentage . '%' . "</info>" : '---'
            ];
        }, 'Optimizing...');
    }

    public function size($size, $precision = 2): string
    {
        $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $step = 1024;
        $i = 0;
        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }
        return round($size, $precision) . $units[$i];
    }
}
