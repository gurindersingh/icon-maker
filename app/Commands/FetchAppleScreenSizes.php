<?php

namespace App\Commands;

use App\Services\Support\FetchIosDeviceScreenSizes;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class FetchAppleScreenSizes extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'fetch-apple-sizes';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        FetchIosDeviceScreenSizes::make()->getSizes();
    }
}
