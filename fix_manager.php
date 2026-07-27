<?php
$db = new PDO('sqlite:var/data_dev.db');

// Assigner le superviseur (ID:3) comme manager de l'agent (ID:2)
$stmt = $db->prepare('UPDATE "user" SET manager_id = ? WHERE email = ?');
$stmt->execute([3, 'agent@gestprimes.fr']);

echo "Manager mis a jour !\n";

// Verification
foreach ($db->query('SELECT id, email, manager_id FROM "user"')->fetchAll(PDO::FETCH_ASSOC) as $u) {
    echo "ID:{$u['id']} | {$u['email']} | manager_id:{$u['manager_id']}\n";
}
