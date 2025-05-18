<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: text/html');

try {
    if (!isset($_POST['type'])) {
        throw new Exception('Invalid request');
    }

    $type = $_POST['type'];
    $query = '';
    $columns = [];

    switch ($type) {
        case 'orders':
            $query = "SELECT 
                        id AS order_id, 
                        user_id, 
                        total_amount, 
                        payment_method, 
                        status, 
                        created_at 
                      FROM orders 
                      WHERE status = 'confirmed'";
            $columns = ['order_id', 'user_id', 'total_amount', 'payment_method', 'status', 'created_at'];
            break;

        case 'cubicle_bookings':
            $query = "SELECT 
                        booking_id, 
                        user_id, 
                        cubicle_id, 
                        booking_time AS start_time, 
                        created_at AS end_time, 
                        status 
                      FROM cubicle_bookings 
                      WHERE status = 'accepted'";
            $columns = ['booking_id', 'user_id', 'cubicle_id', 'start_time', 'end_time', 'status'];
            break;

        case 'room_bookings':
            $query = "SELECT 
                        booking_id, 
                        user_id, 
                        room_id, 
                        booking_time AS start_time, 
                        created_at AS end_time, 
                        status 
                      FROM room_bookings 
                      WHERE status = 'accepted'";
            $columns = ['booking_id', 'user_id', 'room_id', 'start_time', 'end_time', 'status'];
            break;

        case 'room_sessions':
            $query = "SELECT 
                        session_id, 
                        room_id, 
                        start_time, 
                        end_time, 
                        total_bill 
                      FROM study_room_sessions 
                      WHERE status = 'expired'";
            $columns = ['session_id', 'room_id', 'start_time', 'end_time', 'total_bill'];
            break;

        case 'cubicle_sessions':
            $query = "SELECT 
                        id AS session_id, 
                        cubicle_id, 
                        user_id, 
                        start_time, 
                        end_time, 
                        total_bill 
                      FROM timer_sessions 
                      WHERE status = 'expired'";
            $columns = ['session_id', 'cubicle_id', 'user_id', 'start_time', 'end_time', 'total_bill'];
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch associative array

    echo generateTableHtml($data, $columns);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
}

function generateTableHtml($data, $columns)
{
    if (empty($data)) {
        return '<div class="alert alert-info">No records found for this report</div>';
    }

    $html = '<table class="table table-striped table-hover dt-init">';
    $html .= '<thead><tr>';

    foreach ($columns as $column) {
        $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $column))) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $column) {
            // Check if column exists in the row to avoid "N/A"
            $value = isset($row[$column]) ? $row[$column] : 'N/A';
            // Handle empty values
            $html .= '<td>' . (!empty($value) ? htmlspecialchars($value) : '—') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}