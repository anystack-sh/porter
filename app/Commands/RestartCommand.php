<?php

namespace App\Commands;

use App\Commands\Concerns\HiddenProcesses;
use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use LaravelZero\Framework\Commands\Command;

class RestartCommand extends Command
{
    use HiddenProcesses;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'restart {name?}';

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
        $this->ensurePorterIsRunning();

        $configRepository->writeSupervisordConfiguration();

        $availableProcesses = $supervisorRepository->getAllProcessInfo()
            ->map(fn ($process) => $process['group'].':'.$process['name'])
            ->prepend('all')
            ->reject(fn ($val) => in_array($val, $this->hiddenProcesses, true));

        if ($name = $this->argument('name')) {
            $processes = $availableProcesses->filter(function ($process) use ($name) {
                return $process === $name;
            })->toArray();
        } else {
            $processes = $this->choice(
                'Which service would you like to restart?',
                $availableProcesses->toArray(),
                null, null, true
            );
        }

        if (in_array('all', $processes, true)) {
            $this->task('Restarting Porter', fn () => $supervisorRepository->restartSupervisord());

            return;
        }

        foreach ($processes as $process) {
            $this->task("Restarting {$process}", fn () => $supervisorRepository->restartProcess($process));
        }
    }
}
