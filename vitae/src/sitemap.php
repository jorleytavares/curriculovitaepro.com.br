<?php
require_once __DIR__ . '/config/database.php';

// Define cabeçalho XML
header("Content-Type: application/xml; charset=utf-8");

// Detecta protocolo e host dinamicamente
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Início do XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Páginas Estáticas Principais -->
    <url>
        <loc><?php echo $baseUrl; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/register.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/blog.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Posts do Blog (Dinâmico) -->
    <?php
    try {
        $stmt = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastMod = date('Y-m-d', strtotime($row['updated_at']));
            echo "
    <url>
        <loc>{$baseUrl}/blog/{$row['slug']}</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>";
        }
    } catch (PDOException $e) {
        // Erro silencioso
    }
    ?>
</urlset>
