<?php
ob_start();
session_start();

$page_title = "Login";
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    if (is_admin()) {
        redirect('../admin/index.php');
    } else {
        redirect('dashboard.php');
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Email dan password harus diisi.";
    } else {
        $sql = "SELECT id, name, password, role FROM users WHERE email = ?";
        $stmt = execute_query($sql, 's', [$email]);
        $user = fetch_one($stmt);
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                redirect('../admin/index.php');
            } else {
                redirect('dashboard.php');
            }
        } else {
            $error_message = "Email atau password salah.";
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Login Akun</h2>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error']) && $_GET['error'] == 'login_required'): ?>
                        <div class="alert alert-warning" role="alert">
                            Anda harus login untuk mengakses halaman ini.
                        </div>
                    <?php endif; ?>
                     <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
                        <div class="alert alert-danger" role="alert">
                            Anda tidak memiliki akses ke halaman tersebut.
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3">Login</button>
                    </form>
                    <p class="text-center mt-3">Belum punya akun? <a href="register.php">Register di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>