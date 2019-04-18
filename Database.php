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

    public function __construct()
    {
        $filename = 'database.json';
        if (is_file($filename) === true) {
            $json = file_get_contents($filename);
            $settings = json_decode($json, true);
            $this->login=$settings('login');
            $this->password=$settings('password');
            $this->databaseName=$settings('databaseName');
            $this->host=$settings('host');
            $this->port=$settings('port');
        }
    }

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
