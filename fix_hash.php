<?php
$db = new PDO('sqlite:var/data_dev.db');

// Hash vérifié OK pour le mot de passe "password"
$hash = '$2y$13$jHhO9yC33lDp6yAKG54ap.JPY/OKqPQLiufiXer65djbmd8MIPwGW';

$emails = ['admin@gestprimes.fr', 'agent@gestprimes.fr', 'sup@gestprimes.fr'];
foreach ($emails as $email) {
    $stmt = $db->prepare('UPDATE "user" SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    echo "Mis à jour : $email\n";
}

// Vérification
echo "\n=== Vérification ===\n";
$users = $db->query('SELECT email, password FROM "user"')->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    $ok = password_verify('password', $u['password']) ? 'OK ✓' : 'KO ✗';
    echo $u['email'] . " => $ok\n";
}
