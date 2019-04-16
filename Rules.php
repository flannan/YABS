<?php
declare(strict_types=1);


namespace flannan\YABS;

/**
 * Class Rules
 *
 * @package flannan\YABS
 */
class Rules
{
    protected $database;
    protected $apply = true;
    protected $bonuses = true;
    protected $rules;

    /**
     * Rules constructor.
     *
     * @param \flannan\YABS\Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;

        $sqlQuery = <<<SQL
SELECT bonuses,apply_rules
FROM settings
WHERE id=1
LIMIT 1;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $result = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $this->apply = (bool)$result['apply_rules'];
        $this->bonuses = (bool)$result['bonuses'];

        $sqlQuery = <<<SQL
SELECT *
FROM rules;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $this->rules = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /**
     * @param \flannan\YABS\Customer $customer
     */
    public function initialize(Customer $customer): void
    {
        $customer->setDiscount(0);
        $customer->setBonuses(0);
    }

    /**
     * @param \flannan\YABS\Customer $customer
     * @param float                  $receipt
     */
    public function apply(Customer $customer, float $receipt): void
    {
        if ($this->apply === true) {
            $change = $receipt * 0.05;
            $customer->changeBonuses($change);
        }
    }
}