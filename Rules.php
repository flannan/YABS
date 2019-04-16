<?php
declare(strict_types=1);


namespace flannan\YABS;

use RuntimeException;

/**
 * Class Rules
 *
 * @package flannan\YABS
 */
class Rules
{
    protected $database;
    protected $user;
    protected $apply = true;
    protected $bonuses = true;
    protected $rules;


    /**
     * Rules constructor.
     *
     * @param \flannan\YABS\Database $database
     * @param \flannan\YABS\User     $user
     */
    public function __construct(Database $database, User $user)
    {
        $this->database = $database;
        $this->user = $user;

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

        $this->retrieveRules();
    }

    /**
     * @param \flannan\YABS\Customer $customer
     */
    public function initialize(Customer $customer): void
    {
        $customer->setDiscount(0);
        $customer->setBonuses(0);
        $this->apply($customer, 0);
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
                if ($this->checkRule($rule, $customer, $receipt)) {
                    $finalRule=$this->sumRules($finalRule, $rule);
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
    private function sumRules($currentRule, $newRule): array
    {
        if (isset($newRule['add'])) {
            $currentRule['add'] = max($currentRule['add'], $newRule['add']);
        }
        if (isset($newRule['multiplier'])) {
            $currentRule['multiplier'] = max($currentRule['multiplier'], $newRule['multiplier']);
        }
        if (isset($newRule['percentage'])) {
            $currentRule['percentage'] = max($currentRule['percentage'], $newRule['percentage']);
        }
        if (isset($newRule['discount'])) {
            $currentRule['discount'] = max($currentRule['discount'], $newRule['discount']);
        }
        return $currentRule;
    }

    /**
     * @param array                  $rule
     * @param \flannan\YABS\Customer $customer
     * @param float                  $receipt
     *
     * @return bool
     */
    private function checkRule(array $rule, Customer $customer, float $receipt): bool
    {
        $applies = false;
        switch ($rule['type']) {
            case 'birthday':
                $applies = $customer->isBirthday();
                break;
            case 'lump_sum':
                $applies = ($receipt > $rule['condition_value']);
                break;
            case 'turnover':
                $applies = ($customer->getTurnover() > $rule['condition_value']);
                break;
            case 'dates':
                $applies = $this->isHoliday();
                break;
        }
        return $applies;
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

    /**
     * @return array|null
     */
    public function getRules(): ?array
    {
        return $this->rules;
    }

    private function retrieveRules(): void
    {
        $sqlQuery = <<<SQL
SELECT *
FROM rules;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        $this->rules = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }


    /**
     * @param $rule
     */
    public function addRule($rule): void
    {
        if ($this->user->isManager() === false) {
            throw new RuntimeException('Access denied. Manager level necessary.');
        }
        $sqlQuery = <<<SQL
INSERT INTO rules(type,condition_value,bonus,multiplier,percentage,discount)
VALUES ('{$rule['type']}',{$rule['condition_value']},
        {$rule['bonus']},{$rule['multiplier']},{$rule['percentage']},{$rule['discount']});
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Rule adding operation failed');
        }
        $this->retrieveRules();
    }
}
