<?php

/**
 * Класс для работы с СУБД MySQL
 *
 * @author    andrey-tech
 * @copyright 2019-2020 andrey-tech
 * @see https://github.com/andrey-tech/database-php
 * @license   MIT
 *
 * @version 1.0.0
 *
 * v1.0.0 (03.08.2020) Начальный релиз
 *
 */

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class MySQLDatabase extends Database
{
    /**
     * Параметры соединения с СУБД MySQL
     * @var array
     */
    protected $config = [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'db',
        'charset'  => 'utf8mb4',
        'user'     => '',
        'password' => ''
    ];

    /**
     * Опции подключения для драйвера СУБД MySQL
     * @var array
     */
    protected $options = [
         PDO::ATTR_TIMEOUT            => 60,
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    /**
     * Выполняет подключение к СУБД
     * @return void
     * @throws DatabaseException
     */
    public function connect()
    {
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";

        $this->debug("***** CONNECT {$this->config['host']}");

        try {
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['password'], $this->options);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Выполняет отключение от БД
     * @return void
     */
    public function disconnect()
    {
        $this->debug("***** DISCONNECT {$this->config['host']}");
        $this->pdo = null;
    }
}
