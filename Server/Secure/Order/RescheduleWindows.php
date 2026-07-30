<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$windows = array_map(
    static fn ($option) => [
        'label' => $option['label'],
        'day'   => $option['day'],
        'time'  => $option['time'],
    ],
    getRescheduleWindowOptions()
);

echo json_encode([
    'success' => true,
    'windows' => $windows,
]);
exit;
