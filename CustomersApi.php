<?php
declare(strict_types=1);

namespace flannan\YABS;

use RuntimeException;

/**
 * Class customersApi
 *
 * @package flannan\YABS
 */
class CustomersApi extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->apiName = 'customers';
    }

    /**
     * @return false|string
     */
    protected function indexAction()
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
        if (empty($this->requestParams) || array_key_exists('time_start', $this->requestParams) === false) {
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

    /** выдаёт информацию о покупателе
     *
     * @return string
     */
    protected function viewAction(): string
    {
        $database = new Database();
        $user = new User($database);
        if ((isset($this->requestParams) === false) && (isset($this->requestUri[2]) === true)) {
            $this->parseUriId();
        }
        $customer = new Customer($this->requestParams, $this->action, $database, $user);
        if ($this->requestParams['statement']) {
            $response=$customer->getStatement();
            $status = 200;
        } else {
            $customer->retrieveBonuses();
            $response = $customer->prepareExportArray();
            $status = 200;
        }

        return $this->response($response, $status);
    }

    /** Добавляет нового покупателя в базу данных
     *
     * @return false|string
     */
    protected function createAction()
    {
        $database = new Database();
        $user = new User($database);
        $customer = new Customer($this->requestParams, $this->action, $database, $user);
        $rules = new Rules($database, $user);
        $rules->initialize($customer);
        return $this->response('customer added successfully', 200);
    }

    /**
     * @return false|string
     */
    protected function updateAction()
    {
        $database = new Database();
        $user = new User($database);
        $customer = new Customer($this->requestParams, $this->action, $database, $user);
        if (array_key_exists('changeBonus', $this->requestParams)) {
            $customer->changeBonuses($this->requestParams['changeBonus']);
        }
        if (array_key_exists('newDiscount', $this->requestParams)) {
            $customer->setDiscount($this->requestParams['newDiscount']);
        }
        if (array_key_exists('newStatus', $this->requestParams)) {
            $customer->setStatus($this->requestParams['newStatus']);
        }
        if (array_key_exists('receipt', $this->requestParams)) {
            $rules = new Rules($database, $user);
            $customer->addTurnover((float)$this->requestParams['receipt']);
            $rules->apply($customer, (float)$this->requestParams['receipt']);
        }

        return $this->response('change successful', 200);
    }

    /**
     * @return false|string
     */
    protected function deleteAction()
    {
        return $this->response('API not implemented', 405);
    }

    private function parseUriId(): void
    {
        if ((int)$this->requestUri[2] > 100000) {
            $this->requestParams['phone'] = $this->requestUri[2];
        } else {
            $this->requestParams['customer_id'] = $this->requestUri[2];
        }
    }
}
