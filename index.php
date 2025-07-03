<?php
ob_start();
session_start();

require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/index.php');
    } else {
        redirect('pages/dashboard.php');
    }
} else {
    redirect('pages/login.php');
}
?>