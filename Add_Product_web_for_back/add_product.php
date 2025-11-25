<?php
require_once 'db.php';

$message = '';

// ดึงข้อมูลที่มีอยู่
$categories_list = $interests_list = [];
$relationships_list = $genders_list = $age_ranges_list = $budgets_list = [];

try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories_list = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    $int_stmt = $pdo->query("SELECT id, name FROM interests ORDER BY name");
    $interests_list = $int_stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มส่วนนี้
    $rel_stmt = $pdo->query("SELECT id, display_name FROM relationships ORDER BY display_name");
    $relationships_list = $rel_stmt->fetchAll(PDO::FETCH_ASSOC);

    $gen_stmt = $pdo->query("SELECT id, display_name FROM genders ORDER BY display_name");
    $genders_list = $gen_stmt->fetchAll(PDO::FETCH_ASSOC);

    $age_stmt = $pdo->query("SELECT id, display_name FROM age_ranges ORDER BY display_name");
    $age_ranges_list = $age_stmt->fetchAll(PDO::FETCH_ASSOC);

    $bud_stmt = $pdo->query("SELECT id, name FROM budget_options ORDER BY display_order");
    $budgets_list = $bud_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);

        if (empty($name)) throw new Exception("กรุณากรอกชื่อสินค้า");

        $pdo->beginTransaction();

        // 1. บันทึกสินค้า (ไม่มี price, currency อีกต่อไป)
        $stmt = $pdo->prepare("INSERT INTO products (name, description, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $image_url]);
        $product_id = $pdo->lastInsertId();

        // 2. ลิงก์ร้านค้า + ช่วงราคา + label
        if (!empty($_POST['external_urls']) && is_array($_POST['external_urls'])) {
            foreach ($_POST['external_urls'] as $idx => $url) {
                $url = trim($url);
                if (empty($url)) continue;

                $label = trim($_POST['external_labels'][$idx] ?? '');
                $min_price = !empty($_POST['min_prices'][$idx]) ? (float)$_POST['min_prices'][$idx] : 0.0;
                $max_price = !empty($_POST['max_prices'][$idx]) ? (float)$_POST['max_prices'][$idx] : $min_price;
                $currency = trim($_POST['currencies'][$idx] ?? 'THB') ?: 'THB';

                // ตรวจสอบว่า min ≤ max
                if ($min_price > $max_price) {
                    throw new Exception("ช่วงราคาไม่ถูกต้อง: min ต้อง ≤ max");
                }

                // ดึง source_name จาก URL
                $host = parse_url($url, PHP_URL_HOST);
                $source_name = 'Unknown';
                if ($host) {
                    if (strpos($host, 'shopee') !== false) $source_name = 'Shopee';
                    elseif (strpos($host, 'lazada') !== false) $source_name = 'Lazada';
                    elseif (strpos($host, 'jd') !== false) $source_name = 'JD Central';
                    else $source_name = ucfirst(str_replace(['.co.th', '.com', 'www.'], '', $host));
                }

                $stmt = $pdo->prepare("INSERT INTO product_external_urls (product_id, url, source_name, min_price, max_price, currency) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$product_id, $url, $source_name, $min_price, $max_price, $currency]);
            }
        }

        // --- เพิ่มส่วนนี้ ---
        // หาช่วงราคาที่กว้างที่สุดจากที่ป้อนมา
        $min_overall = PHP_FLOAT_MAX;
        $max_overall = PHP_FLOAT_MIN;
        $has_price = false;

        if (!empty($_POST['external_urls']) && is_array($_POST['external_urls'])) {
            foreach ($_POST['external_urls'] as $idx => $url) {
                $url = trim($url);
                if (empty($url)) continue;

                $min_price = !empty($_POST['min_prices'][$idx]) ? (float)$_POST['min_prices'][$idx] : null;
                $max_price = !empty($_POST['max_prices'][$idx]) ? (float)$_POST['max_prices'][$idx] : null;

                if ($min_price !== null && $max_price !== null) {
                    $has_price = true;
                    $min_overall = min($min_overall, $min_price);
                    $max_overall = max($max_overall, $max_price);
                }
            }
        }

        // หากมีการป้อนราคา ให้หา budget ที่ตรงกับช่วงนี้ (เลือกตาม min_price)
        if ($has_price) {
            $stmt = $pdo->prepare("
                SELECT id FROM budget_options
                WHERE ? BETWEEN min_price AND max_price
                ORDER BY display_order
                LIMIT 1
            ");
            $stmt->execute([$min_overall]);
            $budget_match = $stmt->fetch();

            if ($budget_match) {
                $budget_id = $budget_match['id'];
                // บันทึกความเชื่อมโยง
                $stmt = $pdo->prepare("INSERT IGNORE INTO product_budgets (product_id, budget_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $budget_id]);
            }
        }
        // --- จบส่วนที่เพิ่ม ---

        // 3. จัดการหมวดหมู่
        $selected_category_ids = $_POST['category_ids'] ?? [];
        $new_categories = $_POST['new_categories'] ?? [];

        foreach ($new_categories as $cat_name) {
            $cat_name = trim($cat_name);
            if (!empty($cat_name)) {
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
                $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $cat_id]);
            }
        }

        foreach ($selected_category_ids as $cat_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, (int)$cat_id]);
        }

        // 4. จัดการ interests
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

        // --- เพิ่มส่วนนี้: จัดการ product_target_audiences ---
        $selected_relationship_ids = $_POST['target_relationship_ids'] ?? [];
        $selected_gender_ids = $_POST['target_gender_ids'] ?? [];
        $selected_age_range_ids = $_POST['target_age_range_ids'] ?? [];

        // สร้าง Cartesian product ของทั้งสามค่า (เช่น ถ้าเลือก 2 ความสัมพันธ์, 2 เพศ, 2 อายุ จะได้ 2x2x2 = 8 แถว)
        foreach ($selected_relationship_ids as $rel_id) {
            foreach ($selected_gender_ids as $gen_id) {
                foreach ($selected_age_range_ids as $age_id) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO product_target_audiences (product_id, relationship_id, gender_id, age_range_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$product_id, (int)$rel_id, (int)$gen_id, (int)$age_id]);
                }
            }
        }
        // --- จบส่วนที่เพิ่ม ---

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
        .external-url-group { border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9; }
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
            <label class="form-label">คำอธิบาย</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">ลิงก์รูปภาพ (URL)</label>
            <input type="url" name="image_url" class="form-control">
        </div>

        <!-- === ลิงก์ร้านค้า + ช่วงราคา === -->
        <div class="mb-4">
            <label class="form-label d-block">ลิงก์ร้านค้าและช่วงราคา</label>
            <div id="external-url-container">
                <!-- กลุ่มแรก -->
                <div class="external-url-group">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label">URL ร้านค้า *</label>
                            <input type="url" name="external_urls[]" class="form-control" placeholder="https://shopee.co.th/..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ป้ายชื่อร้าน (เช่น "Shopee Official")</label>
                            <input type="text" name="external_labels[]" class="form-control" placeholder="ไม่จำเป็น">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">ราคาต่ำสุด</label>
                            <input type="number" step="0.01" name="min_prices[]" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ราคาสูงสุด</label>
                            <input type="number" step="0.01" name="max_prices[]" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">สกุลเงิน</label>
                            <input type="text" name="currencies[]" class="form-control" value="THB" maxlength="10">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addExternalUrlGroup()">+ เพิ่มร้านค้าอีก</button>
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

        <!-- === เพิ่มส่วนนี้: กลุ่มเป้าหมาย (Target Audiences) === -->
        <div class="mb-4">
            <label class="form-label d-block">กลุ่มเป้าหมาย</label>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">ความสัมพันธ์</label>
                    <?php if (!empty($relationships_list)): ?>
                        <?php foreach ($relationships_list as $rel): ?>
                            <div class="checkbox-group">
                                <input class="form-check-input" type="checkbox" name="target_relationship_ids[]" value="<?= htmlspecialchars($rel['id']) ?>" id="rel_<?= $rel['id'] ?>">
                                <label class="form-check-label" for="rel_<?= $rel['id'] ?>"><?= htmlspecialchars($rel['display_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>ยังไม่มีข้อมูล</em>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เพศ</label>
                    <?php if (!empty($genders_list)): ?>
                        <?php foreach ($genders_list as $gen): ?>
                            <div class="checkbox-group">
                                <input class="form-check-input" type="checkbox" name="target_gender_ids[]" value="<?= htmlspecialchars($gen['id']) ?>" id="gen_<?= $gen['id'] ?>">
                                <label class="form-check-label" for="gen_<?= $gen['id'] ?>"><?= htmlspecialchars($gen['display_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>ยังไม่มีข้อมูล</em>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ช่วงอายุ</label>
                    <?php if (!empty($age_ranges_list)): ?>
                        <?php foreach ($age_ranges_list as $age): ?>
                            <div class="checkbox-group">
                                <input class="form-check-input" type="checkbox" name="target_age_range_ids[]" value="<?= htmlspecialchars($age['id']) ?>" id="age_<?= $age['id'] ?>">
                                <label class="form-check-label" for="age_<?= $age['id'] ?>"><?= htmlspecialchars($age['display_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>ยังไม่มีข้อมูล</em>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- === จบส่วนที่เพิ่ม === -->

        <button type="submit" class="btn btn-primary">เพิ่มสินค้า</button>
        <a href="add_product.php" class="btn btn-secondary ms-2">รีเซ็ตฟอร์ม</a>
    </form>
</div>

<script>
function addExternalUrlGroup() {
    const container = document.getElementById('external-url-container');
    const newGroup = document.createElement('div');
    newGroup.className = 'external-url-group';
    newGroup.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <label class="form-label">URL ร้านค้า *</label>
                <input type="url" name="external_urls[]" class="form-control" placeholder="https://example.com/..." required>
            </div>
            <div class="col-md-6">
                <label class="form-label">ป้ายชื่อร้าน</label>
                <input type="text" name="external_labels[]" class="form-control" placeholder="เช่น 'Lazada Mall'">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">ราคาต่ำสุด</label>
                <input type="number" step="0.01" name="min_prices[]" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label">ราคาสูงสุด</label>
                <input type="number" step="0.01" name="max_prices[]" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label">สกุลเงิน</label>
                <input type="text" name="currencies[]" class="form-control" value="THB" maxlength="10">
            </div>
        </div>
    `;
    container.appendChild(newGroup);
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