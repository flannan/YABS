<?php
declare(strict_types=1);


namespace flannan\YABS;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class Customer
 *
 * @package flannan\YABS
 */
class Customer
{
    protected $name;
    protected $customerId;
    private $database;
    private $user;
    protected $gender; //M/F/O
    protected $birthday = [0, 0, 0];
    protected $phone;
    protected $balance;
    protected $discount;
    protected $turnover;

    /**
     * Customer constructor.
     *
     * @param array                  $customerData
     *
     * @param string                 $action
     * @param \flannan\YABS\Database $database
     * @param \flannan\YABS\User     $user
     */
    public function __construct(array $customerData, string $action, Database $database, User $user)
    {

        $this->database = $database;
        $this->user = $user;

        if (array_key_exists('customer_id', $customerData) === true) {
            $this->customerId = (string)$customerData['customer_id'];
        } elseif (array_key_exists('phone', $customerData) === true) {
            $this->phone = (string)$customerData['phone'];
            $this->customerId = (string)$this->getCardByPhone();
        } else {
            throw new InvalidArgumentException('Not enough data to identify customer');
        }

        $customerExists = $this->checkCustomerExists();
        if ($action === 'createAction') {
            if ($customerExists) {
                throw new InvalidArgumentException('User already exists');
            }
            $this->name = $customerData['name'];
            $this->gender = $customerData['gender'];
            $this->phone = $customerData['phone'];
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
     * @return int
     */
    private function getCardByPhone(): int
    {
        $sqlQuery = <<<SQL
SELECT card_id
FROM customers
WHERE phone=$this->phone
LIMIT 1;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $card = mysqli_fetch_array($result);
        $card = (int)$card['card_id'];
        return $card;
    }

    /** Проверяет существование покупателя в базе.
     *
     * @return bool
     */
    private function checkCustomerExists(): bool
    {
        $sqlQuery = <<<SQL
SELECT card_id
FROM customers
WHERE card_id=$this->customerId
LIMIT 1;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        return count(mysqli_fetch_all($result)) > 0;
    }

    /** Добавляет покупателя в базу данных.
     *
     */
    private function writeCustomerToDatabase(): void
    {
        $this->user->log(
            'new customer',
            'Покупатель ' . $this->name . ' добавляется в базу',
            $this->customerId
        );
        $sqlQuery = <<<SQL
INSERT INTO cards(id,status)
VALUES ($this->customerId,'Active');
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Card adding operation failed');
        }

        if ($this->birthday[2] === null) {
            $year = 'null';
        } else {
            $year = $this->birthday[2];
        }

        $sqlQuery = <<<SQL
INSERT INTO customers(card_id,name,phone,gender,birthDay,birthMonth,birthYear)
VALUES ($this->customerId,'$this->name',$this->phone,'$this->gender',
        {$this->birthday[0]},{$this->birthday[1]},$year);
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);

        if ($result === false) {
            $this->user->log(
                'error',
                'Попытка добавить покупателя ' . $this->name . ' в базу не удалась',
                $this->customerId
            );
            $sqlQuery = <<<SQL
DELETE FROM cards WHERE id={$this->customerId};
SQL;
            mysqli_query($this->database->getConnection(), $sqlQuery);
            throw new RuntimeException('User adding operation failed');
        }
        $this->user->log(
            'new customer',
            'Покупатель ' . $this->name . ' добавлен успешно',
            $this->customerId
        );
    }

    /** Выдаёт данные для передачи в клиентскую систему.
     *
     * @return array
     */
    public function prepareExportArray(): array
    {
        $this->retrieveCustomerData();
        $this->retrieveBonuses();
        $exportArray = [
            'name' => $this->name,
            'customer_id' => $this->customerId,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birthDay' => $this->birthday[0],
            'birthMonth' => $this->birthday[1],
        ];
        //      if ($this->birthday[2] !== null) {
        $exportArray['birthYear'] = $this->birthday[2];
//        }
        $exportArray['balance'] = $this->balance;
        $exportArray['discount'] = $this->discount;
        $exportArray['turnover'] = $this->turnover;

        return $exportArray;
    }

