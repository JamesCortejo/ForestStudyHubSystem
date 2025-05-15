<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        if (!isset($_POST['id'])) {
            throw new Exception('Product ID not provided');
        }
        
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }

        $pdo->beginTransaction();

        // Get existing product data
        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            throw new Exception('Product not found');
        }

        // Handle image upload
        $imagePath = $existing['image_path'];
        if (!empty($_FILES['image']['name'])) {
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
                throw new Exception('Invalid file type. Only JPG/PNG/GIF allowed.');
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/products/';
            $fileName = uniqid() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', $_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                throw new Exception('Failed to upload image');
            }

            // Delete old image
            if (!empty($existing['image_path'])) {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/' . $existing['image_path'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $imagePath = '/ForrestStudy_Hub/resources/products/' . $fileName;
        }

        // Update product
        $stmt = $pdo->prepare("UPDATE products SET
            product_name = ?,
            description = ?,
            price = ?,
            stock = ?,
            category = ?,
            image_path = ?
            WHERE id = ?");

        $stmt->execute([
            $_POST['product_name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category'],
            $imagePath,
            $productId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No changes made');
        }

        $pdo->commit();
        $response = ['status' => 'success', 'message' => 'Product updated'];

    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = $e->getMessage();
        error_log('Update Error: ' . $e->getMessage());
    }

    echo json_encode($response);
    exit;
}