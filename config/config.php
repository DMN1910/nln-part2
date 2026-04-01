<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/camerashop/public');
date_default_timezone_set('Asia/Ho_Chi_Minh');
