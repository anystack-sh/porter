<?php

namespace App\Commands;

use App\Repositories\SupervisordRepository;
use LaravelZero\Framework\Commands\Command;

class StopCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'stop {--force}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Stop all services';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SupervisordRepository $repository)
    {
        $this->task('Stopping services', function () use ($repository) {
            return $repository->stopSupervisord($this->option('force'));
        });
    }
}
