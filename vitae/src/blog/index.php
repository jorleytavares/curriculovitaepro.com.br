<?php
/**
 * Router para URLs /blog/xxx e /blog/categoria/post
 * Este arquivo captura requisições que o .htaccess não conseguiu rotear
 */

$requestUri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($requestUri, PHP_URL_PATH), '/');

// Remove "blog/" do início
$slug = preg_replace('#^blog/?#', '', $path);

if (empty($slug)) {
    // /blog/ -> redireciona para blog.php
    require __DIR__ . '/../blog.php';
    exit;
}

// Verifica se tem dois segmentos (categoria/post)
$segments = explode('/', $slug);

if (count($segments) >= 2) {
    // /blog/categoria/post -> passa category e slug
    $_GET['category'] = $segments[0];
    $_GET['slug'] = $segments[1];
    require __DIR__ . '/../blog_post.php';
    exit;
}

// Apenas um segmento -> fallback (pode ser categoria ou post)
$_GET['fallback_slug'] = $segments[0];
require __DIR__ . '/../blog_post.php';
