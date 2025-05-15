<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$response = ['status' => 'error', 'message' => ''];
$pdo->beginTransaction();

try {
    // Validate required fields
    $requiredFields = ['product_name', 'description', 'price', 'stock', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input
    $productName = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    $category = trim($_POST['category']);

    if ($price === false || $price <= 0) {
        throw new Exception('Invalid price value');
    }

    if ($stock === false || $stock < 0) {
        throw new Exception('Invalid stock value');
    }

    // Check for existing product
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE product_name = ?");
    $checkStmt->execute([$productName]);
    if ($checkStmt->rowCount() > 0) {
        throw new Exception('Product with this name already exists');
    }

    // Handle image upload
    if (empty($_FILES['image']['name'])) {
        throw new Exception('Product image is required');
    }

    $file = $_FILES['image'];
    $fileType = mime_content_type($file['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed types: JPG, PNG, GIF');
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', $file['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to upload image');
    }

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO products 
        (product_name, description, price, stock, category, image_path)
        VALUES (?, ?, ?, ?, ?, ?)");

    $imagePath = '/ForrestStudy_Hub/resources/products/' . $fileName;
    $stmt->execute([
        $productName,
        $description,
        $price,
        $stock,
        $category,
        $imagePath
    ]);

    $pdo->commit();
    $response = [
        'status' => 'success',
        'message' => 'Product added successfully',
        'id' => $pdo->lastInsertId()
    ];

} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = $e->getMessage();

    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    error_log('Add Product Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>