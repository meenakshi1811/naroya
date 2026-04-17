<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class QueueWorkerCron extends Command
{
    protected $signature = 'queue:cron-worker';
    protected $description = 'Run queue worker from cron';

    public function handle()
    {
        $this->info('Running queue worker at: ' . now());

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--timeout' => 90,
        ]);

        $this->line(Artisan::output());

        return Command::SUCCESS;
    }
}