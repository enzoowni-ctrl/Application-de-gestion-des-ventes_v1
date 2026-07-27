<?php
$db = new PDO('sqlite:var/data_dev.db');
$hash = '$2y$13$jHhO9yC33lDp6yAKG54ap.JPY/OKqPQLiufiXer65djbmd8MIPwGW';

$emails = ['admin@gestprimes.fr', 'agent@gestprimes.fr', 'sup@gestprimes.fr'];
foreach ($emails as $email) {
    $stmt = $db->prepare('UPDATE "user" SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    echo "Hash mis à jour : $email\n";
}
echo "Done ! Mot de passe : password\n";
