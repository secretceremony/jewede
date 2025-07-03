<?php
// pages/dashboard.php
$page_title = "Shop - Guza";
require_once __DIR__ . '/../includes/header.php';

$products = [];
$stmt = execute_query("SELECT id, name, description, price, image_url FROM products");
$products = fetch_all($stmt);
$stmt->close();
?>

<section class="bg-light py-5 text-center mb-4">
    <div class="container">
        <h1 class="display-4 fw-bold">Shop</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-light p-2 rounded">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>pages/dashboard.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </nav>
    </div>
</section>

<div class="container my-5">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="<?php echo $base_url . htmlspecialchars($product['image_url']); ?>" class="card-img-top img-fluid rounded-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-primary fs-5 fw-bold">$<?php echo number_format($product['price'], 2); ?></p>
                            <div class="mt-auto"> <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm w-100">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="lead text-muted">Belum ada produk/jasa yang tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>