<?php
declare(strict_types=1);


namespace flannan\YABS;

use mysqli;

/** Класс для связи с базой данных
 * Class Database
 *
 * @package flannan\YABS
 */
class Database
{
    private $login = 'stud08';
    private $password = 'stud08';
    private $databaseName = 'YABS';
    private $host = 'localhost';
    private $port = 3306;

    /** Выдаёт ссылку для работы с базой данных.
     *
     * @return \mysqli
     */
    public function getConnection(): mysqli
    {
        $mysqli = new mysqli($this->host, $this->login, $this->password, $this->databaseName, $this->port);
        $mysqli->set_charset('utf8');
        return $mysqli;
    }
}
