<?php
echo '<pre>';
echo 'ENV: ' . PHP_EOL;
print_r($_ENV);
echo 'SERVER DB vars: ' . PHP_EOL;
foreach ($_SERVER as $k => $v) {
    if (str_contains($k, 'DB_') || str_contains($k, 'MYSQL')) {
        echo "$k = $v" . PHP_EOL;
    }
}
echo '</pre>';