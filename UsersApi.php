<?php
declare(strict_types=1);


namespace flannan\YABS;

/**
 * Class UsersApi
 *
 * @package flannan\YABS
 */
class UsersApi extends Api
{

    /**
     * UsersApi constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiName = 'users';
    }

    /** Возвращает список пользователей.
     * @return false|string
     */
    protected function indexAction()
    {
        $database = new Database();
        $user = new User($database);
        $user->requireManager();
        $sqlQuery = <<<SQL
SELECT name, is_manager
FROM users;
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        $response = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $this->response($response, 200);
    }

    /**
     * @return false|string
     */
    protected function viewAction()
    {
        return $this->response('API not implemented', 405);
    }

    /**
     * @return false|string
     */
    protected function createAction()
    {
        $database = new Database();
        $user = new User($database);
        $user->requireManager();
        $sqlQuery = <<<SQL
INSERT INTO users (name, password, is_manager)
VALUES ('{$this->requestParams['name']}','{$this->requestParams['password']}',{$this->requestParams['is_manager']})
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        if ($result === true) {
            $response='user created successfully';
            $status=200;
        } else {
            $response='user creation failed';
            $status=500;
        }
        return $this->response($response, $status);
    }

    /**
     * @return false|string
     */
    protected function updateAction()
    {
        $database = new Database();
        $user = new User($database);
        $user->requireManager();
        $sqlQuery = <<<SQL
REPLACE INTO users (name, password, is_manager)
VALUES ('{$this->requestParams['name']}','{$this->requestParams['password']}',{$this->requestParams['is_manager']})
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        if ($result === true) {
            $response='user modified successfully';
            $status=200;
        } else {
            $response='user modification failed';
            $status=500;
        }
        return $this->response($response, $status);
    }

    /** Удаляет пользователя из системы.
     * @return false|string
     */
    protected function deleteAction()
    {
        $database = new Database();
        $user = new User($database);
        $user->requireManager();
        $sqlQuery = <<<SQL
DELETE FROM users
WHERE name='{$this->requestParams['name']}'
LIMIT 1;
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        if ($result === true) {
            $response='user deleted successfully';
            $status=200;
        } else {
            $response='user deletion failed';
            $status=500;
        }
        return $this->response($response, $status);
    }
}
