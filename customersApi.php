<?php
declare(strict_types=1);

namespace flannan\YABS;

use flannan\YABS\Api;
use flannan\YABS\Customer;

/**
 * Class customersApi
 *
 * @package flannan\YABS
 */
class customersApi extends Api
{

    protected function indexAction()
    {
        // TODO: Implement indexAction() method.
    }

    /** выдаёт информацию о покупателе
     *
     * @return string
     */
    protected function viewAction(): string
    {
        $database = new Database();
        $customer = new Customer($this->requestParams, $this->action, $database);
        return $this->response($customer->prepareExportArray(), 200);
    }

    /** Добавляет нового покупателя в базу данных
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
        if (array_key_exists('changeBonus',$this->requestParams)) {
            $customer->changeBonuses($this->requestParams['changeBonus']);
        }
        if (array_key_exists('newDiscount',$this->requestParams)) {
            $customer->setDiscount($this->requestParams['newDiscount']);
        }

        return $this->response('change successful', 200);
    }

    protected function deleteAction()
    {
        // TODO: Implement deleteAction() method.
    }
}