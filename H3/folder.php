<?php
// session_start(); ← ลบบรรทัดนี้ออก
include 'api/config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION["user_id"];
$folder_id = intval($_GET['id'] ?? 0);

if ($folder_id <= 0) {
    die("Folder ID is invalid.");
}

try {
    // ดึงข้อมูลโฟลเดอร์
    $stmt = $pdo->prepare("SELECT name FROM bookmark_folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$folder_id, $user_id]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$folder) {
        die("Folder not found or you don't have permission to access it.");
    }

    // ดึงข้อมูลบุ๊กมาร์กในโฟลเดอร์นี้ + ข้อมูลสินค้า + ราคาจาก external_urls
    $stmt = $pdo->prepare("
        SELECT 
            b.id as bookmark_id,
            b.product_id,
            b.custom_name,
            b.note,
            b.created_at,
            p.id,
            p.name,
            p.description,
            p.image_url,
            e.min_price,
            e.max_price,
            e.currency
        FROM bookmarks b
        JOIN products p ON b.product_id = p.id
        LEFT JOIN product_external_urls e ON p.id = e.product_id
        WHERE b.folder_id = ? AND b.user_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$folder_id, $user_id]);
    $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($folder['name']) ?> - Gift Finder</title>
    <link rel="stylesheet" href="assets/css/index&dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="container nav-content">
            <a class="logo" href="index.html">🎁 Gift Finder</a>
            <nav class="nav-links">
                <a href="form.html">Find Gifts</a>
                <a href="blog.html">Blog</a>
                <a href="results.html">Product</a>
                <a href="index.html">Home</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="section">
            <div class="container">
                <h2><?= htmlspecialchars($folder['name']) ?></h2>
                <?php if (count($bookmarks) > 0): ?>
                    <div class="grid cards">
                        <?php foreach ($bookmarks as $bookmark): ?>
                            <div class="card">
                                <img src="<?= htmlspecialchars($bookmark['image_url'] ?? 'https://via.placeholder.com/300x160') ?>" alt="<?= htmlspecialchars($bookmark['name']) ?>">
                                <div class="card-body">
                                    <div class="badge">Gift</div>
                                    <strong><?= htmlspecialchars($bookmark['custom_name'] ?? $bookmark['name']) ?></strong>
                                    <div class="price">
                                        <?php if ($bookmark['min_price'] !== null && $bookmark['max_price'] !== null): ?>
                                            <?php if ($bookmark['min_price'] == $bookmark['max_price']): ?>
                                                <?= number_format($bookmark['min_price'], 2) ?> <?= htmlspecialchars($bookmark['currency']) ?>
                                            <?php else: ?>
                                                <?= number_format($bookmark['min_price'], 2) ?> – <?= number_format($bookmark['max_price'], 2) ?> <?= htmlspecialchars($bookmark['currency']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            ราคาติดต่อ
                                        <?php endif; ?>
                                    </div>
                                    <div><?= htmlspecialchars(substr($bookmark['description'], 0, 80)) ?>...</div>
                                    <div class="stack">
                                        <a class="btn" href="product.php?id=<?= $bookmark['product_id'] ?>">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No bookmarks in this folder.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-copy">
                © 2025 Gift Finder
            </div>
        </div>
    </footer>
</body>
</html>