<?php
declare(strict_types=1);


namespace flannan\YABS;

use InvalidArgumentException;
use flannan\YABS\Database;

/**
 * Class Customer
 *
 * @package flannan\YABS
 */
class Customer
{
    protected $customerId;
    private $database;
    protected $gender; //M/F/O
    protected $birthday = [0, 0, 0];
    protected $phone;
    protected $balance;
    protected $discount;
    //private $max_card_id = 100000;


    /**
     * Customer constructor.
     *
     * @param array                  $customerData
     *
     * @param string                 $action
     * @param \flannan\YABS\Database $database
     */
    public function __construct(array $customerData, string $action, Database $database)
    {
        //include_once __DIR__ . '/Database.php';
        $this->database = $database;

        if (array_key_exists('customer_id', $customerData) === true) {
            $this->customerId = $customerData['customer_id'];
        } elseif (array_key_exists('phone', $customerData) === true) {
            $this->phone = $customerData['phone'];
            $this->customerId = $this->getCardByPhone();
        } else {
            throw new InvalidArgumentException('Not enough data to identify customer');
        }

        $customerExists = $this->checkCustomerExists();
        if ($action === 'createAction') {
            if ($customerExists) {
                throw new InvalidArgumentException('User already exists');
            }

            $this->gender = $customerData['gender'];
            $this->birthday[0] = $customerData['birthDay'];
            $this->birthday[1] = $customerData['birthMonth'];
            if (array_key_exists('birthYear', $customerData) === true) {
                $this->birthday[2] = $customerData['birthYear'];
            } else {
                $this->birthday[2] = null;
            }
            $this->writeCustomerToDatabase();
        } else {
            if ($customerExists === false) {
                throw new InvalidArgumentException('User not found');
            }
            $this->retrieveBonuses();
        }
    }

    /** Находит в базе данных телефон и возвращает номер карты.
     *
     * @param int $phone
     *
     * @return int
     */
    private function getCardByPhone(): int
    {
        //stub
        return $this->phone;
    }

    /**
     * @return bool
     */
    private function checkCustomerExists(): bool
    {
        //stub
        return true;
    }

    private function writeCustomerToDatabase(): void
    {
        //stub
    }

    /** Выдаёт данные для передачи в клиентскую систему.
     *
     * @return array
     */
    public function prepareExportArray(): array
    {
        $exportArray = [
            'customer_id' => $this->customerId,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birthDay' => $this->birthday[0],
            'birthMonth' => $this->birthday[1],
        ];
        if ($this->birthday[2] !== null) {
            $exportArray['birthYear'] = $this->birthday[2];
        }
        if (isset($this->balance)) {
            $exportArray['balance'] = $this->balance;
        }
        if (isset($this->discount)) {
            $exportArray['discount'] = $this->discount;
        }
        return $exportArray;
    }

    public function retrieveBonuses(): void
    {
        //stub
        $this->balance = 100;
        $this->discount = 0;
    }

    /**
     * @param $change
     */
    public function changeBonuses($change): void
    {
        //stub
        $this->balance += $change;

    }

    /**
     * @param $newDiscount
     */
    public function setDiscount($newDiscount): void
    {
        //stub
        $this->discount = $newDiscount;

    }
}