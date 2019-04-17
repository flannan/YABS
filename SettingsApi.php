<?php
declare(strict_types=1);


namespace flannan\YABS;

/**
 * Class SettingsApi
 *
 * @package flannan\YABS
 */
class SettingsApi extends Api
{

    /**
     * SettingsApi constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiName = 'settings';
    }

    /** Выдаёт список праздников
     *
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

    /**Выдаёт настройки и список правил
     *
     * @return false|string
     */
    protected function viewAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        $response = [
            'apply' => $rules->isApplicable(),
            'bonuses' => $rules->isBasedOnBonuses(),
            'rules' => $rules->getRules(),
        ];
        return $this->response($response, 200);
    }

    /**добавляет или изменяет правила.
     *
     * @return false|string
     */
    protected function createAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        if (array_key_exists('id', $this->requestParams)) {
            $rules->replaceRule($this->requestParams);
            $response = 'rule addded/replaced successfully';
        } else {
            $rules->addRule($this->requestParams);
            $response = 'rule added successfully';
        }
        return $this->response($response, 200);
    }

    /**изменяет настройки
     *
     * @return false|string
     */
    protected function updateAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);
        $rules->setApplicable($this->requestParams['applicable']);
        $rules->setBasedOnBonuses($this->requestParams['basedOnBonuses']);
        return $this->response('settings changed successfully', 200);
    }

    /**Удаляет правила
     * @return false|string
     */
    protected function deleteAction()
    {
        $database = new Database();
        $user = new User($database);
        $rules = new Rules($database, $user);

        $rules->removeRule($this->requestParams['id']);
        $response = 'rule removed successfully';

        return $this->response($response, 200);

    }
}