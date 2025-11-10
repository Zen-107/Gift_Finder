<?php
require_once 'db.php';

$message = '';

// ดึงข้อมูลที่มีอยู่
$categories_list = $interests_list = [];
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories_list = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    $int_stmt = $pdo->query("SELECT id, name FROM interests ORDER BY name");
    $interests_list = $int_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $price = $_POST['price'];
        $currency = $_POST['currency'] ?: 'THB';
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);

        if (empty($name)) throw new Exception("กรุณากรอกชื่อสินค้า");

        $pdo->beginTransaction();

        // 1. บันทึกสินค้า
        $external_url_main = !empty($_POST['external_urls']) ? trim($_POST['external_urls'][0]) : '';
        $stmt = $pdo->prepare("INSERT INTO products (name, price, currency, description, image_url, external_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $currency, $description, $image_url, $external_url_main]);
        $product_id = $pdo->lastInsertId();

        // 2. ลิงก์ร้านค้า
        if (!empty($_POST['external_urls']) && is_array($_POST['external_urls'])) {
            foreach ($_POST['external_urls'] as $url) {
                $url = trim($url);
                if (!empty($url)) {
                    $host = parse_url($url, PHP_URL_HOST);
                    $source_name = 'Unknown';
                    if ($host) {
                        if (strpos($host, 'shopee') !== false) $source_name = 'Shopee';
                        elseif (strpos($host, 'lazada') !== false) $source_name = 'Lazada';
                        elseif (strpos($host, 'jd') !== false) $source_name = 'JD Central';
                        else $source_name = ucfirst(str_replace(['.co.th', '.com', 'www.'], '', $host));
                    }
                    $stmt = $pdo->prepare("INSERT INTO product_external_urls (product_id, url, source_name) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $url, $source_name]);
                }
            }
        }

        // 3. จัดการหมวดหมู่: จาก checkbox + ชื่อใหม่
        $selected_category_ids = $_POST['category_ids'] ?? [];
        $new_categories = $_POST['new_categories'] ?? [];

        // เพิ่มหมวดหมู่ใหม่ (ถ้ามี)
        foreach ($new_categories as $cat_name) {
            $cat_name = trim($cat_name);
            if (!empty($cat_name)) {
                // ตรวจสอบว่ามีอยู่แล้วหรือไม่
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$cat_name]);
                $exists = $stmt->fetch();
                if ($exists) {
                    $cat_id = $exists['id'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$cat_name]);
                    $cat_id = $pdo->lastInsertId();
                }
                // เชื่อมโยง
                $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $cat_id]);
            }
        }

        // เชื่อมโยงหมวดหมู่ที่เลือกจาก checkbox
        foreach ($selected_category_ids as $cat_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, (int)$cat_id]);
        }

        // 4. จัดการ interests: เหมือนกัน
        $selected_interest_ids = $_POST['interest_ids'] ?? [];
        $new_interests = $_POST['new_interests'] ?? [];

        foreach ($new_interests as $int_name) {
            $int_name = trim($int_name);
            if (!empty($int_name)) {
                $stmt = $pdo->prepare("SELECT id FROM interests WHERE name = ?");
                $stmt->execute([$int_name]);
                $exists = $stmt->fetch();
                if ($exists) {
                    $int_id = $exists['id'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO interests (name) VALUES (?)");
                    $stmt->execute([$int_name]);
                    $int_id = $pdo->lastInsertId();
                }
                $stmt = $pdo->prepare("INSERT IGNORE INTO product_interests (product_id, interest_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $int_id]);
            }
        }

        foreach ($selected_interest_ids as $int_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO product_interests (product_id, interest_id) VALUES (?, ?)");
            $stmt->execute([$product_id, (int)$int_id]);
        }

        $pdo->commit();
        $message = "<div class='alert alert-success'>เพิ่มสินค้าเรียบร้อย!</div>";

    } catch (Exception $e) {
        $pdo->rollback();
        $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มสินค้า - Gift Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .checkbox-group { margin-bottom: 8px; }
        .new-item-input { margin-top: 8px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">➕ เพิ่มสินค้าใหม่</h2>

    <?= $message ?>

    <form method="POST" class="bg-white p-4 rounded shadow">
        <!-- === ข้อมูลสินค้าทั่วไป === -->
        <div class="mb-3">
            <label class="form-label">ชื่อสินค้า *</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">ราคา</label>
            <input type="number" step="0.01" name="price" class="form-control" value="0.00">
        </div>

        <div class="mb-3">
            <label class="form-label">สกุลเงิน</label>
            <input type="text" name="currency" class="form-control" value="THB" maxlength="10">
        </div>

        <div class="mb-3">
            <label class="form-label">คำอธิบาย</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">ลิงก์รูปภาพ (URL)</label>
            <input type="url" name="image_url" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">ลิงก์ไปยังร้านค้า</label>
            <input type="url" name="external_urls[]" class="form-control mb-2" placeholder="https://shopee.co.th/...">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addExternalUrlField()">+ เพิ่มลิงก์ร้านค้า</button>
        </div>

        <!-- === หมวดหมู่ === -->
        <div class="mb-3">
            <label class="form-label d-block">หมวดหมู่</label>
            <div class="form-check">
                <?php if (!empty($categories_list)): ?>
                    <?php foreach ($categories_list as $cat): ?>
                        <div class="checkbox-group">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?= htmlspecialchars($cat['id']) ?>" id="cat_<?= $cat['id'] ?>">
                            <label class="form-check-label" for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <em>ยังไม่มีหมวดหมู่</em>
                <?php endif; ?>
            </div>
            <div class="new-item-input">
                <label>เพิ่มหมวดหมู่ใหม่:</label>
                <input type="text" name="new_categories[]" class="form-control" placeholder="พิมพ์ชื่อหมวดหมู่ใหม่">
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addNewCategory()">+ เพิ่มอีก</button>
            </div>
        </div>

        <!-- === ความสนใจ === -->
        <div class="mb-3">
            <label class="form-label d-block">ความสนใจ</label>
            <div class="form-check">
                <?php if (!empty($interests_list)): ?>
                    <?php foreach ($interests_list as $int): ?>
                        <div class="checkbox-group">
                            <input class="form-check-input" type="checkbox" name="interest_ids[]" value="<?= htmlspecialchars($int['id']) ?>" id="int_<?= $int['id'] ?>">
                            <label class="form-check-label" for="int_<?= $int['id'] ?>"><?= htmlspecialchars($int['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <em>ยังไม่มีความสนใจ</em>
                <?php endif; ?>
            </div>
            <div class="new-item-input">
                <label>เพิ่มความสนใจใหม่:</label>
                <input type="text" name="new_interests[]" class="form-control" placeholder="พิมพ์ชื่อความสนใจใหม่">
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addNewInterest()">+ เพิ่มอีก</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">เพิ่มสินค้า</button>
        <a href="add_product.php" class="btn btn-secondary ms-2">รีเซ็ตฟอร์ม</a>
    </form>
</div>

<script>
function addExternalUrlField() {
    const container = document.querySelector('input[name="external_urls[]"]').parentElement;
    const newInput = document.createElement('input');
    newInput.type = 'url';
    newInput.name = 'external_urls[]';
    newInput.className = 'form-control mb-2';
    newInput.placeholder = 'https://example.com/product-xyz';
    container.appendChild(newInput);
}

function addNewCategory() {
    const container = document.querySelector('input[name="new_categories[]"]').parentElement;
    const newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.name = 'new_categories[]';
    newInput.className = 'form-control mt-1';
    newInput.placeholder = 'พิมพ์หมวดหมู่ใหม่';
    container.insertBefore(newInput, container.querySelector('button'));
}

function addNewInterest() {
    const container = document.querySelector('input[name="new_interests[]"]').parentElement;
    const newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.name = 'new_interests[]';
    newInput.className = 'form-control mt-1';
    newInput.placeholder = 'พิมพ์ความสนใจใหม่';
    container.insertBefore(newInput, container.querySelector('button'));
}
</script>
</body>
</html>