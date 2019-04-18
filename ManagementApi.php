<?php
declare(strict_types=1);


namespace flannan\YABS;

use RuntimeException;

/**
 * Class ManagementApi
 *
 * @package flannan\YABS
 */
class ManagementApi extends Api
{

    /**
     * ManagementApi constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiName = 'management';
    }


    /**
     * @return false|string
     */
    protected function indexAction()
    {
        return $this->response('API not implemented', 405);
    }


    /** возвращает общую статистику по организации.
     *
     * @return false|string
     */
    protected function viewAction()
    {
        $database = new Database();
        $user = new User($database);
        $user->requireManager();
        $sqlQuery = <<<SQL
SELECT count(cards.id) as numberOfCards, sum(all cards.balance) as totalBonuses
FROM cards
SQL;
        $result = mysqli_query($database->getConnection(), $sqlQuery);
        $response = mysqli_fetch_array($result, MYSQLI_ASSOC);

        //сумма списанных бонусов
        if (isset($this->requestParams) || array_key_exists('time_start', $this->requestParams) === false) {
            $this->requestParams['time_start'] = 0;
        }
        if (array_key_exists('time_end', $this->requestParams) === false) {
            $this->requestParams['time_end'] = time();
        }
        $timeStart = date('Y-m-d H:i:s', $this->requestParams['time_start']);
        $timeEnd = date('Y-m-d H:i:s', $this->requestParams['time_end']);
        $sqlQuery = <<<SQL
SELECT -sum(all operations.value) as totalBonusesUsed
FROM operations
WHERE type='bonuses' 
  AND operations.value<0
  AND time BETWEEN '$timeStart' AND '$timeEnd';
SQL;

        $result = mysqli_query($database->getConnection(), $sqlQuery);
        if ($result===false) {
            throw new RuntimeException('request failed');
        }
        $result = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $response['totalBonusesUsed'] = $result['totalBonusesUsed'];
        return $this->response($response, 200);
    }

    /**
     * @return false|string
     */
    protected function createAction()
    {

        return $this->response('API not implemented', 405);
    }

    /**
     * @return false|string
     */
    protected function updateAction()
    {
        return $this->response('API not implemented', 405);
    }

    /**
     * @return false|string
     */
    protected function deleteAction()
    {
        return $this->response('API not implemented', 405);
    }
}