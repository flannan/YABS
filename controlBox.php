<!DOCTYPE html>
<html lang="ru">
<head>
    <title>YABS</title></head>
<body>
<?php
//default $_POST values
if (empty($_POST)) {
    $_POST['username'] = 'manager';
    $_POST['userPassword'] = '';
}

$user = $_POST['username'];
$password = $_POST['userPassword'];
$authorization = base64_encode($user . ':' . password_hash($password, PASSWORD_BCRYPT));
?>
<p><b>Админка</b></p>
<p>Заглушка пока я пишу собственно админку</p>
<form action="controlBox.php" method="post">
    <p>
        <label>
            Имя пользователя:
            <input type="text" name="username" value="<?= $_POST['username'] ?>">
        </label>
        <label>
            Пароль:
            <input type="password" name="userPassword" value="<?= $_POST['userPassword'] ?>">
        </label>
    </p>
    <input type="submit" value="Отправить запрос"/>
</form>


<p><b>Статус системы</b></p>
<?php
$data = [];
$options = [
    'http' => [
        'method' => 'GET',
        'content' => json_encode($data),
        'header' => 'Content-Type: application/json' . PHP_EOL .
            'Authorization: Basic ' . $authorization . PHP_EOL
    ]
];
$url = 'http://hotel.rarus-crimea.ru/YABS/api/customers';
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);
if ($response['totalBonusesUsed'] === null) {
    $response['totalBonusesUsed'] = 0;
}
?>
<p>Количество пользователей с картами: <?php echo $response['numberOfCards'] ?> человек</p>
<p>Сумма бонусов на картах: <?php echo $response['totalBonuses'] ?> бонусных рублей</p>
<p>Бонусов потрачено за всё время: <?php echo $response['totalBonusesUsed'] ?> бонусных рублей</p>

<?php
$url = 'http://hotel.rarus-crimea.ru/YABS/api/settings/1';
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);
?>
<p>Правила применяются системой:
    <?php if ($response['apply']) {
        echo 'да';
    } else {
        echo 'нет';
    } ?>
</p>
<p>Система использует:
    <?php if ($response['bonuses']) {
        echo 'бонусы';
    } else {
        echo 'скидки';
    } ?>
</p>
<p><b>Список правил</b></p>
<style>
    table, th, td {
        border: 1px solid black;
        border-spacing: 10px;
    }
    th, td { padding: 5px; }
</style>
<table style="border-collapse: collapse; ">
    <tr><td>№</td><td>тип</td><td>условие</td><td>бонус</td><td>множитель</td><td>процент</td><td>скидка</td></tr>
    <?php
    foreach ((array) $response['rules'] as $rule) {
        echo '<tr>';
        foreach ($rule as $data) {
            echo "<td> $data </td>";
        }
        echo '</tr>';
    }
    ?>
</table>
</body>
