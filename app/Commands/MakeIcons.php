<?php

namespace App\Commands;

use App\Services\IconMaker\IconMaker;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class MakeIcons extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'make:icon {--S|splash}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Make regular & PWA icons';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        IconMaker::make($this)->makeIcons();

        return self::SUCCESS;
    }
}
