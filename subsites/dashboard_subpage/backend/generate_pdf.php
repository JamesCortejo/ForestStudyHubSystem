<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10
        ]);

        $data = [];
        $columns = [];
        $title = '';

        switch ($type) {
            case 'orders':
                $stmt = $pdo->prepare("
                    SELECT 
                        id AS order_id,
                        user_id,
                        total_amount,
                        payment_method,
                        status,
                        created_at
                    FROM orders 
                    WHERE status = 'confirmed'
                ");
                $columns = ['order_id', 'user_id', 'total_amount', 'payment_method', 'status', 'created_at'];
                $title = 'Confirmed Orders Report';
                break;

            case 'cubicle_bookings':
                $stmt = $pdo->prepare("
                    SELECT 
                        booking_id,
                        user_id,
                        cubicle_id,
                        booking_time AS start_time,
                        DATE_ADD(booking_time, INTERVAL (duration * 60) MINUTE) AS end_time,
                        status
                    FROM cubicle_bookings 
                    WHERE status = 'accepted'
                ");
                $columns = ['booking_id', 'user_id', 'cubicle_id', 'start_time', 'end_time', 'status'];
                $title = 'Accepted Cubicle Bookings Report';
                break;

            case 'room_bookings':
                $stmt = $pdo->prepare("
                    SELECT 
                        booking_id,
                        user_id,
                        room_id,
                        booking_time AS start_time,
                        DATE_ADD(booking_time, INTERVAL (duration * 60) MINUTE) AS end_time,
                        status
                    FROM room_bookings 
                    WHERE status = 'accepted'
                ");
                $columns = ['booking_id', 'user_id', 'room_id', 'start_time', 'end_time', 'status'];
                $title = 'Accepted Room Bookings Report';
                break;

            case 'room_sessions':
                $stmt = $pdo->prepare("
                    SELECT 
                        session_id,
                        room_id,
                        start_time,
                        end_time,
                        total_bill
                    FROM study_room_sessions 
                    WHERE status = 'expired'
                ");
                $columns = ['session_id', 'room_id', 'start_time', 'end_time', 'total_bill'];
                $title = 'Expired Room Sessions Report';
                break;

            case 'cubicle_sessions':
                $stmt = $pdo->prepare("
                    SELECT 
                        id AS session_id,
                        cubicle_id,
                        user_id,
                        start_time,
                        end_time,
                        total_bill
                    FROM timer_sessions 
                    WHERE status = 'expired'
                ");
                $columns = ['session_id', 'cubicle_id', 'user_id', 'start_time', 'end_time', 'total_bill'];
                $title = 'Expired Cubicle Sessions Report';
                break;

            default:
                die('Invalid report type');
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .report-title { 
                        text-align: center; 
                        font-size: 16pt; 
                        margin-bottom: 10mm;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 0 auto;
                    }
                    th {
                        background-color: #f2f2f2;
                        border: 0.5mm solid #000000;
                        padding: 3mm;
                        font-size: 10pt;
                        text-align: left;
                    }
                    td {
                        border: 0.5mm solid #000000;
                        padding: 3mm;
                        font-size: 10pt;
                    }
                    .header-info {
                        text-align: center;
                        margin-bottom: 5mm;
                        font-size: 9pt;
                    }
                </style>
            </head>
            <body>
                <div class="header-info">
                    <div>StudyHub Management System</div>
                    <div>Generated: ' . date('Y-m-d H:i:s') . '</div>
                </div>
                <div class="report-title">' . htmlspecialchars($title) . '</div>
                <table>
                    <thead>
                        <tr>';

        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $column))) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $row[$column] ?? 'N/A';
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        $mpdf->SetHeader('StudyHub Reports||Page {PAGENO}');
        $mpdf->WriteHTML($html);
        $mpdf->Output("{$type}_report.pdf", 'D');

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    } catch (\Mpdf\MpdfException $e) {
        die("PDF generation error: " . $e->getMessage());
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request method');
}