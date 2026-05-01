<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralSetting;
use Carbon\Carbon;

class ResetBookCount extends Command
{
    protected $signature = 'reset:bookcount';
    protected $description = 'Reset the book_count table on scheduled date and time';

    public function handle()
    {
        $resetSetting = GeneralSetting::where('field_name', 'reset_book_date')->first();

        if (!$resetSetting || !$resetSetting->field_value) {
            $this->error('Reset book date setting not found or not set.');
            return 1;
        }

        $resetAt = Carbon::parse($resetSetting->field_value);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($resetAt)) {
            //  Clear the book_count table
            DB::table('book_count')->truncate();

            // Optional: Clear the reset date so it doesn't repeat
            $resetSetting->update(['field_value' => null]);

            $this->info('book_count table has been reset successfully.');
        } else {
            $this->info('Current time has not reached the reset date/time. No action taken.');
        }

        return 0;
    }
}