<?php

namespace App\Commands;

use App\Exceptions\SupervisordIsAlreadyRunningException;
use App\Exceptions\SupervisordNotInstalledException;
use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'start';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Start all services';

    protected ?string $failedToStartError = null;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $configRepository, SupervisordRepository $supervisorRepository)
    {
        $configRepository->writeSupervisordConfiguration();

        $this->task('Starting services', function () use ($supervisorRepository) {
            try {
                $supervisorRepository->startSupervisord();

                return true;
            } catch (SupervisordIsAlreadyRunningException | SupervisordNotInstalledException $exception) {
                $this->failedToStartError = $exception->getMessage();

                return false;
            }
        });

        if ($this->failedToStartError) {
            $this->comment($this->failedToStartError);
        }
    }
}
