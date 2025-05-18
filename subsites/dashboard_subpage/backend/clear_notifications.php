<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');

$timestamp_files = [
    __DIR__ . '/admin_notifications_time.json',
    __DIR__ . '/admin_order_notifications_time.json'
];

try {
    $clear_time = time() + 10; // 10-second future buffer

    foreach ($timestamp_files as $file) {
        $fp = fopen($file, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode(['last_check' => $clear_time]));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    echo json_encode([
        'success' => true,
        'clear_time' => $clear_time,
        'server_time' => time()
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Clear failed']);
}