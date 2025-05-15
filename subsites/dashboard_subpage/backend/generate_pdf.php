<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    $mpdf = new \Mpdf\Mpdf();
    header('Content-Type: application/pdf');

    $data = [];
    $columns = [];
    $title = '';

    try {
        switch ($type) {
            case 'orders':
                $stmt = $pdo->query("SELECT * FROM orders WHERE status = 'confirmed'");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = ['id', 'user_id', 'total_amount', 'payment_method', 'status', 'created_at'];
                $title = 'Confirmed Orders Report';
                break;

            case 'cubicle_bookings':
                $stmt = $pdo->query("SELECT * FROM cubicle_bookings WHERE status = 'accepted'");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = ['booking_id', 'user_id', 'cubicle_id', 'duration', 'booking_time', 'status'];
                $title = 'Accepted Cubicle Bookings Report';
                break;

            case 'room_bookings':
                $stmt = $pdo->query("SELECT * FROM room_bookings WHERE status = 'accepted'");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = ['booking_id', 'user_id', 'room_id', 'duration', 'booking_time', 'status'];
                $title = 'Accepted Room Bookings Report';
                break;

            case 'room_sessions':
                $stmt = $pdo->query("SELECT * FROM study_room_sessions WHERE status = 'expired'");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = ['session_id', 'room_id', 'start_time', 'end_time', 'time_remaining', 'total_bill'];
                $title = 'Expired Room Sessions Report';
                break;

            case 'cubicle_sessions':
                $stmt = $pdo->query("SELECT * FROM timer_sessions WHERE status = 'expired'");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = ['id', 'user_id', 'cubicle_id', 'start_time', 'end_time', 'time_remaining', 'total_bill'];
                $title = 'Expired Cubicle Sessions Report';
                break;

            default:
                die('Invalid report type');
        }

        $html = generatePdfHtml($title, $data, $columns);
        $mpdf->WriteHTML($html);
        $mpdf->Output("{$type}_report.pdf", 'I');

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

function generatePdfHtml($title, $data, $columns)
{
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
            h3 { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; }
            tr:nth-child(even) { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h3>' . htmlspecialchars($title) . '</h3>
        <table>
            <thead><tr>';

    foreach ($columns as $column) {
        $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $column))) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $column) {
            $html .= '<td>' . htmlspecialchars($row[$column] ?? '') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></body></html>';
    return $html;
}