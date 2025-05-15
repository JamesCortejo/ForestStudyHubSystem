<?php
require_once __DIR__ . '/../../../includes/db.php';

if (!isset($_POST['type'])) {
    die('Invalid request');
}

$type = $_POST['type'];
$tableHtml = '';

try {
    switch ($type) {
        case 'orders':
            $stmt = $pdo->query("SELECT * FROM orders WHERE status = 'confirmed'");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableHtml = generateTableHtml($data, ['id', 'user_id', 'total_amount', 'payment_method', 'status', 'created_at']);
            break;

        case 'cubicle_bookings':
            $stmt = $pdo->query("SELECT * FROM cubicle_bookings WHERE status = 'accepted'");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableHtml = generateTableHtml($data, ['booking_id', 'user_id', 'cubicle_id', 'duration', 'booking_time', 'status']);
            break;

        case 'room_bookings':
            $stmt = $pdo->query("SELECT * FROM room_bookings WHERE status = 'accepted'");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableHtml = generateTableHtml($data, ['booking_id', 'user_id', 'room_id', 'duration', 'booking_time', 'status']);
            break;

        case 'room_sessions':
            $stmt = $pdo->query("SELECT * FROM study_room_sessions WHERE status = 'expired'");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableHtml = generateTableHtml($data, ['session_id', 'room_id', 'start_time', 'end_time', 'time_remaining', 'total_bill']);
            break;

        case 'cubicle_sessions':
            $stmt = $pdo->query("SELECT * FROM timer_sessions WHERE status = 'expired'");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableHtml = generateTableHtml($data, ['id', 'user_id', 'cubicle_id', 'start_time', 'end_time', 'time_remaining', 'total_bill']);
            break;

        default:
            die('Invalid report type');
    }

    echo $tableHtml;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function generateTableHtml($data, $columns)
{
    if (empty($data)) {
        return '<p>No records found</p>';
    }

    $html = '<div class="table-responsive">'; // Already in your code
    $html .= '<table class="table table-striped">';

    foreach ($columns as $column) {
        $html .= '<th>' . ucwords(str_replace('_', ' ', $column)) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $column) {
            $html .= '<td>' . htmlspecialchars($row[$column] ?? '') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';
    return $html;
}