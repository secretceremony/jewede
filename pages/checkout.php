<?php
$page_title = "Checkout";
require_once __DIR__ . '/../includes/header.php';
protect_page(); // Hanya user yang login yang bisa checkout

// Redirect jika keranjang kosong
if (empty($_SESSION['cart'])) {
    redirect('cart.php?msg=' . urlencode('Keranjang Anda kosong. Silakan tambahkan item sebelum checkout.') . '&type=warning');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Ambil data user dari database (untuk pre-fill form)
$sql_user = "SELECT name, email FROM users WHERE id = ?";
$stmt_user = execute_query($sql_user, 'i', [$user_id]);
$current_user = fetch_one($stmt_user);
$stmt_user->close();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_email = sanitize_input($_POST['customer_email']);
    $shipping_address = sanitize_input($_POST['shipping_address']); // Untuk ilustrasi digital, ini bisa jadi 'Contact Info' atau 'Project Brief Summary'
    $payment_method = sanitize_input($_POST['payment_method']); // Metode pembayaran yang dipilih

    // Validasi input
    if (empty($customer_name) || empty($customer_email) || empty($shipping_address) || empty($payment_method)) {
        $message = "Semua field wajib diisi.";
        $message_type = "danger";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $message_type = "danger";
    } else {
        // Hitung total harga dari keranjang
        $total_price = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        // --- Mulai Transaksi Database ---
        $conn->begin_transaction();
        try {
            // 1. Simpan ke tabel orders
            $sql_order = "INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')";
            $stmt_order = execute_query($sql_order, 'id', [$user_id, $total_price]);

            if ($stmt_order->affected_rows > 0) {
                $order_id = $stmt_order->insert_id;
                $stmt_order->close();

                // 2. Simpan item keranjang ke tabel order_items
                $all_items_saved = true;
                foreach ($_SESSION['cart'] as $item_id => $item) {
                    $sql_order_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $stmt_order_item = execute_query($sql_order_item, 'iiid', [$order_id, $item['id'], $item['quantity'], $item['price']]);

                    if ($stmt_order_item->affected_rows === 0) {
                        $all_items_saved = false;
                        break; // Keluar dari loop jika ada item yang gagal disimpan
                    }
                    $stmt_order_item->close();

                    // 3. Kurangi stok produk
                    // Asumsi: 'stock' pada products merepresentasikan slot yang tersedia untuk jasa
                    $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                    $stmt_update_stock = execute_query($sql_update_stock, 'ii', [$item['quantity'], $item['id']]);
                    if ($stmt_update_stock->affected_rows === 0) {
                        $all_items_saved = false;
                        break;
                    }
                    $stmt_update_stock->close();
                }

                if ($all_items_saved) {
                    $conn->commit(); // Commit transaksi jika semua berhasil
                    unset($_SESSION['cart']); // Kosongkan keranjang setelah pesanan berhasil
                    redirect('order_history.php?order_id=' . $order_id . '&msg=' . urlencode('Pesanan Anda berhasil dibuat! Silakan tunggu konfirmasi.') . '&type=success');
                } else {
                    $conn->rollback(); // Rollback jika ada yang gagal
                    $message = "Gagal menyimpan beberapa item pesanan atau mengurangi stok. Silakan coba lagi.";
                    $message_type = "danger";
                }
            } else {
                $conn->rollback(); // Rollback jika pesanan utama gagal
                $message = "Gagal membuat pesanan. Silakan coba lagi.";
                $message_type = "danger";
            }
        } catch (Exception $e) {
            $conn->rollback(); // Rollback jika ada exception
            $message = "Terjadi kesalahan sistem: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Ambil pesan dari redirect GET jika ada (dari cart.php misalnya)
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Data keranjang untuk ditampilkan
$cart_items = $_SESSION['cart'];
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Konfirmasi Pemesanan</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    Detail Kontak & Pembayaran
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email Kontak</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Detail Proyek</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="4" placeholder="Contoh: Deskripsi singkat ilustrasi yang diinginkan, atau informasi relevan lainnya untuk komunikasi proyek." required></textarea>
                            <div class="form-text">
                                Untuk jasa digital, ini bisa berisi ringkasan yang diinginkan.
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Metode Pembayaran</h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment_bank_transfer" value="Bank Transfer" required>
                            <label class="form-check-label" for="payment_bank_transfer">
                                Transfer Bank
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment_paypal" value="PayPal">
                            <label class="form-check-label" for="payment_paypal">
                               Cash on Delivery
                            </label>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100 mt-4">
                            <i class="fas fa-check-circle me-2"></i> Konfirmasi Pesanan & Bayar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    Item di Keranjang
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($cart_items as $item_id => $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <img src="<?php echo $base_url . htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <span class="fw-bold">$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold">Total Item:</span>
                        <span><?php echo count($cart_items); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-4 fw-bold">Grand Total:</span>
                        <span class="fs-4 fw-bold text-primary">$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>