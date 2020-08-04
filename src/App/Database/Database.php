<?php

/**
 * Абстрактный базовый класс для работы с СУБД
 *
 * @author    andrey-tech
 * @copyright 2019-2020 andrey-tech
 * @see https://github.com/andrey-tech/database-php
 * @license   MIT
 *
 * @version 2.0.0
 *
 * v1.0.0 (07.06.2019) Начальный релиз
 * v1.0.1 (22.07.2019) Изменения для App
 * v1.2.0 (23.07.2019) Добавлен метод fetchAll()
 * v1.2.1 (21.08.2019) Добавлен метод connect()
 * v1.3.0 (25.08.2019) Добавлен параметр конфигурации wait_timeout
 * v1.4.0 (27.08.2019) Изменено место хранения единственного объекта дочернего класса
 * v1.4.1 (31.08.2019) Добавлена проверка результата execute()
 * v1.5.0 (23.10.2019) Добавлен метод getLastInsertId()
 * v1.6.0 (02.11.2019) Добавлен отладочный режим, методы debugStatement() и interpolateQuery()
 * v1.7.0 (03.11.2019) Добавлен метод createInStatement()
 * v1.7.1 (06.11.2019) Метод fetchAll() теперь public
 * v2.0.0 (03.08.2020) Разделение класса на подклассы по типам СУБД. Изменения в названиях методов
 *
 */

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use PDOStatement;
use Generator;

abstract class Database
{
    /**
     * Флаг включения отладочного режима
     * @var boolean
     */
    public $debug = false;

    /**
     * Параметры соединения с СУБД
     * @var array
     */
    protected $config = [];

    /**
     * Опции подключения для драйвера СУБД
     * @var array
     */
    protected $options = [];

    /**
     * Счетчик числа выполненных запросов для отладки
     * @var integer
     */
    protected $queryCounter = 0;

    /**
     * Объект PDO
     * @var PDO
     */
    protected $pdo;

    /**
     * Кэш дескрипторов операторов PDO
     * @var array
     */
    protected $statements = [];

    /**
     * Массив единственных объектов каждого дочернего класса
     * @var array
     */
    private static $instances = [];

    /**
     * Конструктор
     * @param array $config Конфигурация соединения с СУБД
     * @param array $options Опции подключения для драйвера СУБД
     */
    protected function __construct(array $config = [], array $options = [])
    {
        $this->config = array_replace($this->config, $config);
        $this->options = array_replace($this->options, $options);
    }

    /**
     * Возвращает единственный объект дочернего класса
     * @param array $config Конфигурация соединения с СУБД
     * @param array $options Опции подключения для драйвера СУБД
     * @return Database
     */
    public static function instance(array $config = [], array $options = []) :Database
    {
        if (!isset(self::$instances[static::class])) {
            self::$instances[static::class] = new static($config, $options);
        }
        return self::$instances[static::class];
    }

    /**
     * Выполняет подключение к СУБД
     * @return void
     */
    abstract public function connect();

    /**
     * Выполняет отключение от БД
     * @return void
     */
    abstract public function disconnect();

    /**
     * Возвращает объект класса PDO
     * @return PDO
     */
    public function getPdo() :PDO
    {
        return $this->pdo;
    }

