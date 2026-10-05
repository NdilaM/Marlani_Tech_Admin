<?php
// Messages/message-view.php - Thread view
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$me    = (int)$_SESSION['staff_id'];
$other = (int)($_GET['with'] ?? 0);

if ($other <= 0 || $other === $me) {
    header('Location: messages.php');
    exit();
}

/* Load other user */
$stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE id = ?");
$stmt->execute([$other]);
$otherUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otherUser) {
    header('Location: messages.php');
    exit();
}

/* Mark their messages as read */
$conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0")
     ->execute([$other, $me]);

/* Full conversation */
$stmt = $conn->prepare("
    SELECT * FROM messages
    WHERE (sender_id = ? AND recipient_id = ?)
       OR (sender_id = ? AND recipient_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$me, $other, $other, $me]);
$thread = $stmt->fetchAll(PDO::FETCH_ASSOC);

$name = $otherUser['first_name'] . ' ' . $otherUser['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Chat with <?= htmlspecialchars($name) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Nunito,sans-serif;background:#f8f9fc;display:flex;min-height:100vh;color:#5a5c69}
#sidebar-container{display:flex;flex-shrink:0;height:100vh;position:sticky;top:0;align-self:flex-start}
.main{flex:1;padding:2rem;display:flex;flex-direction:column;min-width:0}
.back{margin-bottom:1rem}
.back a{color:#4e73df;text-decoration:none;font-weight:600}
.back a:hover{text-decoration:underline}
h1{font-size:1.3rem;color:#2d3748;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);flex:1;display:flex;flex-direction:column;overflow:hidden;min-height:0}
.thread{flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:.75rem}
.bubble{max-width:70%;padding:.75rem 1rem;border-radius:14px;font-size:.95rem;line-height:1.4;word-wrap:break-word}
.bubble.in{background:#f0f4ff;color:#2d3748;align-self:flex-start;border-top-left-radius:4px}
.bubble.out{background:#003986;color:#fff;align-self:flex-end;border-top-right-radius:4px}
.bubble .t{font-size:.7rem;opacity:.7;margin-top:.3rem;display:block}
.composer{border-top:1px solid #eef1f7;padding:1rem;display:flex;gap:.75rem;background:#fafbff}
.composer textarea{flex:1;padding:.75rem;border:1.5px solid #e2e8f0;border-radius:12px;font-family:inherit;font-size:.95rem;resize:none;min-height:48px;color:#2d3748}
.composer textarea:focus{outline:none;border-color:#003986;box-shadow:0 0 0 3px rgba(0,57,134,.15)}
.composer button{background:#003986;color:#fff;border:none;border-radius:12px;padding:0 1.25rem;font-weight:700;cursor:pointer;font-size:1rem}
.composer button:hover{background:#002a66}
.empty{text-align:center;color:#a0aec0;margin:auto;padding:2rem}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <div class="back">
        <a href="messages.php"><i class="fas fa-arrow-left"></i> Back to Inbox</a>
    </div>
    <h1><i class="fas fa-user-circle"></i> <?= htmlspecialchars($name) ?></h1>

    <div class="card">
        <div class="thread" id="thread">
            <?php if (empty($thread)): ?>
                <div class="empty">
                    <i class="fas fa-comments" style="font-size:2.5rem;display:block;margin-bottom:.5rem"></i>
                    No messages yet. Say hello!
                </div>
            <?php else: foreach ($thread as $m):
                $dir = ((int)$m['sender_id'] === $me) ? 'out' : 'in';
            ?>
                <div class="bubble <?= $dir ?>">
                    <?= nl2br(htmlspecialchars($m['body'])) ?>
                    <span class="t">
                        <?= $dir === 'out' ? 'You' : htmlspecialchars($name) ?>
                        · <?= date('M j, H:i', strtotime($m['created_at'])) ?>
                    </span>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <form class="composer" method="POST" action="send-message.php">
            <input type="hidden" name="recipient_id" value="<?= (int)$other ?>">
            <textarea name="body" placeholder="Type your reply..." required></textarea>
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");
var t = document.getElementById('thread');
t.scrollTop = t.scrollHeight;
</script>
</body>
</html>