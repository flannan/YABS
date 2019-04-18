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
    protected $applicable = true;
    protected $basedOnBonuses = true;
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
        $this->applicable = (bool)$result['apply_rules'];
        $this->basedOnBonuses = (bool)$result['bonuses'];
        if ($this->isApplicable()) {
            $this->retrieveRules();
        }
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
        if ($this->applicable === true) {
            $finalRule = [
                'multiplier' => 1,
                'add' => 0,
                'percentage' => 0,
                'discount' => 0
            ];
            foreach ((array) $this->rules as $rule) {
                if ($this->checkRule($rule, $customer, $receipt)) {
                    $finalRule = $this->sumRules($finalRule, $rule);
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
        return !empty(mysqli_fetch_all($result));
    }

    /**
     * @return array|null
     */
    public function getHolidays(): array
    {
        $sqlQuery = <<<SQL
SELECT *
FROM holidays;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Holidays not found');
        }
        return mysqli_fetch_all($result);
    }

    /**
     * @param int    $timestamp
     * @param string $name
     */
    public function addHoliday(int $timestamp, string $name): void
    {
        $sqlQuery = <<<SQL
INSERT INTO holidays
VALUES ({date('Y-m-d',$timestamp)},'$name')
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Holiday date adding operation failed');
        }
        $this->user->log(
            'new holiday',
            'День ' . date('Y-m-d', $timestamp) . ' объявлен праздничным и называется ' . $name
        );
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
        if (is_array($this->rules) === false) {
            throw new RuntimeException('rules not found');
        }
    }


    /**
     * @param $rule
     */
    public function addRule($rule): void
    {
        $this->user->requireManager();

        $sqlQuery = <<<SQL
INSERT INTO rules(type,condition_value,bonus,multiplier,percentage,discount)
VALUES ('{$rule['type']}',{$rule['condition_value']},
        {$rule['bonus']},{$rule['multiplier']},{$rule['percentage']},{$rule['discount']});
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Rule adding operation failed');
        }
        $this->logRule($rule, 'добавлено');
        $this->retrieveRules();
    }

    /**
     * @param $rule
     */
    public function replaceRule($rule): void
    {
        $this->user->requireManager();

        $sqlQuery = <<<SQL
REPLACE INTO rules(id,type,condition_value,bonus,multiplier,percentage,discount)
VALUES ({$rule['id']},'{$rule['type']}',{$rule['condition_value']},
        {$rule['bonus']},{$rule['multiplier']},{$rule['percentage']},{$rule['discount']});
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Rule changing operation failed');
        }
        $this->logRule($rule, 'заменено');
        $this->retrieveRules();
    }

    /** Удаляет правило по номеру.
     *
     * @param int $ruleId
     */
    public function removeRule(int $ruleId): void
    {
        $this->user->requireManager();

        $sqlQuery = <<<SQL
DELETE FROM rules
WHERE id=$ruleId;
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Rule deletion operation failed');
        }
        $this->user->log('rule change', "Правило №$ruleId удалено.");
        $this->retrieveRules();
    }

    /**
     * @param        $rule
     * @param string $action
     */
    private function logRule($rule, string $action): void
    {
        $message = $action . ' правило ';
        if (array_key_exists('id', $rule)) {
            $message .= "№ {$rule['id']} ";
        }
        $message .= 'при условии ' . $rule['type'];
        if (isset($rule['condition_value'])) {
            $message .= ' > ' . $rule['condition_value'];
        }
        $message .= ' выполнять:';
        if (isset($rule['condition_value'])) {
            $message .= ' бонус +' . $rule['bonus'];
        }
        if (isset($rule['multiplier'])) {
            $message .= ' бонусы умножаются на ' . $rule['multiplier'];
        }
        if (isset($rule['percentage'])) {
            $message .= ' выдать бонус на ' . 100 * $rule['percentage'] . '% от чека';
        }
        if (isset($rule['discount'])) {
            $message .= ' выдать скидку на ' . 100 * $rule['discount'] . '% от чека';
        }

        $this->user->log('rule change', $message);
    }

    /**
     * @return bool
     */
    public function isApplicable(): bool
    {
        return $this->applicable;
    }

    /**
     * @return bool
     */
    public function isBasedOnBonuses(): bool
    {
        return $this->basedOnBonuses;
    }

    /**
     * @param bool $applicable
     */
    public function setApplicable(bool $applicable): void
    {
        $this->applicable = $applicable;
        $this->setSettings();
    }

    /**
     * @param bool $basedOnBonuses
     */
    public function setBasedOnBonuses(bool $basedOnBonuses): void
    {
        $this->basedOnBonuses = $basedOnBonuses;
        $this->setSettings();
    }

    private function setSettings(): void
    {
        $basedOnBonuses=(int)$this->basedOnBonuses;
        $applicable=(int)$this->applicable;
        $sqlQuery = <<<SQL
REPLACE 
    INTO settings (id, bonuses, apply_rules) 
VALUES (1, $basedOnBonuses , $applicable);
SQL;
        $result = mysqli_query($this->database->getConnection(), $sqlQuery);
        if ($result === false) {
            throw new RuntimeException('Setting changing operation failed');
        }
    }
}
