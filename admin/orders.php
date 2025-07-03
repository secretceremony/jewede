<?php
$page_title = "Kelola Pesanan - Admin Guza";
require_once __DIR__ . '/../includes/header.php';
protect_page('admin'); // Hanya admin yang bisa mengakses halaman ini

$message = '';
$message_type = '';

// --- Logika Update Status Pesanan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // --- START DEBUGGING ADDITIONS ---
    // Log all POST data received
    error_log("Order Update Attempt: POST data received - " . print_r($_POST, true));
    // --- END DEBUGGING ADDITIONS ---

    $order_id = (int)sanitize_input($_POST['order_id']);
    $new_status = sanitize_input($_POST['new_status']);

    // --- START DEBUGGING ADDITIONS ---
    // Log the parsed order ID and new status
    error_log("Debug: Attempting to change order ID: {$order_id} to new status: '{$new_status}'");
    // --- END DEBUGGING ADDITIONS ---

    // Validasi status yang diizinkan
    $allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $message = "Status tidak valid.";
        $message_type = "danger";
        // --- START DEBUGGING ADDITIONS ---
        error_log("Debug: Validation failed - Invalid status '{$new_status}' provided for order ID {$order_id}.");
        // --- END DEBUGGING ADDITIONS ---
    } else {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = execute_query($sql, 'si', [$new_status, $order_id]);

        // It's critical that execute_query returns the statement object or false/null on failure.
        // If execute_query itself fails (e.g., database connection issue, SQL syntax error in your function),
        // $stmt might be false/null, and trying to access $stmt->affected_rows would cause a PHP error.
        if ($stmt === false || $stmt === null) {
            $message = "Terjadi kesalahan fatal saat mempersiapkan atau menjalankan query. Periksa log server.";
            $message_type = "danger";
            // --- START DEBUGGING ADDITIONS ---
            error_log("Debug: Fatal error encountered within execute_query for order ID {$order_id}. Check database connection and execute_query implementation.");
            // --- END DEBUGGING ADDITIONS ---
        } else if ($stmt->affected_rows > 0) {
            $message = "Status pesanan #{$order_id} berhasil diperbarui menjadi '" . ucfirst($new_status) . "'.";
            $message_type = "success";
            // --- START DEBUGGING ADDITIONS ---
            error_log("Debug: Order ID {$order_id} status successfully updated to '{$new_status}'. Affected rows: {$stmt->affected_rows}");
            // --- END DEBUGGING ADDITIONS ---
        } else {
            $message = "Gagal memperbarui status pesanan atau tidak ada perubahan.";
            $message_type = "info";
            // This is the message you've been seeing. Let's log more context.
            // --- START DEBUGGING ADDITIONS ---
            error_log("Debug: Order ID {$order_id} update reported 0 affected rows. New status '{$new_status}' might be same as current, or the ID was not found in the database.");
            // --- END DEBUGGING ADDITIONS ---
        }
        // Only close the statement if it's a valid mysqli_stmt object
        if ($stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }
    // Redirect to show the message. Make sure the redirect function is defined.
    redirect('orders.php?msg=' . urlencode($message) . '&type=' . $message_type);
}

// Ambil pesan dari redirect GET jika ada
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Ambil semua data pesanan dari database, beserta nama user
$orders = [];
$sql_orders = "SELECT o.id, o.total_price, o.status, o.created_at, u.name AS user_name, u.email AS user_email
                FROM orders o JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";
$stmt_all_orders = execute_query($sql_orders);

// Handle case where fetching all orders might fail
if ($stmt_all_orders === false) {
    // Log an error or display a user-friendly message if fetching orders fails
    $orders = []; // Ensure $orders is an empty array to prevent errors in the loop
    error_log("Error: Failed to fetch all orders from the database.");
    // Optionally set a user message like:
    // $message = "Gagal memuat daftar pesanan. Periksa koneksi database.";
    // $message_type = "danger";
} else {
    $orders = fetch_all($stmt_all_orders);
    $stmt_all_orders->close();
}
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Kelola Pesanan Pelanggan</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 fw-bold">
            Daftar Semua Pesanan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ordersTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada pesanan ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php
                                            switch ($order['status']) {
                                                case 'pending': echo 'bg-warning text-dark'; break;
                                                case 'processing': echo 'bg-info text-white'; break;
                                                case 'completed': echo 'bg-success text-white'; break;
                                                case 'cancelled': echo 'bg-danger text-white'; break;
                                                default: echo 'bg-secondary text-white'; break;
                                            }
                                        ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm me-2 view-detail-btn"
                                                data-bs-toggle="modal" data-bs-target="#orderDetailAdminModal"
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($order['user_name']); ?>"
                                                data-user-email="<?php echo htmlspecialchars($order['user_email']); ?>"
                                                data-total-price="<?php echo htmlspecialchars($order['total_price']); ?>"
                                                data-status="<?php echo htmlspecialchars($order['status']); ?>"
                                                data-created-at="<?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?>">
                                                <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm edit-status-btn"
                                                data-bs-toggle="modal" data-bs-target="#editOrderStatusModal"
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-current-status="<?php echo htmlspecialchars($order['status']); ?>">
                                                <i class="fas fa-sync-alt"></i> Status
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderDetailAdminModal" tabindex="-1" aria-labelledby="orderDetailAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="orderDetailAdminModalLabel">Detail Pesanan #<span id="modalOrderId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pelanggan:</strong> <span id="modalUserName"></span> (<span id="modalUserEmail"></span>)</p>
                <p><strong>Tanggal Pesan:</strong> <span id="modalCreatedAt"></span></p>
                <p><strong>Total Harga:</strong> <span class="fw-bold fs-5 text-primary">$<span id="modalTotalPrice"></span></span></p>
                <p><strong>Status:</strong> <span id="modalStatusBadge" class="badge"></span></p>

                <h6 class="mt-4">Item Pesanan:</h6>
                <ul class="list-group list-group-flush" id="modalOrderItemsList">
                    <li class="list-group-item text-center text-muted">Memuat item...</li>
                </ul>

                <h6 class="mt-4">Detail Proyek / Info Kontak Tambahan (diambil dari kolom shipping_address di tabel orders):</h6>
                <p class="mb-0 small text-muted">
                    * Untuk saat ini, kolom ini belum tersimpan di tabel `orders` secara eksplisit, Anda perlu menambahkannya jika ingin melihat *brief* lengkap di sini.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editOrderStatusModal" tabindex="-1" aria-labelledby="editOrderStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="orders.php" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="editStatusOrderId">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editOrderStatusModalLabel">Ubah Status Pesanan #<span id="editStatusModalOrderId"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Pilih Status Baru:</label>
                        <select class="form-select" id="newStatus" name="new_status" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Perbarui Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>