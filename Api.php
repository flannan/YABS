<?php
declare(strict_types=1);

namespace flannan\YABS;

use RuntimeException;

/** Базовый класс Api
 * Class Api
 */
abstract class Api
{
    public $apiName = ''; //users

    protected $method = ''; //GET|POST|PUT|DELETE

    public $requestUri = [];
    public $requestParams = [];

    protected $action = ''; //Название метод для выполнения


    /**
     * Api constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        header('Access-Control-Allow-Orgin: *');
        header('Access-Control-Allow-Methods: *');
        header('Content-Type: application/json');

        //Массив GET параметров разделенных слешем
        $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $this->requestParams = json_decode(file_get_contents('php://input'), true);

        //Определение метода запроса
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] === 'DELETE') {
                $this->method = 'DELETE';
            } elseif ($_SERVER['HTTP_X_HTTP_METHOD'] === 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new RuntimeException('Unexpected Header');
            }
        }
    }

    /**
     * @return mixed
     */
    public function run()
    {
        //Первые 2 элемента массива URI должны быть "api" и название таблицы
        if ($this->requestUri[0] !== 'api' || $this->requestUri[1] !== $this->apiName) {
            throw new RuntimeException('API Not Found', 404);
        }
        //Определение действия для обработки
        $this->action = $this->getAction();

        //Если метод(действие) определен в дочернем классе API
        if (method_exists($this, $this->action) === false) {
            throw new RuntimeException('Invalid Method', 405);
        }
        return $this->{$this->action}();
    }

    /**
     * @param     $data
     * @param int $status
     *
     * @return false|string
     */

    protected function response($data, $status = 500)
    {
        header('HTTP/1.1 ' . $status . ' ' . $this->requestStatus($status));
        return json_encode($data);
    }

    /**
     * @param $code
     *
     * @return mixed
     */
    private function requestStatus($code)
    {
        $status = [
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        ];
        return $status[$code] ?: $status[500];
    }

    /**
     * @return string|null
     */
    protected function getAction(): ?string
    {
        $method = $this->method;
        switch ($method) {
            case 'GET':
                if ($this->requestUri) {
                    $action = 'viewAction';
                } else {
                    $action = 'indexAction';
                }
                break;
            case 'POST':
                $action = 'createAction';
                break;
            case 'PUT':
                $action = 'updateAction';
                break;
            case 'DELETE':
                $action = 'deleteAction';
                break;
            default:
                $action = null;
        }
        return $action;
    }

    abstract protected function indexAction();

    abstract protected function viewAction();

    abstract protected function createAction();

    abstract protected function updateAction();

    abstract protected function deleteAction();
}
