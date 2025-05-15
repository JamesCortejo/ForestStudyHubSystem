<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../php/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

verifyUserSession();

try {
    if (!isset($_SESSION['checkout_cart']) || empty($_SESSION['checkout_cart'])) {
        throw new Exception("Your cart is empty");
    }

    global $pdo;
    $total = 0.00;

    foreach ($_SESSION['checkout_cart'] as $item) {
        if (!isset($item['id'], $item['quantity'])) {
            error_log("Invalid cart item: " . print_r($item, true));
            continue;
        }

        $stmt = $pdo->prepare("SELECT product_name, price, image_path FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            error_log("Product {$item['id']} not found");
            continue;
        }

        // Convert price to float and calculate
        $price = (float) str_replace(',', '', $product['price']);
        $subtotal = $price * (int) $item['quantity'];
        $total += $subtotal;
        ?>
        <div class="cart-item d-flex align-items-center mb-3">
            <img src="<?= htmlspecialchars($product['image_path']) ?>" class="me-3 rounded" width="60" height="60"
                alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='/path/to/default-image.jpg'">
            <div class="flex-grow-1">
                <h6 class="mb-1"><b><?= htmlspecialchars($product['product_name']) ?></b></h6>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Price: ₱<?= number_format($price, 2, '.', '') ?> <br> Quantity: <?= $item['quantity'] ?></span>
                    <span>Total: ₱<?= number_format($subtotal, 2, '.', '') ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalElement = document.getElementById('totalAmount');
            if (totalElement) {
                // Format with 2 decimal places using PHP
                totalElement.textContent = '<?= number_format($total, 2) ?>';
                // Store raw value for calculations
                totalElement.dataset.rawTotal = <?= number_format($total, 2, '.', '') ?>;
            }
        });
    </script>
    <?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error. Please try again.</div>';
} catch (Exception $e) {
    echo '<div class="alert alert-warning">' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>