<?php

function protect_page($role_required = 'user') {
    if (!is_logged_in()) {
        redirect('login.php?error=login_required');
    }

    if ($role_required === 'admin' && !is_admin()) {
        redirect('../pages/dashboard.php?error=access_denied');
    }
}
?>