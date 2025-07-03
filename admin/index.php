<?php
// admin/index.php
$page_title = "Admin Dashboard - Guza";
require_once __DIR__ . '/../includes/header.php';
protect_page('admin'); // Hanya admin yang bisa mengakses halaman ini

// Logic untuk mengambil statistik atau ringkasan (opsional, bisa dikembangkan)
$total_products = 0;
$total_orders = 0;
$pending_orders = 0;
$completed_orders = 0;

// Get total products
$stmt_products = execute_query("SELECT COUNT(id) AS total FROM products");
$data_products = fetch_one($stmt_products);
$total_products = $data_products['total'];
$stmt_products->close();

// Get total orders
$stmt_orders_total = execute_query("SELECT COUNT(id) AS total FROM orders");
$data_orders_total = fetch_one($stmt_orders_total);
$total_orders = $data_orders_total['total'];
$stmt_orders_total->close();

// Get pending orders
$stmt_orders_pending = execute_query("SELECT COUNT(id) AS total FROM orders WHERE status = 'pending'");
$data_orders_pending = fetch_one($stmt_orders_pending);
$pending_orders = $data_orders_pending['total'];
$stmt_orders_pending->close();

// Get completed orders
$stmt_orders_completed = execute_query("SELECT COUNT(id) AS total FROM orders WHERE status = 'completed'");
$data_orders_completed = fetch_one($stmt_orders_completed);
$completed_orders = $data_orders_completed['total'];
$stmt_orders_completed->close();

?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Dashboard Admin</h1>

    <div class="alert alert-info text-center" role="alert">
        Selamat datang, Admin <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
        <div class="col">
            <div class="card h-100 shadow-sm border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-box-open fa-3x text-primary mb-3"></i>
                    <h5 class="card-title fw-bold">Total Paket Jasa</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $total_products; ?></p>
                </div>
                <div class="card-footer bg-light border-0">
                    <a href="products.php" class="btn btn-sm btn-outline-primary w-100">Kelola Paket Jasa</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-receipt fa-3x text-success mb-3"></i>
                    <h5 class="card-title fw-bold">Total Pesanan</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $total_orders; ?></p>
                </div>
                <div class="card-footer bg-light border-0">
                    <a href="orders.php" class="btn btn-sm btn-outline-success w-100">Lihat Semua Pesanan</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-hourglass-half fa-3x text-warning mb-3"></i>
                    <h5 class="card-title fw-bold">Pesanan Pending</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $pending_orders; ?></p>
                </div>
                <div class="card-footer bg-light border-0">
                    <a href="orders.php?status=pending" class="btn btn-sm btn-outline-warning w-100">Tinjau Pesanan Pending</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-info mb-3"></i>
                    <h5 class="card-title fw-bold">Pesanan Selesai</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $completed_orders; ?></p>
                </div>
                <div class="card-footer bg-light border-0">
                    <a href="orders.php?status=completed" class="btn btn-sm btn-outline-info w-100">Lihat Pesanan Selesai</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Akses Cepat</h5>
                    <div class="d-grid gap-2">
                        <a href="products.php" class="btn btn-lg btn-outline-secondary">
                            <i class="fas fa-palette me-2"></i> Kelola Ilustrasi
                        </a>
                        <a href="orders.php" class="btn btn-lg btn-outline-secondary">
                            <i class="fas fa-clipboard-list me-2"></i> Kelola Pesanan
                        </a>
                        <a href="users.php" class="btn btn-lg btn-outline-secondary">
                            <i class="fas fa-users me-2"></i> Kelola Pengguna
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>