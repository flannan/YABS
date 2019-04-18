<?php
declare(strict_types=1);


namespace flannan\YABS;

use RuntimeException;

/**
 * Class HolidaysApi
 *
 * @package flannan\YABS
 */
class HolidaysApi extends Api
{

    /**
     * HolidaysApi constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiName = 'holidays';
    }

    /**
     * @return false|string
     */
    protected function indexAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        $response = $rules->getHolidays();
        return $this->response($response, 200);
    }

    protected function viewAction()
    {
        // TODO: Implement viewAction() method.
    }

    /**
     * @return false|string
     */
    protected function createAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        if (array_key_exists('timestamp', $this->requestParams)) {
            $rules->addHoliday($this->requestParams['timestamp'], $this->requestParams['name']);
        } elseif (array_key_exists('date', $this->requestParams)) {
            $rules->addHoliday(strtotime($this->requestParams['date']), $this->requestParams['name']);
        } else {
            throw new \RuntimeException('holiday date not found');
        }

        return $this->response('holiday added successfully', 200);
    }

    protected function updateAction()
    {
        // TODO: Implement updateAction() method.
    }

    protected function deleteAction()
    {
        // TODO: Implement deleteAction() method.
    }
}