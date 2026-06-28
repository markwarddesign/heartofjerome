<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'total'    => total_acts(),
    'goal'     => GOAL,
    'starting' => STARTING_COUNT,
]);
