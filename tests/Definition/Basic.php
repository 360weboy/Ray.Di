<?php

namespace Ray\Di\Definition;

use Ray\Di\Mock\DbInterface;
use Ray\Di\Mock\UserInterface;
use Ray\Di\Di\Inject;

/**
 * Setter Injection
 *
 */
class Basic
{
    /**
     * @var DbInterface
     */
    public $db;

    /**
     * @Inject
     *
     * @param DbInterface $db
     */
    public function setDb(DbInterface $db)
    {
        $this->db = $db;
    }
}
