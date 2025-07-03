<?php
$page_title = "Keranjang Belanja - Guza";
require_once __DIR__ . '/../includes/header.php';
protect_page(); // Hanya user yang login yang bisa mengakses keranjang

// Inisialisasi keranjang di session jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = ''; // Untuk pesan sukses/error
$message_type = ''; // success, danger, info, warning

// --- Logika Tambah Produk ke Keranjang ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)sanitize_input($_POST['product_id']);
    $quantity = (int)sanitize_input($_POST['quantity']);

    if ($product_id <= 0 || $quantity <= 0) {
        $message = "Input tidak valid.";
        $message_type = "danger";
    } else {
        // Ambil info produk dari database
        $sql = "SELECT id, name, price, stock, image_url FROM products WHERE id = ?";
        $stmt = execute_query($sql, 'i', [$product_id]);
        $product = fetch_one($stmt);
        $stmt->close();

        if ($product) {
            // Cek ketersediaan stok
            if ($quantity > $product['stock']) {
                $message = "Jumlah yang diminta melebihi stok yang tersedia untuk " . htmlspecialchars($product['name']) . ". Stok saat ini: " . $product['stock'];
                $message_type = "warning";
            } else {
                if (isset($_SESSION['cart'][$product_id])) {
                    // Produk sudah ada di keranjang, update kuantitas
                    $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                    if ($new_quantity > $product['stock']) {
                        $message = "Penambahan jumlah untuk " . htmlspecialchars($product['name']) . " melebihi stok. Hanya " . ($product['stock'] - $_SESSION['cart'][$product_id]['quantity']) . " yang dapat ditambahkan.";
                        $message_type = "warning";
                        $new_quantity = $product['stock']; // Set to max stock
                    }
                    $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
                    $message = htmlspecialchars($product['name']) . " kuantitas diperbarui di keranjang.";
                    $message_type = "success";
                } else {
                    // Produk belum ada di keranjang, tambahkan
                    $_SESSION['cart'][$product_id] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'image_url' => $product['image_url'],
                        'stock' => $product['stock'] // Simpan stok saat ini untuk validasi di keranjang
                    ];
                    $message = htmlspecialchars($product['name']) . " berhasil ditambahkan ke keranjang!";
                    $message_type = "success";
                }
            }
        } else {
            $message = "Produk tidak ditemukan.";
            $message_type = "danger";
        }
    }
}

// --- Logika Update Kuantitas di Keranjang ---
if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['product_id']) && isset($_GET['qty'])) {
    $product_id = (int)sanitize_input($_GET['product_id']);
    $new_quantity = (int)sanitize_input($_GET['qty']);

    if (isset($_SESSION['cart'][$product_id])) {
        // Re-check stock from DB (good practice)
        $sql = "SELECT stock FROM products WHERE id = ?";
        $stmt = execute_query($sql, 'i', [$product_id]);
        $product_db = fetch_one($stmt);
        $stmt->close();

        if ($product_db && $new_quantity > 0 && $new_quantity <= $product_db['stock']) {
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
            $message = "Kuantitas diperbarui.";
            $message_type = "success";
        } elseif ($new_quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            $message = "Produk dihapus dari keranjang.";
            $message_type = "success";
        } else {
            $message = "Kuantitas melebihi stok atau tidak valid.";
            $message_type = "warning";
        }
    } else {
        $message = "Produk tidak ditemukan di keranjang.";
        $message_type = "danger";
    }
    redirect('cart.php?msg=' . urlencode($message) . '&type=' . $message_type);
}

// --- Logika Hapus Produk dari Keranjang ---
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['product_id'])) {
    $product_id = (int)sanitize_input($_GET['product_id']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $message = "Produk berhasil dihapus dari keranjang.";
        $message_type = "success";
    } else {
        $message = "Produk tidak ditemukan di keranjang.";
        $message_type = "danger";
    }
    redirect('cart.php?msg=' . urlencode($message) . '&type=' . $message_type);
}

// Ambil pesan dari redirect GET jika ada
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

$cart_items = $_SESSION['cart'];
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Keranjang Belanja Anda</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info text-center" role="alert">
            Keranjang belanja Anda kosong. <a href="dashboard.php" class="alert-link">Mulai belanja sekarang!</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 fw-bold">
                        Daftar Item
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cart_items as $item_id => $item): ?>
                            <li class="list-group-item d-flex align-items-center py-3">
                                <div class="flex-shrink-0 me-3">
                                    <img src="<?php echo $base_url . htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="mb-1 text-muted small">Harga Satuan: $<?php echo number_format($item['price'], 2); ?></p>
                                    <div class="d-flex align-items-center mt-2">
                                        <label for="qty-<?php echo $item_id; ?>" class="form-label mb-0 me-2 small">Kuantitas:</label>
                                        <input type="number" id="qty-<?php echo $item_id; ?>" class="form-control form-control-sm w-25 update-cart-qty"
                                               data-product-id="<?php echo $item_id; ?>"
                                               value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                        <span class="ms-3 text-muted small">Stok: <?php echo $item['stock']; ?></span>
                                    </div>
                                </div>
                                <div class="text-end ms-auto">
                                    <p class="fs-5 fw-bold mb-1">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    <a href="cart.php?action=remove&product_id=<?php echo $item_id; ?>" class="btn btn-outline-danger btn-sm mt-2">Hapus</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 fw-bold">
                        Ringkasan Belanja
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Total Item:</span>
                            <span><?php echo count($cart_items); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-4 fw-bold">Total Harga:</span>
                            <span class="fs-4 fw-bold text-primary">$<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-money-check-alt me-2"></i> Lanjutkan ke Pembayaran
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">
                            Lanjutkan Belanja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>