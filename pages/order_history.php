<?php
// pages/order_history.php
$page_title = "Riwayat Pesanan - Guza";
require_once __DIR__ . '/../includes/header.php';
protect_page(); // Hanya user yang login yang bisa melihat riwayat pesanan

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Ambil pesan dari redirect GET jika ada (dari checkout.php misalnya)
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Ambil semua pesanan untuk user yang sedang login, diurutkan berdasarkan tanggal terbaru
$sql_orders = "SELECT id, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt_orders = execute_query($sql_orders, 'i', [$user_id]);
$orders = fetch_all($stmt_orders);
$stmt_orders->close();
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Riwayat Pesanan Anda</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center" role="alert">
            Anda belum memiliki riwayat pesanan. <a href="dashboard.php" class="alert-link">Mulai belanja sekarang!</a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 fw-bold">
                        Daftar Pesanan
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($orders as $order): ?>
                            <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center py-3">
                                <div class="mb-2 mb-md-0">
                                    <h5 class="mb-1 fw-bold">Pesanan #<?php echo htmlspecialchars($order['id']); ?></h5>
                                    <p class="text-muted small mb-0">Tanggal: <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
                                    <p class="text-muted small mb-0">Total: <span class="fw-bold">$<?php echo number_format($order['total_price'], 2); ?></span></p>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge <?php
                                        // Menentukan warna badge berdasarkan status
                                        switch ($order['status']) {
                                            case 'pending': echo 'bg-warning text-dark'; break;
                                            case 'processing': echo 'bg-info text-white'; break;
                                            case 'completed': echo 'bg-success text-white'; break;
                                            case 'cancelled': echo 'bg-danger text-white'; break;
                                            default: echo 'bg-secondary text-white'; break;
                                        }
                                    ?> me-3"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#orderDetailModal<?php echo $order['id']; ?>">
                                        Lihat Detail
                                    </button>
                                </div>
                            </li>

                            <div class="modal fade" id="orderDetailModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="orderDetailModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderDetailModalLabel<?php echo $order['id']; ?>">Detail Pesanan #<?php echo htmlspecialchars($order['id']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <h6>Status: <span class="badge <?php
                                                switch ($order['status']) {
                                                    case 'pending': echo 'bg-warning text-dark'; break;
                                                    case 'processing': echo 'bg-info text-white'; break;
                                                    case 'completed': echo 'bg-success text-white'; break;
                                                    case 'cancelled': echo 'bg-danger text-white'; break;
                                                    default: echo 'bg-secondary text-white'; break;
                                                }
                                            ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></h6>
                                            <p><strong>Tanggal Pesan:</strong> <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
                                            <p><strong>Total Harga:</strong> <span class="fw-bold">$<?php echo number_format($order['total_price'], 2); ?></span></p>

                                            <h6 class="mt-4">Item Pesanan:</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                // Ambil item pesanan untuk order ini
                                                $sql_order_items = "SELECT oi.quantity, oi.price, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
                                                $stmt_order_items = execute_query($sql_order_items, 'i', [$order['id']]);
                                                $order_items = fetch_all($stmt_order_items);
                                                $stmt_order_items->close();

                                                foreach ($order_items as $item_detail):
                                                ?>
                                                    <li class="list-group-item d-flex align-items-center py-2">
                                                        <img src="<?php echo $base_url . htmlspecialchars($item_detail['image_url']); ?>" alt="<?php echo htmlspecialchars($item_detail['name']); ?>" class="img-fluid rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <div class="flex-grow-1">
                                                            <?php echo htmlspecialchars($item_detail['name']); ?> x <?php echo htmlspecialchars($item_detail['quantity']); ?>
                                                        </div>
                                                        <span class="fw-bold">$<?php echo number_format($item_detail['price'] * $item_detail['quantity'], 2); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <h6 class="mt-4">Info Kontak & Proyek:</h6>
                                            <p class="text-muted small">
                                                Detail spesifik proyek dan komunikasi lebih lanjut akan dilakukan langsung oleh seniman/admin.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>