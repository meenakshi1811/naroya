<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ResetMonthlyPayout extends Command
{
    protected $signature = 'payout:reset-monthly';
    protected $description = 'Reset monthly_payout flag to 0 for all approved doctors';

    public function handle()
    {
        $updated = User::query()
            ->where('chrApproval', 'Y')
            ->update(['monthly_payout' => 0]);

        $this->info("Monthly payout flag reset for {$updated} doctors.");

        return self::SUCCESS;
    }
}
