<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use LaravelZero\Framework\Commands\Command;

class RestartCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'restart';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Restart all services';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SupervisordRepository $supervisorRepository, ConfigRepository $configRepository)
    {
        $configRepository->writeSupervisordConfiguration();

        $this->task('Restarting Porter', fn () => $supervisorRepository->restartSupervisord());
    }
}
