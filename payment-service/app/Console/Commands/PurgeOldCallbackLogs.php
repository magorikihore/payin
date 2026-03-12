<?php

namespace App\Console\Commands;

use App\Models\CallbackLog;
use Illuminate\Console\Command;

class PurgeOldCallbackLogs extends Command
{
    protected $signature = 'callback-logs:purge {--days=3 : Delete logs older than this many days}';
    protected $description = 'Delete callback logs older than the specified number of days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = CallbackLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} callback logs older than {$days} days.");

        return self::SUCCESS;
    }
}
