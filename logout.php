<?php
// logout.php - Log out user session and redirect to login.php
require_once __DIR__ . '/auth.php';

logoutUser();

header('Location: login.php?logged_out=1');
exit;
