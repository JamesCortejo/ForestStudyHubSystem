<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['q'])) {
        throw new Exception('Search query parameter is missing');
    }

    $searchTerm = '%' . $pdo->quote($_GET['q']) . '%';
    $searchTerm = str_replace("'", "", $searchTerm); // Remove quotes added by quote()

    $stmt = $pdo->prepare("SELECT *, 
                          MATCH(product_name, description) AGAINST (:search IN BOOLEAN MODE) AS relevance 
                          FROM products 
                          WHERE MATCH(product_name, description) AGAINST (:search IN BOOLEAN MODE)
                          OR product_name LIKE :likeSearch
                          OR description LIKE :likeSearch
                          ORDER BY relevance DESC");

    $stmt->execute([
        ':search' => $_GET['q'] . '*',
        ':likeSearch' => '%' . $_GET['q'] . '%'
    ]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $products
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}