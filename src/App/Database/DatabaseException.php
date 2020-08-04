<?php

/**
 * Обработчик исключений в классах пространства имен \App\Database
 *
 * @author    andrey-tech
 * @copyright 2019-2020 andrey-tech
 * @see https://github.com/andrey-tech/database-php
 * @license   MIT
 *
 * @version 1.0.2
 *
 * v1.0.0 (28.05.2019) Начальный релиз
 * v1.0.1 (22.07.2019) Изменения для App
 * v1.0.2 (03.08.2020) Добавлен \Exception
 *
 */

declare(strict_types = 1);

namespace App\Database;

use Exception;

class DatabaseException extends Exception
{
    /**
     * Конструктор
     * @param string $message Сообщение об исключении
     * @param int|string $code Код исключения
     * @param Exception|null $previous Предыдущее исключение
     */
    public function __construct(string $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct('App Database: ' . $message, (int) $code, $previous);
    }
}
