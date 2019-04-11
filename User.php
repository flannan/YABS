<?php
declare(strict_types=1);


namespace flannan\YABS;

use flannan\YABS\Database;

/** Пользователь системы (то есть кассир или менеджер)
 * Class User
 *
 * @package flannan\YABS
 */
class User
{
    private $userId;
    private $manager = false;
    private $database;

    /**
     * User constructor.
     *
     * @param string                 $userId
     * @param \flannan\YABS\Database $database
     */
    public function __construct(string $userId, Database $database)
    {
        $this->userId = $userId;
        $this->database = $database;
    }

    /** Проверяет соответствие идентификатора пользователя и хэша.
     *
     * @param $hash
     *
     * @return bool
     */
    public function authenticate($hash): bool
    {
        //stub
        $this->manager = true;
        return true;
    }


    /**
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->manager;
    }
}
