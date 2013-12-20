<?php

namespace Ray\Di\Modules;

use Ray\Di\AbstractModule;

class InterfaceBindModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind('Ray\Di\Mock\DbInterface')->to('Ray\Di\Mock\UserDb');
        $this->bind('Ray\Di\Mock\ChildDbInterface')->to('Ray\Di\Mock\DbInterface');
    }
}
