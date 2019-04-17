<?php
declare(strict_types=1);

echo base64_encode('cashier1:' . password_hash('qwerty', PASSWORD_BCRYPT)) . PHP_EOL;

echo base64_encode('manager:' . password_hash('zxcvb', PASSWORD_BCRYPT)) . PHP_EOL;
