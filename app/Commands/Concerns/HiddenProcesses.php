<?php

namespace App\Commands\Concerns;

trait HiddenProcesses
{
    /**
     * Hidden background processes
     *
     * @var array
     */
    protected $hiddenProcesses = ['scheduler', 'watcher'];
}
