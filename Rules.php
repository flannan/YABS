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
            $finalRule = [
                'multiplier' => 1,
                'add' => 0,
                'percentage' => 0,
                'discount' => 0,
            ];
            foreach ($this->rules as $rule) {
                switch ($rule['type']) {
                    case 'birthday':
                        if ($customer->isBirthday()) {
                            $this->addRules($finalRule, $rule);
                        }
                        break;
                    case 'lump_sum':
                        if ($receipt > $rule['condition_value']) {
                            $this->addRules($finalRule, $rule);
                        }
                        break;
                    case 'turnover':
                        if (($receipt + $customer->getTurnover()) > $rule['condition_value']) {
                            $this->addRules($finalRule, $rule);
                        }
                        break;
                    case 'dates':
                        if ($this->isHoliday()) {
                            $this->addRules($finalRule, $rule);
                        }
                        break;
                }
            }
            $change = ($receipt * $finalRule['percentage'] + $finalRule['add']) * $finalRule['multiplier'];
            $customer->changeBonuses($change);
            if ($customer->getDiscount() < $finalRule['discount']) {
                $customer->setDiscount($finalRule['discount']);
            }
        }
    }

    /** "складывает" два правила по начислению бонусов.
     *
     * @param $currentRule
     * @param $newRule
     *
     * @return array
     */
    private function addRules($currentRule, $newRule): array
    {
        $currentRule['add'] += $newRule['add'];
        $currentRule['multiplier'] *= $newRule['multiplier'];
        $currentRule['percentage'] = max($currentRule['percentage'], $newRule['percentage']);
        $currentRule['discount'] = max($currentRule['discount'], $newRule['discount']);
        return $currentRule;
    }

    /**
     * @return bool
     */
    private function isHoliday(): bool
    {

        $sqlQuery = <<<SQL
SELECT *
FROM holidays
WHERE date={date('Y-m-d')};
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        return count(mysqli_fetch_all($result)) > 0;
    }
}