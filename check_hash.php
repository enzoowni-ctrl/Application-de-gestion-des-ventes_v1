<?php
$db = new PDO('sqlite:var/data_dev.db');

echo "=== Users en base ===\n";
$users = $db->query('SELECT email, password FROM "user"')->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo $u['email'] . "\n";
    echo "  hash: " . $u['password'] . "\n";
    echo "  verify: " . (password_verify('password', $u['password']) ? 'OK ✓' : 'KO ✗') . "\n\n";
}

echo "=== Test hash direct ===\n";
$hash = '$2y$13$jHhO9yC33lDp6yAKG54ap.JPY/OKqPQLiufiXer65djbmd8MIPwGW';
echo "Hash attendu: " . $hash . "\n";
echo "Verify 'password': " . (password_verify('password', $hash) ? 'OK ✓' : 'KO ✗') . "\n";

echo "\n=== Nouveau hash généré ===\n";
echo password_hash('password', PASSWORD_BCRYPT) . "\n";
