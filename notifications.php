<?php
session_start();
include('db_connect.php'); // $conn as PDO

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch latest notifications
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications
        WHERE notification_user_id = :user_id
        ORDER BY notification_created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id'=>$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unread_stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE notification_user_id = :user_id AND notification_is_read = FALSE
    ");
    $unread_stmt->execute([':user_id'=>$user_id]);
    $unread_count = $unread_stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database error: ".$e->getMessage());
}

function getIconByType($type){
    switch(strtolower($type)){
        case 'estimation_success': return '🤖';
        case 'low_confidence': return '⚠️';
        case 'no_match': return '🚫';
        default: return '🔔';
    }
}

function getColorByType($type){
    switch(strtolower($type)){
        case 'estimation_success': return '#d4edda'; // green
        case 'low_confidence': return '#fff3cd'; // yellow
        case 'no_match': return '#f8d7da'; // red
        default: return '#d1ecf1'; // blue/gray
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - Used Item Value Estimator</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    font-family:'Poppins',sans-serif; 
    background: #ffffff; /* changed background to white */
    margin:0; 
    padding:0; 
    min-height:100vh;
}

/* ---------- Header ---------- */
header {
    background: #f8f9fa; /* light gray for subtle contrast */
    padding: 30px 20px; 
    text-align: center; 
    border-bottom: 1px solid #ddd;
}
header h1 {
    margin:0; 
    font-size:28px; 
    color:#0984e3;
}

/* ---------- Container ---------- */
.container {
    max-width:700px; 
    margin:30px auto; 
    background:#fff; 
    padding:30px; 
    border-radius:15px; 
    box-shadow:0 10px 25px rgba(0,0,0,.1); 
    animation: fadeIn 0.8s ease-in-out;
}

/* ---------- Notification Cards ---------- */
.notif-item {
    padding:20px; 
    border-radius:12px; 
    margin-bottom:15px; 
    display:flex; 
    justify-content:space-between; 
    align-items:flex-start; 
    box-shadow:0 4px 12px rgba(0,0,0,0.05); 
    transition: transform 0.3s, box-shadow 0.3s;
}
.notif-item:hover {
    transform: translateY(-3px); 
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}
.notif-left {flex:1;}
.notif-left span {font-size:24px; margin-right:10px;}
.notif-left strong {font-size:16px; color:#0984e3;}
.notif-left small {display:block; color:#555; margin-top:5px; font-size:13px;}
.mark-read-btn, .delete-btn {
    background:#28a745; color:white; border:none; border-radius:5px; padding:6px 12px; cursor:pointer; font-size:12px; margin-left:5px; transition:0.3s;
}
.mark-read-btn:hover {background:#1e7e34;}
.delete-btn {background:#d63031;}
.delete-btn:hover {background:#ff7675;}
.return-btn {
    display:block; 
    width:220px; 
    margin:25px auto 0; 
    text-align:center; 
    padding:12px 15px; 
    background:#0984e3; 
    color:white; 
    border-radius:10px; 
    text-decoration:none; 
    font-weight:600; 
    transition:0.3s;
}
.return-btn:hover {background:#74b9ff; transform: translateY(-2px);}
.unread-badge {
    background:#0984e3; 
    color:white; 
    font-size:11px; 
    padding:2px 6px; 
    border-radius:50%; 
    margin-left:8px;
}
@keyframes fadeIn {from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:translateY(0);}}
</style>
</head>
<body>

<header>
    <h1>Notifications <?php if($unread_count>0) echo "($unread_count unread)"; ?></h1>
</header>

<div class="container">
    <?php if(empty($notifications)): ?>
        <p style="text-align:center; font-size:16px; color:#555;">No notifications</p>
    <?php else: ?>
        <?php foreach($notifications as $note): ?>
            <div class="notif-item" style="background: <?php echo getColorByType($note['notification_type']); ?>;">
                <div class="notif-left">
                    <span><?php echo getIconByType($note['notification_type']); ?></span>
                    <strong>
                        <?php echo htmlspecialchars($note['notification_title']); ?>
                        <?php if(!$note['notification_is_read']): ?><span class="unread-badge">NEW</span><?php endif; ?>
                    </strong>
                    <small><?php echo htmlspecialchars($note['notification_message']); ?></small>
                    <small><?php echo date('d M H:i', strtotime($note['notification_created_at'])); ?></small>
                </div>
                <div>
                    <?php if(!$note['notification_is_read']): ?>
                        <button class="mark-read-btn" onclick="markRead(<?php echo $note['notification_id']; ?>)">Mark Read</button>
                    <?php endif; ?>
                    <button class="delete-btn" onclick="deleteNotif(<?php echo $note['notification_id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="dashboard.php" class="return-btn">Return to Dashboard</a>
</div>

<script>
function markRead(id){
    fetch('mark_read.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'notification_id='+id
    }).then(()=> location.reload());
}

function deleteNotif(id){
    if(confirm("Are you sure you want to delete this notification?")){
        fetch('delete_notification.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'notification_id='+id
        }).then(()=> location.reload());
    }
}
</script>

</body>
</html>
