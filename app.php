<?php
// ==============================================
// PERSONAL NOTES KEEPER — Single-File PHP App
// ==============================================

// Database Settings
$db_host = 'localhost';
$db_name = 'notes_app';
$db_user = 'root';
$db_pass = '';

// Auto-Setup Database & Table
try {
    // Connect & create database if missing
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Create notes table
    $sql = "
        CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            color VARCHAR(20) DEFAULT '#ffffff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// ==============================================
// Handle Actions
// ==============================================

// Save New Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title']) && !empty($_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO notes (title, content, color) VALUES (:title, :content, :color)");
    $stmt->execute([
        'title' => trim($_POST['title']),
        'content' => trim($_POST['content']),
        'color' => $_POST['color']
    ]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Delete Note
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get All Notes
$stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📒 My Personal Notes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: white;
            font-size: 2.2rem;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 35px;
        }
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        .form-grid {
            display: grid;
            gap: 15px;
        }
        input, textarea, button {
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            width: 100%;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .color-picker {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .color-picker label {
            font-weight: 600;
            color: #4a5568;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .note {
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            position: relative;
        }
        .note:hover {
            transform: translateY(-5px);
        }
        .note h3 {
            margin-bottom: 10px;
            color: #1a202c;
            font-size: 1.1rem;
        }
        .note p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        .note .date {
            font-size: 0.8rem;
            color: #718096;
        }
        .note .del {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #e53e3e;
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
            cursor: pointer;
        }
        .empty {
            text-align: center;
            color: #718096;
            padding: 40px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>📒 My Personal Notes</h1>

        <!-- Add Note Form -->
        <div class="card">
            <h2>✍️ Write New Note</h2>
            <form method="POST" class="form-grid">
                <input type="text" name="title" placeholder="Note title..." required>
                <textarea name="content" placeholder="Write your note here..." required></textarea>
                <div class="color-picker">
                    <label>Note Color:</label>
                    <select name="color">
                        <option value="#ffffff">White</option>
                        <option value="#fff3cd">Yellow</option>
                        <option value="#d4edda">Green</option>
                        <option value="#cce5ff">Blue</option>
                        <option value="#f8d7da">Pink</option>
                        <option value="#e9d8fd">Purple</option>
                    </select>
                </div>
                <button type="submit">💾 Save Note</button>
            </form>
        </div>

        <!-- Notes List -->
        <div class="card">
            <h2>📁 All Notes</h2>
            <?php if (empty($notes)): ?>
                <p class="empty">No notes yet. Write your first note above! ✨</p>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($notes as $n): ?>
                        <div class="note" style="background: <?= $n['color'] ?>">
                            <form method="GET" style="position:absolute;top:10px;right:10px;">
                                <button type="submit" name="delete" value="<?= $n['id'] ?>" class="del"
                                    onclick="return confirm('Delete this note?')">×</button>
                            </form>
                            <h3><?= htmlspecialchars($n['title']) ?></h3>
                            <p><?= htmlspecialchars($n['content']) ?></p>
                            <span class="date">📅 <?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>