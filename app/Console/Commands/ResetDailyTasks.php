<?php

namespace App\Console\Commands;

use App\Jobs\ResetDailyTasks as ResetDailyTasksJob;
use Illuminate\Console\Command;

class ResetDailyTasks extends Command
{
    protected $signature = 'app:reset-daily-tasks';

    protected $description = 'Reset task limits for users whose local date has advanced';

    public function handle(ResetDailyTasksJob $resetDailyTasks): int
    {
        $count = $resetDailyTasks->handle('manual');

        $this->info("Reset daily task limits for {$count} users.");

        return self::SUCCESS;
    }
}
