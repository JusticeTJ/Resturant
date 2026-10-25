<?php
require __DIR__ . '/session-config.php';
$_SESSION = [];
session_unset();
session_destroy();
header('Location: admin-login.php');
exit;