    /**
     * Создаёт, кэширует и возвращает дескриптор оператора СУБД
     * @param string $statement SQL оператор
     * @param array $prepareOptions Опции драйвера СУБД для подготовки запроса
     * @return PDOStatement
     * @throws DatabaseException
     */
    protected function prepareStatement(string $statement, array $prepareOptions = []): PDOStatement
    {
        if (isset($this->statements[$statement])) {
            return $this->statements[$statement];
        }

        if (! isset($this->pdo)) {
            $this->connect();
        }

        try {
            $this->statements[$statement] = $this->pdo->prepare($statement, $prepareOptions);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $this->statements[$statement];
    }

    /**
     * Подготавливает запрос и запускает подготовленный запрос на выполнение
     * @param string $statement SQL оператор
     * @param array $values Массив значений для SQL оператора
     * @param bool $isNamed Флаг именованных параметров (:name) в SQL операторе
     * @param array $prepareOptions Опции драйвера СУБД для подготовки запроса
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function doStatement(
        string $statement,
        array $values = [],
        bool $isNamed = true,
        array $prepareOptions = []
    ): PDOStatement {

        $stmtHandle = $this->prepareStatement($statement, $prepareOptions);
        $this->debugStatement($statement, $values);

        if ($isNamed) {
            $values = $this->getNamedValues($values, $statement);
        }

        try {
            $stmtHandle->closeCursor();
            $stmtHandle->execute($values);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $stmtHandle;
    }

    /**
     * Возвращает массив именованных параметров (:name) для SQL оператора
     * @param array $values Массив значений для SQL оператора
     * @param string $statement SQL оператор
     * @return array Массив именованных значений для SQL оператора
     */
    protected function getNamedValues(array $values, string $statement): array
    {
        // Добавляет : ко всем ключам массива значений
        $values = array_combine(array_map(function ($key) {
            return ':' . $key;
        }, array_keys($values)), array_values($values));

        // Удаляет из массива значения, неиспользуемые в SQL операторе
        if (preg_match_all('/:\w+/', $statement, $matches)) {
            $allowed = $matches[0];
            $values  = array_filter($values, function ($key) use ($allowed) {
                return in_array($key, $allowed);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $values;
    }

    /**
     * Инициализирует транзакцию
     * @return void
     * @throws DatabaseException
     */
    public function beginTransaction()
    {
        $this->debugStatement('BEGIN TRANSACTION');

        try {
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Фиксирует транзакцию
     * @return void
     * @throws DatabaseException
     */
    public function commitTransaction()
    {
        $this->debugStatement('COMMIT TRANSACTION');

        try {
            $this->pdo->commit();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Откатывает транзакцию
     * @return void
     * @throws DatabaseException
     */
    public function rollbackTransaction()
    {
        $this->debugStatement('ROLLBACK TRANSACTION');

        try {
            $this->pdo->rollback();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Выбирает все записи с помощью генератора
     * @param PDOStatement $stmt Объект класса PDOStatement
     * @return Generator
     */
    public function fetchAll(PDOStatement $stmt): Generator
    {
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    /**
     * Возвращает значение 'id' последней вставленной записи
     * @param string|null $idName Имя столбца
     * @return string
     */
    public function getLastInsertId(string $idName = null) :string
    {
        return $this->pdo->lastInsertId($idName);
    }

    /**
     * Создает строку вида ?, ?, ? для выражения IN (?, ?, ?)
     * @param array $in Массив значений внутри выражения IN()
     * @return string
     */
    public function createInStatement(array $in = []): string
    {
        return implode(', ', array_fill(0, count($in), '?'));
    }

    /**
     * Выводит в STDOUT SQL оператор и значения для SQL оператора
     * @param string $statement SQL оператор
     * @param array $values Массив значений для SQL оператора
     * @return void
     */
    protected function debugStatement(string $statement, array $values = [])
    {
        $this->queryCounter++;

        if (! $this->debug) {
            return;
        }

        $query = $this->interpolateQuery($statement, $values);
        $query = preg_replace('/[\r\n\s]+/m', ' ', $query);

        $this->debug("***** [{$this->queryCounter}] {$query}");
    }

    /**
     * Выводить отладочное сообщение в STDOUT
     * @param string $message Текст сообщения
     */
    protected function debug(string $message)
    {
        echo $message . PHP_EOL;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are in the same order as specified in $query
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @return string The interpolated query
     * @see https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements/1376838
     */
    protected function interpolateQuery(string $query, array $params = []): string
    {
        $keys   = [];
        $values = $params;

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value)) {
                $values[$key] = "'" . $value . "'";
            }

            if (is_array($value)) {
                $values[$key] = "'" . implode("','", $value) . "'";
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        $query = preg_replace($keys, $values, $query);

        return $query;
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
