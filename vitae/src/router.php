<?php
// router.php for PHP built-in server
// Simulates .htaccess rewrites

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// 1. Serve static files if they exist
if ($uri !== '/' && file_exists($root . $uri)) {
    return false; // Let PHP serve the file
}

// 2. Specific Rewrites (Aliases)
$aliases = [
    '/entrar' => '/login.php',
    '/criar-conta' => '/register.php',
    '/painel' => '/dashboard.php',
    '/privacidade' => '/privacy.php',
    '/termos-de-uso' => '/terms.php',
    '/recuperar-senha' => '/forgot_password.php',
    '/minha-conta' => '/user_profile.php',
    '/sair' => '/logout.php',
    '/planos' => '/upgrade.php',
    '/magic-login' => '/magic_login.php',
    '/blog' => '/blog.php',
    '/editor' => '/editor.php',
    '/' => '/index.php'
];

if (isset($aliases[$uri])) {
    $_SERVER['SCRIPT_NAME'] = $aliases[$uri];
    require $root . $aliases[$uri];
    return;
}

// 3. Regex Rewrites

// Editor with ID: /editor/123
if (preg_match('#^/editor/([^/]+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/editor.php';
    require $root . '/editor.php';
    return;
}

// Blog Post: /blog/category/slug
if (preg_match('#^/blog/([^/]+)/([^/]+)$#', $uri, $matches)) {
    $_GET['category'] = $matches[1];
    $_GET['slug'] = $matches[2];
    $_SERVER['SCRIPT_NAME'] = '/blog_post.php';
    require $root . '/blog_post.php';
    return;
}

// Blog Category or Fallback: /blog/category
if (preg_match('#^/blog/([^/]+)$#', $uri, $matches)) {
    $_GET['fallback_slug'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/blog_post.php';
    require $root . '/blog_post.php';
    return;
}

// 4. Generic Fallback: /some-page -> /some-page.php
// Remove trailing slash if present for file check
$cleanUri = rtrim($uri, '/');
if (file_exists($root . $cleanUri . '.php')) {
    $_SERVER['SCRIPT_NAME'] = $cleanUri . '.php';
    require $root . $cleanUri . '.php';
    return;
}

// 5. 404
http_response_code(404);
$_SERVER['SCRIPT_NAME'] = '/404.php';
if (file_exists($root . '/404.php')) {
    require $root . '/404.php';
} else {
    echo "404 Not Found";
}
