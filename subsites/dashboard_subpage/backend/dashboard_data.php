<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    $data = [];

    // Current Active Sessions
    $stmt = $pdo->query("SELECT COUNT(*) AS cubicle FROM timer_sessions WHERE status = 'active'");
    $data['currentSessions']['cubicle'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS room FROM study_room_sessions WHERE status = 'active'");
    $data['currentSessions']['room'] = $stmt->fetchColumn();

    // Today's Sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS sales 
                          FROM orders 
                          WHERE DATE(created_at) = CURDATE() 
                          AND status = 'confirmed'");
    $stmt->execute();
    $data['todaySales'] = (float) $stmt->fetchColumn();

    // Booking Overview
    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM room_bookings) + 
        (SELECT COUNT(*) FROM cubicle_bookings) AS total");
    $data['bookings']['total'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM room_bookings WHERE status = 'pending') + 
        (SELECT COUNT(*) FROM cubicle_bookings WHERE status = 'pending') AS pending");
    $data['bookings']['pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM room_bookings WHERE status = 'accepted') + 
        (SELECT COUNT(*) FROM cubicle_bookings WHERE status = 'accepted') AS approved");
    $data['bookings']['approved'] = $stmt->fetchColumn();

    // Purchase Overview
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM orders");
    $data['purchases']['total'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS pending FROM orders WHERE status = 'pending'");
    $data['purchases']['pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS confirmed FROM orders WHERE status = 'confirmed'");
    $data['purchases']['confirmed'] = $stmt->fetchColumn();

    // Session Distribution
    $stmt = $pdo->query("SELECT COUNT(*) AS cubicle FROM timer_sessions");
    $data['sessionDistribution']['cubicle'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS room FROM study_room_sessions");
    $data['sessionDistribution']['room'] = $stmt->fetchColumn();

    // Sales Breakdown
    $stmt = $pdo->query("SELECT p.category, SUM(oi.quantity) AS total 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        JOIN orders o ON oi.order_id = o.id
                        WHERE DATE(o.created_at) = CURDATE()
                        AND o.status = 'confirmed'
                        GROUP BY p.category");
    $data['salesBreakdown'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Hourly Usage Trends
    $stmt = $pdo->query("SELECT 
        CASE
            WHEN duration = 3600 THEN '1 hour'
            WHEN duration = 5400 THEN '1.5 hours'
            WHEN duration = 7200 THEN '2 hours'
            WHEN duration = 9000 THEN '2.5 hours'
            WHEN duration = 10800 THEN '3 hours'
            WHEN duration = 12600 THEN '3.5 hours'
            WHEN duration = 14400 THEN '4 hours'
            ELSE 'Other'
        END AS duration_label,
        COUNT(*) AS count
    FROM (
        SELECT TIMESTAMPDIFF(SECOND, start_time, end_time) AS duration
        FROM timer_sessions
        UNION ALL
        SELECT TIMESTAMPDIFF(SECOND, start_time, end_time) AS duration
        FROM study_room_sessions
    ) AS sessions
    GROUP BY duration_label
    ORDER BY FIELD(duration_label, 
        '1 hour',
        '1.5 hours',
        '2 hours',
        '2.5 hours',
        '3 hours',
        '3.5 hours',
        '4 hours',
        'Other')");

    $hourlyUsage = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['hourlyUsage'] = $hourlyUsage;

    // Top Items
    $stmt = $pdo->query("SELECT p.category AS item, SUM(oi.quantity) AS total
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.status = 'confirmed'
                    GROUP BY p.category
                    ORDER BY total DESC
                    LIMIT 5");
    $data['topItems'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Session Revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_bill), 0) AS cubicle_rev 
                        FROM timer_sessions 
                        WHERE status = 'expired'");
    $data['cubicleRevenue'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_bill), 0) AS room_rev 
                        FROM study_room_sessions 
                        WHERE status = 'expired'");
    $data['roomRevenue'] = (float) $stmt->fetchColumn();

    // Product Statistics
    $stmt = $pdo->query("SELECT COUNT(*) AS total_products FROM products");
    $data['totalProducts'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT 
        p.id,
        p.product_name,
        p.image_path,
        SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'confirmed'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 3");
    $data['topProducts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}