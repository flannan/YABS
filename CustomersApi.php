<?php
declare(strict_types=1);

namespace flannan\YABS;

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
        return $this->response('API not implemented', 405);
    }

    /** выдаёт информацию о покупателе
     *
     * @return string
     */
    protected function viewAction(): string
    {
        $database = new Database();
        $user = new User($database);
        if (isset($this->requestParams)) {
            $customer = new Customer($this->requestParams, $this->action, $database, $user);
            $customer->retrieveBonuses();
            $response = $customer->prepareExportArray();
            $status = 200;
        } else {
            $response = 'No instructions found';
            $status = 404;
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
            $customer->addTurnover((float) $this->requestParams['receipt']);
            $rules->apply($customer, (float) $this->requestParams['receipt']);
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
}
