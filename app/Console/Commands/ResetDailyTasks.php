<?php

namespace App\Console\Commands;

use App\Jobs\ResetDailyTasks as ResetDailyTasksJob;
use Illuminate\Console\Command;

class ResetDailyTasks extends Command
{
    protected $signature = 'app:reset-daily-tasks
                            {--scheduled : Ignored; accepted so stale scheduler entries do not fail}';

    protected $description = 'Reset task limits for users whose local date has advanced';

    public function handle(ResetDailyTasksJob $resetDailyTasks): int
    {
        if ($this->option('scheduled')) {
            $this->info('Scheduled daily task resets are disabled.');

            return self::SUCCESS;
        }

        $count = $resetDailyTasks->handle('manual');

        $this->info("Reset daily task limits for {$count} users.");

        return self::SUCCESS;
    }
}
