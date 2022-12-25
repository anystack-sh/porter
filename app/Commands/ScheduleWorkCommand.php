<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

class ScheduleWorkCommand extends Command
{
    protected $name = 'schedule:work';

    public function handle(ConfigRepository $repository)
    {
        $this->components->info('Running schedule tasks every minute.');
        [$lastExecutionStartedAt, $executions] = [null, []];

        while (true) {
            usleep(100 * 1000);

            if (Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $executions[] = $execution = new Process([
                    PHP_BINARY,
                    $repository->porterBinaryPath(),
                    'schedule:run',
                ]);

                $execution->start();

                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($executions as $key => $execution) {
                $output = $execution->getIncrementalOutput().
                    $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }
}
