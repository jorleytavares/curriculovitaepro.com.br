<?php
require_once __DIR__ . '/config/database.php';

header("Content-Type: application/xml; charset=utf-8");

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>Blog - Currículo Vitae Pro</title>
    <link><?php echo $baseUrl; ?>/blog.php</link>
    <description>Estratégias de carreira, preparação para entrevistas e as melhores dicas para o seu currículo.</description>
    <language>pt-br</language>
    <atom:link href="<?php echo $baseUrl; ?>/rss.php" rel="self" type="application/rss+xml" />
    
    <?php
    try {
        $stmt = $pdo->query("SELECT title, slug, excerpt, content, cover_image, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 20");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $link = "{$baseUrl}/blog/{$row['slug']}";
            $pubDate = date(DATE_RSS, strtotime($row['created_at']));
            $description = htmlspecialchars($row['excerpt'] ? $row['excerpt'] : strip_tags(mb_substr($row['content'], 0, 200) . '...'));
            
            // Se houver imagem de capa, podemos incluir no description (alguns leitores suportam)
            if (!empty($row['cover_image'])) {
                 $imgUrl = (strpos($row['cover_image'], 'http') === 0) ? $row['cover_image'] : $baseUrl . $row['cover_image'];
                 $description = "&lt;img src=&quot;$imgUrl&quot; /&gt;&lt;br/&gt;" . $description;
            }

            echo "
    <item>
        <title>" . htmlspecialchars($row['title']) . "</title>
        <link>{$link}</link>
        <description>{$description}</description>
        <pubDate>{$pubDate}</pubDate>
        <guid isPermaLink=\"true\">{$link}</guid>
    </item>";
        }
    } catch (PDOException $e) {}
    ?>
</channel>
</rss>
