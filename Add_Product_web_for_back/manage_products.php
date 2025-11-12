<?php
require_once 'db.php';

// ดึงสินค้าทั้งหมด พร้อมช่วงราคาต่ำสุด-สูงสุดจากทุกลิงก์ร้านค้า
$stmt = $pdo->prepare("
    SELECT 
        p.id, 
        p.name, 
        p.description, 
        p.image_url,
        COUNT(DISTINCT pc.category_id) AS category_count,
        COUNT(DISTINCT pe.id) AS url_count,
        MIN(pe.min_price) AS min_price_overall,
        MAX(pe.max_price) AS max_price_overall,
        -- ถ้ามีหลายสกุลเงิน อาจแสดงแค่ค่าแรก หรือรวมเป็น THB เท่านั้น (สมมติว่าทุกอย่างเป็น THB)
        'THB' AS currency
    FROM products p
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN product_external_urls pe ON p.id = pe.product_id
    GROUP BY p.id
    ORDER BY p.id DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสินค้า - Gift Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📦 จัดการสินค้า</h2>
        <a href="add_product.php" class="btn btn-success">➕ เพิ่มสินค้าใหม่</a>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">ยังไม่มีสินค้าในระบบ</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>รูป</th>
                        <th>ชื่อสินค้า</th>
                        <th>ช่วงราคา</th> <!-- เปลี่ยนชื่อหัวข้อ -->
                        <th>หมวดหมู่</th>
                        <th>ลิงก์ร้านค้า</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['id']) ?></td>
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="รูปสินค้า" width="50" class="rounded">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>
                                <?php if ($p['url_count'] > 0): ?>
                                    <?php 
                                        $min = $p['min_price_overall'];
                                        $max = $p['max_price_overall'];
                                        if ($min == $max) {
                                            echo number_format($min, 2) . ' ' . htmlspecialchars($p['currency']);
                                        } else {
                                            echo number_format($min, 2) . ' – ' . number_format($max, 2) . ' ' . htmlspecialchars($p['currency']);
                                        }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['category_count'] ?></td>
                            <td><?= $p['url_count'] ?></td>
                            <td>
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">✏️ แก้ไข</a>
                                <a href="delete_product.php?id=<?= $p['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('คุณแน่ใจหรือว่าจะลบสินค้านี้?\nการกระทำนี้ไม่สามารถยกเลิกได้!');">🗑️ ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <!--
    <a href="index.html" class="btn btn-outline-secondary mt-3">กลับหน้าหลัก</a>
    -->
</div>
</body>
</html>