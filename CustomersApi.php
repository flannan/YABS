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
    public $apiName = 'customers';

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
        if (isset($this->requestParams)) {
            $customer = new Customer($this->requestParams, $this->action, $database);
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
        new Customer($this->requestParams, $this->action, $database);
        return $this->response('customer added successfully', 200);
    }

    /**
     * @return false|string
     */
    protected function updateAction()
    {
        $database = new Database();
        $customer = new Customer($this->requestParams, $this->action, $database);
        if (array_key_exists('changeBonus', $this->requestParams)) {
            $customer->changeBonuses($this->requestParams['changeBonus']);
        }
        if (array_key_exists('newDiscount', $this->requestParams)) {
            $customer->setDiscount($this->requestParams['newDiscount']);
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
