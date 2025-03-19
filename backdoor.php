<?php
// Ensure secure directory for logs
if (!is_dir('secure')) {
    mkdir('secure', 0755, true);
}


// Log function
function log_activity($message) {
    $log_file = 'secure/bd_activity.log';
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Handle directory traversal
$cwd = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (!$cwd || strpos($cwd, getcwd()) !== 0) {
    $cwd = getcwd(); // Prevent directory traversal outside base
}

$parent_dir = dirname($cwd);
$message = '';
$file_content = '';
$filename = '';

// Handle file actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = $_POST['filename'];
    $action = $_POST['action'];
    $file_path = $cwd . DIRECTORY_SEPARATOR . $filename;

    if ($action === 'view' || $action === 'edit') {
        if (file_exists($file_path) && is_file($file_path)) {
            $file_content = file_get_contents($file_path); // Load file content
            log_activity("User viewed file: $file_path");
        } else {
            $message = "File does not exist.";
        }
    }

    if ($action === 'save' && isset($_POST['content'])) {
        // Ensure that content is sanitized before saving
        $file_content = $_POST['content'];
        if (file_put_contents($file_path, $file_content)) {
            log_activity("User edited and saved file: $file_path");
            $message = "File saved successfully!";
        } else {
            $message = "Failed to save the file.";
        }
    }
}

// Get list of files and directories
$items = array_diff(scandir($cwd), ['.', '..']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1e1e2f;
            color: #fff;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #ff7f50;
        }
        .message {
            color: #ffae42;
            text-align: center;
            margin-bottom: 10px;
        }
        .directory-nav {
            margin-bottom: 20px;
            text-align: center;
        }
        .directory-nav a {
            color: #61dafb;
            text-decoration: none;
        }
        .directory-nav a:hover {
            text-decoration: underline;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 10px;
            margin: 5px 0;
            background: #333;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        li:hover {
            background: #444;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        button {
            background-color: #61dafb;
            color: #000;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        button:hover {
            background-color: #21a1f1;
        }
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1e1e2f;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 80%;
            overflow-y: auto;
            z-index: 1000;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .modal-header h2 {
            margin: 0;
        }
        .close-btn {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .close-btn:hover {
            background-color: #c0392b;
        }
        textarea {
            width: 100%;
            height: 300px;
            background-color: #282a36;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
        }
    </style>
    <script>
        function openModal(content, filename) {
            document.getElementById('modal').style.display = 'block';
            document.getElementById('modal-content').value = content || '';
            document.getElementById('modal-filename').value = filename;
            document.getElementById('modal-action').value = 'save'; // Ensure action is set to save
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
    </script>
</head>
<body>
    <h1>File Manager</h1>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <div class="directory-nav">
        <a href="?dir=<?= urlencode($parent_dir) ?>">⬅ Back to Parent Directory</a>
        <p>Current Directory: <?= htmlspecialchars($cwd) ?></p>
    </div>
    <ul>
        <?php foreach ($items as $item): ?>
            <?php $item_path = $cwd . DIRECTORY_SEPARATOR . $item; ?>
            <li>
                <span><?= htmlspecialchars($item) ?></span>
                <div class="file-actions">
                    <?php if (is_dir($item_path)): ?>
                        <a href="?dir=<?= urlencode($item_path) ?>">
                            <button>Open</button>
                        </a>
                    <?php else: ?>
                        <button onclick="openModal(`<?= addslashes(htmlspecialchars(file_get_contents($item_path))) ?>`, '<?= htmlspecialchars($item) ?>')">Edit</button>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div id="modal" class="modal">
        <div class="modal-header">
            <h2>File Editor</h2>
            <button class="close-btn" onclick="closeModal()">X</button>
        </div>
        <form method="POST">
            <textarea id="modal-content" name="content"></textarea>
            <input type="hidden" id="modal-filename" name="filename">
            <input type="hidden" id="modal-action" name="action" value="save">
            <button type="submit" style="margin-top: 10px;">Save</button>
        </form>
    </div>
</body>
</html>
