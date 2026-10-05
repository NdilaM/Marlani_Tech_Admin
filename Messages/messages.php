<?php
// Messages/messages.php - Inbox
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$me = (int)$_SESSION['staff_id'];

/* Latest message per sender (inbox) */
$stmt = $conn->prepare("
    SELECT m.*, s.first_name, s.last_name,
           (SELECT COUNT(*) FROM messages x
            WHERE x.sender_id = m.sender_id
              AND x.recipient_id = :me
              AND x.is_read = 0) AS unread
    FROM messages m
    JOIN staff s ON s.id = m.sender_id
    WHERE m.recipient_id = :me2
      AND m.id = (
          SELECT MAX(id) FROM messages
          WHERE sender_id = m.sender_id AND recipient_id = :me3
      )
    ORDER BY m.created_at DESC
");
$stmt->execute([':me' => $me, ':me2' => $me, ':me3' => $me]);
$inbox = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* All other staff for compose dropdown */
$stmt = $conn->prepare("SELECT id, first_name, last_name FROM staff WHERE id <> ? ORDER BY first_name");
$stmt->execute([$me]);
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

function time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60)    return $d . 's ago';
    if ($d < 3600)  return floor($d/60) . 'm ago';
    if ($d < 86400) return floor($d/3600) . 'h ago';
    return floor($d/86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Messages</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Nunito,sans-serif;background:#f8f9fc;display:flex;min-height:100vh;color:#5a5c69}
#sidebar-container{display:flex;flex-shrink:0;height:100vh;position:sticky;top:0;align-self:flex-start}
.main{flex:1;padding:2rem;min-width:0}
h1{font-size:1.5rem;margin-bottom:1.5rem;color:#2d3748}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.card h2{padding:1rem 1.25rem;font-size:.85rem;text-transform:uppercase;color:#a0aec0;background:#fafbff;border-bottom:1px solid #eef1f7;font-weight:700}
.row{display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid #eef1f7;text-decoration:none;color:inherit;transition:background .15s}
.row:last-child{border-bottom:none}
.row:hover{background:#f0f4ff}
.avatar{width:42px;height:42px;border-radius:50%;background:#003986;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
.meta{flex:1;min-width:0}
.name{font-weight:700;color:#2d3748}
.preview{color:#718096;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.time{font-size:.75rem;color:#a0aec0;margin-top:.2rem}
.badge{background:#e74a3b;color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:20px}
.empty{padding:2.5rem;text-align:center;color:#a0aec0}
.compose{position:fixed;right:2rem;bottom:2rem;background:#003986;color:#fff;border:none;border-radius:50px;padding:.9rem 1.5rem;font-weight:700;cursor:pointer;box-shadow:0 8px 20px -6px rgba(0,57,134,.5);font-size:.95rem;font-family:inherit}
.compose:hover{background:#002a66}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:999}
.modal.show{display:flex}
.modal-box{background:#fff;border-radius:12px;padding:1.5rem;width:90%;max-width:450px}
.modal-box h3{margin-bottom:1rem;color:#2d3748}
select,textarea{width:100%;padding:.7rem;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:1rem;font-family:inherit;font-size:.95rem;color:#2d3748}
select:focus,textarea:focus{outline:none;border-color:#003986;box-shadow:0 0 0 3px rgba(0,57,134,.15)}
textarea{min-height:120px;resize:vertical}
.modal-actions{display:flex;justify-content:flex-end;gap:.5rem}
.btn{padding:.6rem 1.2rem;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-size:.9rem;font-family:inherit}
.btn-cancel{background:#eaecf4;color:#5a5c69}
.btn-send{background:#003986;color:#fff}
.btn-send:hover{background:#002a66}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-envelope"></i> Messages</h1>

    <div class="card">
        <h2>Inbox</h2>
        <?php if (empty($inbox)): ?>
            <div class="empty">
                <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                No messages yet.
            </div>
        <?php else: foreach ($inbox as $m):
            $name = $m['first_name'] . ' ' . $m['last_name'];
        ?>
            <a class="row" href="message-view.php?with=<?= (int)$m['sender_id'] ?>">
                <div class="avatar"><?= strtoupper(substr($m['first_name'], 0, 1)) ?></div>
                <div class="meta">
                    <div class="name"><?= htmlspecialchars($name) ?></div>
                    <div class="preview"><?= htmlspecialchars($m['body']) ?></div>
                    <div class="time"><?= time_ago($m['created_at']) ?></div>
                </div>
                <?php if ($m['unread'] > 0): ?>
                    <span class="badge"><?= (int)$m['unread'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; endif; ?>
    </div>
</div>

<button class="compose" onclick="document.getElementById('m').classList.add('show')">
    <i class="fas fa-pen"></i> New Message
</button>

<div class="modal" id="m">
    <div class="modal-box">
        <h3>New Message</h3>
        <form method="POST" action="send-message.php">
            <select name="recipient_id" required>
                <option value="">— Select staff —</option>
                <?php foreach ($staff as $s): ?>
                    <option value="<?= (int)$s['id'] ?>">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <textarea name="body" placeholder="Type your message..." required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel"
                        onclick="document.getElementById('m').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-send">Send</button>
            </div>
        </form>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
// Load sidebar from the parent folder
$("#sidebar-container").load("../sidebar.php");
</script>
</body>
</html>