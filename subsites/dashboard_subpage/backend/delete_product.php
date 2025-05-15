<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];

    try {
        // Verify product ID exists
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            throw new Exception('Product ID not provided');
        }

        // Validate product ID
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }

        // Get database connection
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }

        // Start transaction
        $pdo->beginTransaction();

        // 1. Get product details
        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Product not found');
        }

        // 2. Delete from database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);

        // Verify deletion
        if ($stmt->rowCount() === 0) {
            throw new Exception('No records deleted - product may not exist');
        }

        // 3. Delete image file if exists
        if (!empty($product['image_path'])) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/' . $product['image_path'];
            if (file_exists($filePath) && is_writable($filePath)) {
                if (!unlink($filePath)) {
                    throw new Exception('Failed to delete image file');
                }
            }
        }

        // Commit transaction
        $pdo->commit();

        $response = [
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ];

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = $e->getMessage();
        error_log('Delete Error: ' . $e->getMessage());
    }

    echo json_encode($response);
    exit;
}
?>