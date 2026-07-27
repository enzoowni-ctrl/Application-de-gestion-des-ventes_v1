<?php
$db = new PDO('sqlite:var/data_dev.db');
foreach (['user','sale','bareme'] as $t) {
    echo '=== '  . $t . " ===\n";
    $rows = $db->query('PRAGMA table_info("' . $t . '")');
    foreach ($rows as $r) {
        echo $r[1] . ' (' . $r[2] . ")\n";
    }
}
