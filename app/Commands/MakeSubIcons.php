<?php

namespace App\Commands;

use App\Services\IconMaker\SubIconMaker;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class MakeSubIcons extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'make:sub-icon {--S|splash} {--O|optimize} {--P|path}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Make sub icons';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        SubIconMaker::make($this)->makeIcons();

        return self::SUCCESS;
    }
}