    public function retrieveBonuses(): void
    {
        $sqlQuery = <<<SQL
SELECT balance, discount, turnover
FROM cards
WHERE id=$this->customerId
LIMIT 1;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $result = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $this->balance = (float)$result['balance'];
        $this->discount = (float)$result['discount'];
        $this->turnover = (float)$result['turnover'];
    }

    /**
     * @param $change
     */
    public function changeBonuses($change): void
    {
        $this->retrieveBonuses();
        $newBalance = $this->balance + $change;
        if ($newBalance > 0) {
            $this->user->log(
                'bonuses',
                'Изменение баланса бонусов',
                $this->customerId,
                $change
            );
            $this->setBonuses($newBalance);
        } else {
            throw new RuntimeException('Not enough bonuses');
        }
    }

    /**
     * @param float $newBalance
     */
    public function setBonuses(float $newBalance): void
    {
        $sqlQuery = <<<SQL
UPDATE cards
SET balance=$newBalance
WHERE id=$this->customerId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Balance update operation failed');
        }
        $this->balance = $newBalance;
        $this->user->log(
            'bonuses state',
            'Новый баланс бонусов',
            $this->customerId,
            $newBalance
        );
    }

    /**
     * @param $newDiscount
     */
    public function setDiscount($newDiscount): void
    {
        $sqlQuery = <<<SQL
UPDATE cards
SET discount=$newDiscount
WHERE id=$this->customerId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Discount change operation failed');
        }
        $this->discount = $newDiscount;
        $this->user->log(
            'discount change',
            'новое значение скидки',
            $this->customerId,
            $newDiscount
        );
    }

    protected function retrieveCustomerData(): void
    {
        $sqlQuery = <<<SQL
SELECT name,gender,phone,birthDay,birthMonth,birthYear
FROM customers
WHERE card_id=$this->customerId
LIMIT 1;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $result = mysqli_fetch_array($result);
        $this->name = $result[0];
        $this->gender = $result[1];
        $this->phone = $result[2];
        $this->birthday[0] = (int)$result[3];
        $this->birthday[1] = (int)$result[4];
        if ($result[5] === null) {
            $this->birthday[2] = null;
        } else {
            $this->birthday[2] = (int)$result[5];
        }
    }

    /**
     * @param $newStatus
     */
    public function setStatus($newStatus): void
    {
        $sqlQuery = <<<SQL
UPDATE cards
SET status=$newStatus
WHERE id=$this->customerId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Status change operation failed');
        }
        $this->user->log(
            'status change',
            'Новый статус карты - ' . $newStatus,
            $this->customerId
        );
    }

    /**
     * @return bool
     */
    public function isBirthday(): bool
    {
        $date = getdate();
        return (($this->birthday[0] === $date['mday']) && ($this->birthday[1] === $date['mon']));
    }

    public function getDiscount()
    {
        if (isset($this->discount) === false) {
            $this->retrieveBonuses();
        }
        return $this->discount;
    }

    public function getTurnover()
    {
        if (isset($this->turnover) === false) {
            $this->retrieveBonuses();
        }
        return $this->turnover;
    }

    /**
     * @param $receipt
     */
    public function addTurnover(float $receipt): void
    {
        if (isset($this->turnover) === false) {
            $this->retrieveBonuses();
        }
        $newTurnover = $this->turnover + $receipt;

        $sqlQuery = <<<SQL
UPDATE cards
SET turnover=$newTurnover
WHERE id=$this->customerId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Turnover update operation failed');
        }
        $this->turnover = $newTurnover;
        $this->user->log(
            'turnover',
            'Зарегистрирован чек на сумму',
            $this->customerId,
            $receipt
        );
    }

    /**
     * @return array|null
     */
    public function getStatement(): ?array
    {
        $sqlQuery = <<<SQL
SELECT -sum(all operations.value) as totalBonusesUsed
FROM operations
WHERE customer_id=$this->customerId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('request failed');
        }
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
