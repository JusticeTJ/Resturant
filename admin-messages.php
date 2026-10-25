<?php
require __DIR__ . '/session-config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $messageId = (int)($_POST['message_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $token) || $messageId < 1 || !in_array($status, ['New', 'Read'], true)) {
        $_SESSION['message_flash'] = ['type' => 'error', 'text' => 'Invalid message update. Please try again.'];
    } else {
        $stmt = $conn->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $messageId);
        $_SESSION['message_flash'] = $stmt->execute()
            ? ['type' => 'success', 'text' => 'Message status updated.']
            : ['type' => 'error', 'text' => 'Could not update this message.'];
    }
    header('Location: admin-messages.php');
    exit;
}

$result = $conn->query('SELECT id, name, email, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC');
$allMessages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$validFilter = in_array($statusFilter, ['New', 'Read'], true) ? $statusFilter : '';
$messages = array_values(array_filter($allMessages, static function ($message) use ($search, $validFilter) {
    $matchesSearch = $search === '' || stripos($message['name'] . ' ' . $message['email'] . ' ' . $message['subject'] . ' ' . $message['message'], $search) !== false;
    return $matchesSearch && ($validFilter === '' || $message['status'] === $validFilter);
}));
$newCount = count(array_filter($allMessages, static fn($message) => $message['status'] === 'New'));
$flash = $_SESSION['message_flash'] ?? null;
unset($_SESSION['message_flash']);
require __DIR__ . '/admin-layout.php';
admin_page_start('Messages', 'messages');
?>
<?php if ($flash): ?><div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<section class="stats">
    <article class="stat"><span>Total messages</span><strong><?php echo count($allMessages); ?></strong></article>
    <article class="stat"><span>New messages</span><strong><?php echo $newCount; ?></strong></article>
    <article class="stat"><span>Read messages</span><strong><?php echo count($allMessages) - $newCount; ?></strong></article>
</section>
<section class="panel">
    <div class="panel-head"><h2>Customer inbox</h2><span class="sub"><?php echo count($messages); ?> shown</span></div>
    <form class="form-grid panel-body" method="GET" action="admin-messages.php">
        <div class="field"><label for="message-search">Search inbox</label><input id="message-search" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name, email, subject, or message"></div>
        <div class="field"><label for="message-status">Status</label><select id="message-status" name="status"><option value="">All messages</option><option value="New" <?php echo $validFilter === 'New' ? 'selected' : ''; ?>>New</option><option value="Read" <?php echo $validFilter === 'Read' ? 'selected' : ''; ?>>Read</option></select></div>
        <div class="field full form-actions"><button class="button" type="submit">Filter inbox</button></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Received</th><th>From</th><th>Subject and message</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (!$messages): ?><tr><td colspan="5" class="empty">No messages match this view.</td></tr><?php endif; ?>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($message['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars($message['name'], ENT_QUOTES, 'UTF-8'); ?></strong><br><a href="mailto:<?php echo htmlspecialchars($message['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message['email'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                        <td><strong><?php echo htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8'); ?></strong><p style="max-width:520px;white-space:pre-wrap;color:var(--muted);line-height:1.5;"><?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?></p></td>
                        <td><span class="status status-<?php echo strtolower(htmlspecialchars($message['status'], ENT_QUOTES, 'UTF-8')); ?>"><?php echo htmlspecialchars($message['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><form method="POST" class="form-actions"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="message_id" value="<?php echo (int)$message['id']; ?>"><button class="button secondary" type="submit" name="status" value="<?php echo $message['status'] === 'New' ? 'Read' : 'New'; ?>">Mark <?php echo $message['status'] === 'New' ? 'read' : 'new'; ?></button></form></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_page_end(); ?>