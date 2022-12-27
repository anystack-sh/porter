<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use LaravelZero\Framework\Commands\Command;

class InitCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create new configuration';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $configRepository)
    {
        if (file_exists('porter.yml') === true) {
            $this->comment('Porter.yml already exists, skipping...');

            return;
        }

        if ($this->confirm(sprintf('Create porter.yml in %s?', getcwd()), true)) {
            $this->task('Creating porter.yml boilerplate', function () use ($configRepository) {
                $configRepository->createStub();

                return true;
            });

            $this->comment('Run "porter add" to add your product and start your services.');
        }
    }
}
