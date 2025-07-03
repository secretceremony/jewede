<?php
// admin/users.php
$page_title = "Kelola Pengguna - Admin";
require_once __DIR__ . '/../includes/header.php';
protect_page('admin'); // Hanya admin yang bisa mengakses halaman ini

// Ambil semua data pengguna dari database
$users = [];
$sql_users = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$stmt_all_users = execute_query($sql_users);
$users = fetch_all($stmt_all_users);
$stmt_all_users->close();
?>

<div class="container my-5">
    <h1 class="mb-4 display-5 fw-bold text-center">Kelola Pengguna Sistem</h1>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 fw-bold">
            Daftar Semua Pengguna
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($user['role'] === 'admin') ? 'bg-primary' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" disabled>
                                        <i class="fas fa-info-circle"></i> Info
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>