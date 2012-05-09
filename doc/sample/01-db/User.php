<?php
namespace Ray\Di\Sample;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class User
{
    private $db;

    /**
     * @Inject
     * @Named("pdo=pdo_user")
     */
    public function __construct(\PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * @PostConstruct
     */
    public function init()
    {
        // if not exist...
        $this->db->query("CREATE TABLE User (Id INTEGER PRIMARY KEY, Name TEXT, Age INTEGER)");
    }

    /**
     * @Transactional
     */
    public function createUser($name, $age)
    {
        $sth = $this->db->prepare("INSERT INTO User (Name, Age) VALUES (:name, :age)");
        $sth->execute(array(':name' => $name, ':age' => $age));
    }

    /**
     * @Template
     */
    public function readUsers()
    {
        $sth = $this->db->query("SELECT name, age FROM User");
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
}