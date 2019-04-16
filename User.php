<?php
declare(strict_types=1);


namespace flannan\YABS;

use RuntimeException;

/** Пользователь системы (то есть кассир или менеджер)
 * Class User
 *
 * @package flannan\YABS
 */
class User
{
    private $userId;
    private $manager;

    /**
     * User constructor.
     *
     * @param \flannan\YABS\Database $database
     */
    public function __construct(Database $database)
    {
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            throw new RuntimeException('Authentication required', 401);
        }
        $this->userId = $_SERVER['PHP_AUTH_USER'];

        $sqlQuery = <<<SQL
SELECT password, is_manager
FROM users
WHERE name='{$this->userId}'
LIMIT 1;
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        $result = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($result['password'] !== $_SERVER['PHP_AUTH_PW']) {
            throw new RuntimeException('Authentication failed', 401);
        }
        $this->manager = (bool) $result['is_manager'];
    }

    /**
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->manager;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function requireManager(): void
    {
        if ($this->isManager() === false) {
            throw new RuntimeException('Access denied. Manager level necessary.');
        }
    }
}
