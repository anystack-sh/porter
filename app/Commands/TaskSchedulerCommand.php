<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class TaskSchedulerCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'task-scheduler';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Schedule porter tasks';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        app(ConfigRepository::class)->getApplicationConfigurations()->filter(function ($app) use ($schedule) {
            return collect($app['config']['services'] ?? [])
                ->where('restartInMinutes', '>', 0)
                ->each(function ($command) use ($schedule) {
                    $schedule->call(function () use ($command) {
                        $this->app->make(SupervisordRepository::class)->restartProcess($command['process_name']);
                    })->cron("*/{$command['restartInMinutes']} * * * *");
                });
        });
    }
}
