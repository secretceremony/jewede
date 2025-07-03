<?php
// pages/product_detail.php
$page_title = "Detail Paket Ilustrasi";
require_once __DIR__ . '/../includes/header.php';

$product = null;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Ambil ID produk dari URL

if ($product_id > 0) {
    $sql = "SELECT id, name, description, price, stock, image_url FROM products WHERE id = ?";
    $stmt = execute_query($sql, 'i', [$product_id]);
    $product = fetch_one($stmt);
    $stmt->close();

    if ($product) {
        $page_title = htmlspecialchars($product['name']) . " - Guza";
    }
}

// Jika produk tidak ditemukan, arahkan kembali ke dashboard atau tampilkan pesan error
if (!$product) {
    // Opsional: Tampilkan halaman 404 atau redirect
    echo '<div class="container my-5 text-center">';
    echo '  <div class="alert alert-warning" role="alert">';
    echo '    Paket ilustrasi tidak ditemukan.';
    echo '  </div>';
    echo '  <a href="dashboard.php" class="btn btn-primary">Kembali ke Katalog</a>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="<?php echo $base_url . htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-lg-6">
            <h1 class="mb-3 display-5 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="text-primary fs-3 fw-bold mb-4">$<?php echo number_format($product['price'], 2); ?></p>

            <h5 class="fw-bold mb-3">Deskripsi:</h5>
            <p class="mb-4 text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <div class="mb-4">
                <h5 class="fw-bold mb-2">Ketersediaan:</h5>
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success text-white">Tersedia (<?php echo $product['stock']; ?> slot)</span>
                <?php else: ?>
                    <span class="badge bg-danger text-white">Stok Habis</span>
                <?php endif; ?>
            </div>

            <form action="cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div class="mb-3">
                    <label for="quantity" class="form-label fw-bold">Kuantitas:</label>
                    <input type="number" class="form-control w-auto" id="quantity" name="quantity" value="1" min="1" <?php echo ($product['stock'] > 0) ? 'max="' . $product['stock'] . '"' : 'disabled'; ?> required>
                </div>

                <?php if ($product['stock'] > 0): ?>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-shopping-cart me-2"></i> Tambah ke Keranjang
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                        Stok Habis
                    </button>
                <?php endif; ?>
            </form>

            <p class="small text-muted mt-3">
                Estimasi waktu pengerjaan, jumlah revisi, dan detail lainnya akan dikonfirmasi setelah pemesanan.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>