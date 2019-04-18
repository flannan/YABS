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

    /**
     * @return false|string
     */
    protected function viewAction()
    {
        return $this->indexAction();
    }

    /**
     * @return false|string
     */
    protected function createAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        $user->requireManager();
        if (array_key_exists('timestamp', $this->requestParams)) {
            $rules->addHoliday((int) $this->requestParams['timestamp'], $this->requestParams['name']);
        } elseif (array_key_exists('date', $this->requestParams)) {
            $timestamp=strtotime($this->requestParams['date']);
            if ($timestamp === false) {
                throw new RuntimeException('holiday date parsing failed');
            }
            $rules->addHoliday($timestamp, $this->requestParams['name']);
        } else {
            throw new RuntimeException('holiday date not found');
        }

        return $this->response('holiday added successfully', 200);
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
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        $user->requireManager();
        if (array_key_exists('timestamp', $this->requestParams)) {
            $rules->removeHoliday((int) $this->requestParams['timestamp']);
        } elseif (array_key_exists('date', $this->requestParams)) {
            $timestamp=strtotime($this->requestParams['date']);
            if ($timestamp === false) {
                throw new RuntimeException('holiday date parsing failed');
            }
            $rules->removeHoliday($timestamp);
        } else {
            throw new RuntimeException('holiday date not found');
        }

        return $this->response('holiday deleted successfully', 200);
    }
}