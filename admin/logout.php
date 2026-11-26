<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

csrf_check();

$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
session_destroy();

redirect(admin_url('login.php'));
