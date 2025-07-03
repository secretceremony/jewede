<?php
// admin/products.php
$page_title = "Kelola Paket Jasa - Admin";
require_once __DIR__ . '/../includes/header.php';
protect_page('admin'); // Hanya admin yang bisa mengakses halaman ini

$message = '';
$message_type = '';

// --- Logika Tambah/Edit Produk ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['id']) ? (int)sanitize_input($_POST['id']) : 0;
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = (float)sanitize_input($_POST['price']);
    $stock = (int)sanitize_input($_POST['stock']);
    $current_image_url = sanitize_input($_POST['current_image_url'] ?? ''); // Untuk edit

    // Validasi input
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $message = "Nama, Deskripsi, Harga, dan Stok tidak boleh kosong atau tidak valid.";
        $message_type = "danger";
    } else {
        $image_url = $current_image_url; // Default ke gambar yang sudah ada

        // Penanganan upload gambar
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = __DIR__ . "/../assets/img/";
            $file_name = uniqid() . '_' . basename($_FILES["image"]["name"]); // Nama unik untuk file
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Cek format gambar
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) {
                $message = "File bukan gambar.";
                $message_type = "danger";
            } elseif ($_FILES["image"]["size"] > 5000000) { // 5MB max
                $message = "Ukuran gambar terlalu besar.";
                $message_type = "danger";
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $message = "Hanya format JPG, JPEG, PNG & GIF yang diizinkan.";
                $message_type = "danger";
            } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = "assets/img/" . $file_name;
                // Jika ini mode edit dan ada gambar lama, hapus gambar lama
                if ($product_id > 0 && !empty($current_image_url) && $current_image_url !== $image_url) {
                    $old_image_path = __DIR__ . '/../' . $current_image_url;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            } else {
                $message = "Gagal mengupload gambar.";
                $message_type = "danger";
            }
        }

        if ($message_type !== "danger") { // Lanjutkan hanya jika tidak ada error gambar
            if ($product_id > 0) { // Mode Edit
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE id = ?";
                $stmt = execute_query($sql, 'ssdisi', [$name, $description, $price, $stock, $image_url, $product_id]);
                if ($stmt->affected_rows > 0) {
                    $message = "Paket jasa berhasil diperbarui!";
                    $message_type = "success";
                } else {
                    $message = "Tidak ada perubahan atau gagal memperbarui paket jasa.";
                    $message_type = "info"; // Bisa info jika tidak ada perubahan
                }
            } else { // Mode Tambah
                $sql = "INSERT INTO products (name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?)";
                $stmt = execute_query($sql, 'ssdis', [$name, $description, $price, $stock, $image_url]);
                if ($stmt->affected_rows > 0) {
                    $message = "Paket jasa berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menambahkan paket jasa.";
                    $message_type = "danger";
                }
            }
            $stmt->close();
        }
    }
}

// --- Logika Hapus Produk ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id = (int)sanitize_input($_GET['id']);

    // Ambil path gambar sebelum dihapus dari DB
    $stmt_img = execute_query("SELECT image_url FROM products WHERE id = ?", 'i', [$product_id]);
    $product_to_delete = fetch_one($stmt_img);
    $stmt_img->close();

    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = execute_query($sql, 'i', [$product_id]);

    if ($stmt->affected_rows > 0) {
        $message = "Paket jasa berhasil dihapus!";
        $message_type = "success";
        // Hapus file gambar juga
        if ($product_to_delete && !empty($product_to_delete['image_url'])) {
            $image_path = __DIR__ . '/../' . $product_to_delete['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    } else {
        $message = "Gagal menghapus paket jasa atau paket jasa tidak ditemukan.";
        $message_type = "danger";
    }
    $stmt->close();
    redirect('products.php?msg=' . urlencode($message) . '&type=' . $message_type);
}

// Ambil pesan dari redirect GET jika ada
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Ambil semua data produk untuk ditampilkan
$products = [];
$stmt_all_products = execute_query("SELECT id, name, price, stock, image_url, description FROM products ORDER BY created_at DESC"); // Added 'description' to the select query
$products = fetch_all($stmt_all_products);
$stmt_all_products->close();
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Kelola Paket Jasa Ilustrasi</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus-circle me-2"></i> Tambah Paket Jasa Baru
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 fw-bold">
            Daftar Paket Jasa
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="productsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Nama Paket</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td>
                                    <img src="<?php echo $base_url . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid rounded" style="width: 70px; height: 70px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm me-2 edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editProductModal"
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                            data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                            data-stock="<?php echo htmlspecialchars($product['stock']); ?>"
                                            data-image-url="<?php echo htmlspecialchars($product['image_url']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="products.php?action=delete&id=<?php echo $product['id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus paket jasa ini?');">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="products.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addProductModalLabel">Tambah Paket Jasa Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addName" class="form-label">Nama Paket Jasa</label>
                        <input type="text" class="form-control" id="addName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addDescription" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="addDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="addPrice" class="form-label">Harga ($)</label>
                        <input type="number" step="0.01" class="form-control" id="addPrice" name="price" required min="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="addStock" class="form-label">Stok / Slot Ketersediaan</label>
                        <input type="number" class="form-control" id="addStock" name="stock" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="addImage" class="form-label">Gambar Contoh</label>
                        <input class="form-control" type="file" id="addImage" name="image" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Paket Jasa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="products.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editProductId">
                <input type="hidden" name="current_image_url" id="editCurrentImageUrl">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Paket Jasa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Nama Paket Jasa</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editPrice" class="form-label">Harga ($)</label>
                        <input type="number" step="0.01" class="form-control" id="editPrice" name="price" required min="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="editStock" class="form-label">Stok / Slot Ketersediaan</label>
                        <input type="number" class="form-control" id="editStock" name="stock" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="editImage" class="form-label">Gambar Contoh (biarkan kosong jika tidak berubah)</label>
                        <input class="form-control" type="file" id="editImage" name="image" accept="image/*">
                        <div class="mt-2" id="currentImagePreview">
                            Gambar Saat Ini: <img src="" alt="Current Image" class="img-thumbnail" style="max-width: 100px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Perbarui Paket Jasa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>