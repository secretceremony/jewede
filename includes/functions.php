<?php
// D:\_xampp\htdocs\JWD\includes\functions.php

// Fungsi untuk membersihkan input dari user (tanpa real_escape_string, karena kita pakai prepared statement)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk redirect halaman
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi untuk mengecek apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null; // Ensure user_id is set and not null
}

// Fungsi untuk mengecek apakah user adalah admin
function is_admin() {
    // Make sure you're storing the 'role' in $_SESSION as 'user_role' or directly 'role'
    // Based on previous discussions, it's often $_SESSION['user_role']
    // If your login script stores it as $_SESSION['role'], then this is fine.
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// --- START: Tambahkan fungsi protect_page() ini ---
/**
 * Protects a page by checking login status and optional role.
 * Redirects if conditions are not met.
 * @param string|null $required_role The role required to access the page (e.g., 'admin', 'user').
 */
function protect_page($required_role = null) {
    // Check if user is logged in
    if (!is_logged_in()) {
        $_SESSION['redirect_message'] = "Anda harus login untuk mengakses halaman ini.";
        $_SESSION['redirect_type'] = "warning";
        // Determine the correct redirect path based on current script location
        // This makes it work whether you're in /admin/ or /pages/
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $base_path = '/jwd/'; // Your base project path
        $login_path = $base_path . 'pages/login.php';

        if (strpos($current_dir, '/admin') !== false) { // If currently in an admin sub-directory
            redirect('../pages/login.php');
        } else {
            redirect($login_path); // Assume login is directly in pages/
        }
        exit(); // Always exit after a redirect
    }

    // If a specific role is required, check it
    if ($required_role !== null) {
        if (!is_admin() && $required_role === 'admin') { // If admin role is required but user is not admin
            $_SESSION['redirect_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
            $_SESSION['redirect_type'] = "danger";
            $current_dir = dirname($_SERVER['PHP_SELF']);
            $base_path = '/jwd/';
            $dashboard_path = $base_path . 'pages/dashboard.php';

            if (strpos($current_dir, '/admin') !== false) { // If currently in an admin sub-directory
                redirect('../pages/dashboard.php');
            } else {
                redirect($dashboard_path);
            }
            exit(); // Always exit after a redirect
        }
        // You can add more complex role checks here if needed, e.g., for 'user' role
        // if ($required_role === 'user' && !is_user_role()) { ... }
    }
}
// --- END: Tambahkan fungsi protect_page() ini ---


// Fungsi untuk mengeksekusi query SQL dengan prepared statements
function execute_query($sql, $types = '', $params = []) {
    global $conn;
    if (!$conn) {
        // This check is crucial if db_connect.php failed or wasn't included
        die("Error: Database connection not available. Please check db_connect.php");
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    if ($types && $params) {
        // Ensure params is an array of references if using bind_param directly with dynamic number of args
        // For PHP 5.6+, ...$params is fine for values
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Fungsi untuk mengambil satu baris hasil query
function fetch_one($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk mengambil semua baris hasil query
function fetch_all($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}