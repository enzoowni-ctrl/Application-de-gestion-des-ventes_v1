<?php
$db = new PDO('sqlite:var/data_dev.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Hash bcrypt de "password"
$hash = '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC8a.rexchxXJ7BnX3m2';

// === USERS ===
$users = [
    ['admin@gestprimes.fr',  '["ROLE_ADMIN"]',        $hash, null],
    ['agent@gestprimes.fr',  '["ROLE_AGENT"]',         $hash, 1],
    ['sup@gestprimes.fr',    '["ROLE_SUPERVISEUR"]',   $hash, null],
];

foreach ($users as $u) {
    $stmt = $db->prepare('INSERT OR IGNORE INTO "user" (email, roles, password, manager_id) VALUES (?, ?, ?, ?)');
    $stmt->execute($u);
    echo "User inseré : " . $u[0] . "\n";
}

// === BAREMES ===
$baremes = [
    ['FTTH',    'FIT Serie Speciale',           48.38,  '2024-01-01', null,         1],
    ['FTTH',    'Serie Speciale',               101.45, '2024-01-01', '2026-06-07', 0],
    ['FTTH',    'Serie Speciale',               82.00,  '2026-06-08', null,         1],
    ['FTTH',    'MUST',                         134.27, '2024-01-01', '2026-06-07', 0],
    ['FTTH',    'MUST',                         153.27, '2026-06-08', null,         1],
    ['FTTH',    'ULTYM',                        153.29, '2024-01-01', '2026-06-07', 0],
    ['FTTH',    'ULTYM',                        173.30, '2026-06-08', null,         1],
    ['XGBOX',   'XGBOX',                        85.32,  '2024-01-01', null,         1],
    ['FAM',     '2H PRO 5Go',                   11.10,  '2024-01-01', null,         1],
    ['FAM',     'SL 20Go',                      51.80,  '2024-01-01', null,         1],
    ['FAM',     'SL 130Go',                     96.20,  '2024-01-01', null,         1],
    ['FAM',     '150Go',                        122.10, '2024-01-01', null,         1],
    ['FAM',     '200Go 300Go',                  170.20, '2024-01-01', null,         1],
    ['FSM',     '2H PRO 5Go',                   8.00,   '2024-01-01', null,         1],
    ['FSM',     'SL 20Go',                      14.80,  '2024-01-01', null,         1],
    ['FSM',     'SL 130Go 150Go 200Go 300Go',   33.30,  '2024-01-01', null,         1],
    ['FAM_MIG', '2H PRO 5Go',                   0.71,   '2024-01-01', null,         1],
    ['FAM_MIG', 'SL 20Go',                      6.70,   '2024-01-01', null,         1],
    ['FAM_MIG', 'SL 130Go',                     14.80,  '2024-01-01', null,         1],
    ['FAM_MIG', '150Go',                        22.20,  '2024-01-01', null,         1],
    ['FAM_MIG', '200Go 300Go',                  29.60,  '2024-01-01', null,         1],
    ['BBOX',    'BBOX EXTRA',                   37.00,  '2024-01-01', null,         1],
];

foreach ($baremes as $b) {
    $stmt = $db->prepare('INSERT INTO bareme (produit, offre, prime, date_effet, date_fin, actif) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute($b);
    echo "Bareme inseré : " . $b[0] . " - " . $b[1] . " : " . $b[2] . "€\n";
}

echo "\nDone ! Connectez-vous avec :\n";
echo "  admin@gestprimes.fr / password\n";
echo "  agent@gestprimes.fr / password\n";
echo "  sup@gestprimes.fr   / password\n";
