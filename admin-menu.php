<?php
require __DIR__ . '/session-config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
$statuses = ['Main', 'Side', 'Dessert', 'Drink', 'Starter'];
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $validImage = filter_var($imageUrl, FILTER_VALIDATE_URL) || str_starts_with($imageUrl, 'images/');

    if (!hash_equals($_SESSION['admin_csrf'], $token)) {
        $_SESSION['admin_menu_flash'] = ['type' => 'error', 'text' => 'Your session expired. Please submit again.'];
    } elseif ($name === '' || $description === '' || !in_array($category, $statuses, true) || $price < 10000 || !$validImage) {
        $_SESSION['admin_menu_flash'] = ['type' => 'error', 'text' => 'Enter a name, description, valid image path, category, and price of at least ₦10,000.'];
    } else {
        if ($itemId > 0) {
            $stmt = $conn->prepare('UPDATE menu_items SET name = ?, description = ?, category = ?, price = ?, image_url = ?, is_available = ? WHERE id = ?');
            $stmt->bind_param('sssdsii', $name, $description, $category, $price, $imageUrl, $isAvailable, $itemId);
        } else {
            $stmt = $conn->prepare('INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssdsi', $name, $description, $category, $price, $imageUrl, $isAvailable);
        }

        if ($stmt->execute()) {
            $_SESSION['admin_menu_flash'] = ['type' => 'success', 'text' => $itemId > 0 ? 'Menu item updated.' : 'Menu item added.'];
        } else {
            $_SESSION['admin_menu_flash'] = ['type' => 'error', 'text' => 'Could not save this item. Item names must be unique.'];
        }
    }

    header('Location: admin-menu.php');
    exit;
}

$result = $conn->query('SELECT id, name, description, category, price, image_url, is_available FROM menu_items ORDER BY category, name');
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$flash = $_SESSION['admin_menu_flash'] ?? null;
unset($_SESSION['admin_menu_flash']);
require __DIR__ . '/admin-layout.php';
admin_page_start('Menu management', 'menu');
?>
<?php if ($flash): ?><div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-head"><h2>Add menu item</h2><span class="sub">Prices must be at least ₦10,000</span></div>
    <div class="panel-body">
        <form class="form-grid" method="POST" action="admin-menu.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field"><label for="new-name">Item name</label><input id="new-name" name="name" maxlength="160" required></div>
            <div class="field"><label for="new-category">Category</label><select id="new-category" name="category" required><?php foreach ($statuses as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="new-price">Price (NGN)</label><input id="new-price" name="price" type="number" min="10000" step="1000" required></div>
            <div class="field"><label for="new-image">Image URL or images/ path</label><input id="new-image" name="image_url" type="text" placeholder="https://... or images/dish.jpg" required></div>
            <div class="field full"><label for="new-description">Description</label><textarea id="new-description" name="description" maxlength="1000" required></textarea></div>
            <div class="field full form-actions"><button class="button" type="submit">Add item</button></div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Current menu</h2><span class="sub"><?php echo count($items); ?> items</span></div>
    <div class="panel-body">
        <?php if (!$items): ?><p class="empty">No menu items found.</p><?php endif; ?>
        <div class="form-grid">
            <?php foreach ($items as $item): ?>
                <form class="panel panel-body" method="POST" action="admin-menu.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                    <div class="form-grid">
                        <div class="field"><label>Item name</label><input name="name" value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="160" required></div>
                        <div class="field"><label>Category</label><select name="category"><?php foreach ($statuses as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $item['category'] === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>Price (NGN)</label><input name="price" type="number" min="10000" step="1000" value="<?php echo (int)$item['price']; ?>" required></div>
                        <div class="field"><label>Image URL</label><input name="image_url" value="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                        <div class="field full"><label>Description</label><textarea name="description" maxlength="1000" required><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                        <div class="field full"><label><input type="checkbox" name="is_available" value="1" <?php echo $item['is_available'] ? 'checked' : ''; ?> style="width:auto;min-height:0;"> Available on public menu</label></div>
                        <div class="field full form-actions"><button class="button" type="submit">Save item</button><span class="sub">Current: ₦<?php echo number_format((float)$item['price']); ?></span></div>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php admin_page_end(); ?>