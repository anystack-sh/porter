<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use LaravelZero\Framework\Commands\Command;

class AddCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'add';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Add new application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $configRepository)
    {
        if (file_exists('porter.yml') === false) {
            $this->task('Creating porter.yml boilerplate', function () use ($configRepository) {
                $configRepository->createStub();

                return true;
            });
        }

        $this->task(sprintf('Adding %s', getcwd()), function () use ($configRepository) {
            $configRepository->addApplication(getcwd());

            return true;
        });

        $this->call(RestartCommand::class);
    }
}
