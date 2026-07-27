<?php
$db = new PDO('sqlite:var/data_dev.db');

echo "=== VENTES ===\n";
$sales = $db->query('SELECT id, type, offre, statut, prime, agent_id, date FROM sale')->fetchAll(PDO::FETCH_ASSOC);
foreach ($sales as $s) {
    echo "ID:{$s['id']} | {$s['type']} {$s['offre']} | statut:{$s['statut']} | prime:{$s['prime']} | agent_id:{$s['agent_id']} | date:{$s['date']}\n";
}

echo "\n=== TABLES EN BASE ===\n";
foreach ($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC) as $t) {
    echo $t['name'] . "\n";
}

echo "\n=== BAREMES ACTIFS ===\n";
$baremes = $db->query("SELECT produit, offre, prime, date_effet FROM bareme WHERE actif = 1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($baremes as $b) {
    echo "{$b['produit']} | {$b['offre']} | {$b['prime']}€ | depuis {$b['date_effet']}\n";
}

echo "\n=== USERS ===\n";
$users = $db->query('SELECT id, email, roles FROM "user"')->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID:{$u['id']} | {$u['email']} | {$u['roles']}\n";
}
