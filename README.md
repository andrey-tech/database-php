# Database PHP

Простая расширяемая библиотека классов на PHP7+ и PDO для работы с СУБД (MySQL, SQLite и др.) с кэшированием подготовленных запросов.

## Содержание

<!-- MarkdownTOC levels="1,2,3,4,5,6" autoanchor="true" autolink="true" -->

- [Требования](#%D0%A2%D1%80%D0%B5%D0%B1%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F)
- [Установка](#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
- [Класс `Database`](#%D0%9A%D0%BB%D0%B0%D1%81%D1%81-database)
    - [Класс `MySQLDatabase`](#%D0%9A%D0%BB%D0%B0%D1%81%D1%81-mysqldatabase)
        - [Примеры](#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B)
    - [Класс `SQLiteDatabase`](#%D0%9A%D0%BB%D0%B0%D1%81%D1%81-sqlitedatabase)
        - [Примеры](#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-1)
- [Автор](#%D0%90%D0%B2%D1%82%D0%BE%D1%80)
- [Лицензия](#%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F)

<!-- /MarkdownTOC -->

<a id="%D0%A2%D1%80%D0%B5%D0%B1%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F"></a>
## Требования

- PHP >= 7.0.
- Произвольный автозагрузчик классов, реализующий стандарт [PSR-4](https://www.php-fig.org/psr/psr-4/).

<a id="%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0"></a>
## Установка

Установка через composer:
```
$ composer require andrey-tech/database-php:"^2.0"
```

или добавить

```
"andrey-tech/database-php": "^2.0"
```

в секцию require файла composer.json.

<a id="%D0%9A%D0%BB%D0%B0%D1%81%D1%81-database"></a>
## Класс `Database`

Класс `\App\Database\Database` является абстрактным базовым классом для работы с СУБД.  
При возникновении ошибок в классах пространства имен `\App\Database` выбрасывается исключение с объектом класса `\App\Database\DatabaseException`.  

Класс `\App\Database\Database` содержит следующие общие публичные методы:

- `static instance(array $config = [], array $options = []) :Database` Возвращает единственный объект класса.
    + `$config` - конфигурация соединения с СУБД;
    + `$options` - опции подключения для драйвера СУБД.
- `connect() :void` Выполняет подключение к серверу СУБД. В обычных условиях не требуется, так как подключение к серверу СУБД выполняется автоматически при первом запросе.
- `disconnect() :void` Выполняет отключение от сервера СУБД. В обычных условиях не требуется, так как отключение от сервера СУБД выполняется автоматически при уничтожении объекта класса.
- doStatement($statement, array $values = [], bool $isNamed = true, array $prepareOptions = []): \PDOStatement  
    Подготавливает запрос, кэширует подготовленный запрос и запускает подготовленный запрос на выполнение.  
    Возвращает объект класса \PDOStatement.
    + `$statement` - SQL оператор;
    + `$values` - массив значений для SQL оператора;
    + `$isNamed` - флаг именованных параметров (:name) в SQL операторе;
    + `$prepareOptions` - опции драйвера СУБД для подготовки запроса.
- `beginTransaction()` Инициализирует транзакцию.
- `commitTransaction()` Фиксирует транзакцию.
- `rollbackTransaction()` Откатывает транзакцию.
- `fetchAll(\PDOStatement $stmt): \Generator` Позволяет выбирать все записи с помощью генератора.  
    + `$stmt` - объект класса \PDOStatement.
- `getLastInsertId(string $idName = null) :string` Возвращает значение "id" последней вставленной записи.
    + `$idName` - имя столбца "id".
- `createInStatement(array $in = []): string` Возвращает строку для выражения IN (?, ?, ?,...).
    + `$in` - массив значений внутри выражения IN(?, ?, ?,...).
- `getPdo() :\PDO` Возвращает объект класса \PDO.

Дополнительные параметры доступны через публичные свойства объекта класса:

Свойство                | По умолчанию       | Описание
----------------------- | ------------------ | --------
`$debug`                | false              | Включает отладочный режим с выводом в STDOUT всех выполняемых операций


<a id="%D0%9A%D0%BB%D0%B0%D1%81%D1%81-mysqldatabase"></a>
### Класс `MySQLDatabase`

Класс `\App\Database\MySQLDatabase` расширяет класс `\App\Database\Database` и предназначен для работы с СУБД MySQL.

По умолчанию установлены следующие параметры конфигурации и опции подключения:
```php
$config = [
    'driver'   => 'mysql',     // Имя драйвера PDO
    'host'     => 'localhost', // Имя или IP хоста
    'port'     => 3306,        // Порт
    'database' => 'db',        // Имя БД
    'charset'  => 'utf8mb4',   // Кодировка
    'user'     => '',          // Имя пользователя
    'password' => ''           // Пароль пользователя
];

$options = [
     PDO::ATTR_TIMEOUT            => 60,
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];    
```

<a id="%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B"></a>
#### Примеры

Пример работы непосредственно с классом `MySQLDatabase`:

```php
use App\Database\{MySQLDatabase, DatabaseException};

try {

    $config = [
        'host'     => 'localhost',
        'user'     => 'test',
        'password' => 'pass',
        'database' => 'mydb'
    ];

    $db = MySQLDatabase::instance($config);

    // Включаем отладочный режим с выводом в STDOUT
    $db->debug = true;

    // Запрос к таблице contacts без параметров
    $stmt = $db->doStatement("
        SELECT COUNT(*) AS `count` 
        FROM `contacts`
    ");
    print_r($stmt->fetchAll());

    // Запрос к таблице contacts с использованием именованных параметров
    $stmt = $db->doStatement("
        SELECT * 
        FROM `contacts`
        WHERE `status` = :status
        LIMIT 10
    ", [ 'status' => 1 ]);
    print_r($stmt->fetchAll());

    // Запрос к таблице contacts с использованием НЕ именованных параметров
    $stmt = $db->doStatement("
        SELECT * 
        FROM `contacts`
        WHERE `status` = ?
    ", [ 1 ], $isNamed = false);

    // Выбираем все записи с помощью генератора
    $generator = $db->fetchAll($stmt);
    foreach ($generator as $row) {
        print_r($row);
    }

} catch (DatabaseException $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}
```

Пример вывода отладочной информации в STDOUT:

```
***** CONNECT localhost
***** [1]  SELECT COUNT(*) AS `count` FROM `contacts` 
***** [2]  SELECT * FROM `contacts` WHERE `status` = 1 LIMIT 10 
***** [3]  SELECT * FROM `contacts` WHERE `status` = 1 
***** DISCONNECT localhost
```

Пример с созданием дочернего класса:

```php
use App\Database\{MySQLDatabase, DatabaseException};

class MyDatabase extends MySQLDatabase
{
    /**
     * Выполняет соединение с CУБД
     * @return void
     */
    public function connect()
    {
        parent::connect();
        $this->tune();
    }

    /**
     * Выполняет дополнительную настройку после соединения с СУБД
     * @return void
     */
    public function tune()
    {
        // Устанавливаем таймаут неактивности соединения с СУБД
        $this->doStatement("SET SESSION wait_timeout = {$this->config['wait_timeout']}");
        // Устанавливаем уровень изолированности транзакций
        $this->doStatement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    /**
     * Извлекает клиентов с заданным статусом
     * @param int $status ID статуса
     * @return Generator
     */
    public function selectContactsByStatus(int $status) :Generator
    {
        $stmt = $this->doStatement("
            SELECT * 
            FROM `contacts`
            WHERE `status` = :status
            FOR UPDATE
        ", [ 'status' => $status ]);

        return $this->fetchAll($stmt);
    }
}

try {

    $config = [
        'host'     => 'localhost',
        'user'     => 'test',
        'password' => 'pass',
        'database' => 'mydb'
    ];

    $db = MyDatabase::instance($config);

    // Включаем отладочный режим с выводом в STDOUT
    $db->debug = true;

    // Выбираем все записи с помощью генератора
    $generator = $db->selectContactsByStatus($status = 1);
    foreach ($generator as $row) {
        print_r($row);
    }

} catch (DatabaseException $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}
```

<a id="%D0%9A%D0%BB%D0%B0%D1%81%D1%81-sqlitedatabase"></a>
### Класс `SQLiteDatabase`

Класс `\App\Database\SQliteDatabase` расширяет класс `\App\Database\Database` и предназначен для работы с СУБД SQLite.

По умолчанию установлены следующие параметры конфигурации и опции подключения:
```php
$config = [
    'driver'   => 'sqlite',         // Имя драйвера PDO
    'database' => 'database.sqlite' // Имя файла SQLite
];

$options = [
     PDO::ATTR_TIMEOUT            => 60,
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];    
```

<a id="%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-1"></a>
#### Примеры

Пример работы непосредственно с классом `SQLiteDatabase`:

```php
use App\Database\{SQLiteDatabase, DatabaseException};

try {

    $config = [
        'database' => 'db.sqlite',
    ];

    $db = SQLiteDatabase::instance($config);
    
    // Включаем отладочный режим с выводом в STDOUT
    $db->debug = true;

    // Запрос к таблице contacts без параметров
    $stmt = $db->doStatement("
        SELECT COUNT(*) AS `count`
        FROM `contacts`
    ");
    print_r($stmt->fetchAll());

} catch (DatabaseException $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}
```

<a id="%D0%90%D0%B2%D1%82%D0%BE%D1%80"></a>
## Автор

© 2019-2020 andrey-tech

<a id="%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F"></a>
## Лицензия

Данная библиотека распространяется на условиях лицензии [MIT](./LICENSE).
