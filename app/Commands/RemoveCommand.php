<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use LaravelZero\Framework\Commands\Command;

class RemoveCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'remove';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Remove application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $repository)
    {
        $this->task(sprintf('Removing %s', getcwd()), function () use ($repository) {
            $repository->removeApplication(getcwd());

            return true;
        });

        $this->call(RestartCommand::class);
    }
}
