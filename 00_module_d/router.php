<?php
require 'db.php';
$request_uri = $_SERVER['REQUEST_URI'];
$base_path_str = '00_module_d';
$pos = strpos($request_uri, $base_path_str);
if ($pos !== false) {
    $route = substr($request_uri, $pos + strlen($base_path_str));
} else {
    $route = '/';
}
$route = explode('?', $route)[0]; // strip query
$route = trim($route, '/');

if ($route === 'login') {
    require 'login.php';
} elseif ($route === 'logout') {
    session_destroy();
    header("Location: /00_module_d/login");
} elseif (strpos($route, 'books.json') === 0 || preg_match('/^books\/([^\/]+)\.json$/', $route)) {
    require 'api.php';
} elseif (preg_match('/^01\/(.+)$/', $route) || $route === 'verify' || preg_match('/^publisher\/(.+)$/', $route)) {
    require 'public.php';
} elseif (strpos($route, 'books') === 0) {
    require 'admin_books.php';
} elseif (strpos($route, 'publishers') === 0 || strpos($route, 'users') === 0) {
    require 'admin_publishers.php';
} else {
    // Default dashboard
    $user = getCurrentUser();
    if($user) {
        if ($user['role'] === 'super') {
            header("Location: /00_module_d/publishers");
        } else {
            header("Location: /00_module_d/books");
        }
    } else {
        header("Location: /00_module_d/login");
    }
}
