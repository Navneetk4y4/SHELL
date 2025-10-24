# Web Shell Collection

A collection of PHP-based web shells for authorized security testing and penetration testing purposes.

## ⚠️ IMPORTANT DISCLAIMER

**THIS SOFTWARE IS FOR EDUCATIONAL AND AUTHORIZED SECURITY TESTING PURPOSES ONLY**

- Only use on systems you own or have explicit written permission to test
- Unauthorized use of this software is illegal and unethical
- The authors are not responsible for any misuse of this software
- Use responsibly and in accordance with applicable laws

## 📁 Files Overview

### `bashos.php`
A basic terminal web shell with cross-platform support (Windows/Linux). Features:
- Clean terminal-style interface
- Cross-platform command execution
- Real-time command output display
- Command history with arrow key navigation
- System information display
- Responsive design with terminal aesthetics

### `bashos2.php`
An advanced web shell with enhanced features:
- Multi-tab interface (Terminal, File Manager, System Info, Network)
- File operations (read, write, delete, list directories)
- Command history with session persistence
- Quick action buttons for common commands
- Advanced system information gathering
- Network reconnaissance tools
- Matrix-style background animation
- Stealth mode features

## 🚀 Features

### Basic Shell (`bashos.php`)
- ✅ Cross-platform command execution
- ✅ Real-time output display
- ✅ Command history
- ✅ System information
- ✅ Responsive terminal interface

### Advanced Shell (`bashos2.php`)
- ✅ Multi-tab interface
- ✅ File manager with CRUD operations
- ✅ System information gathering
- ✅ Network reconnaissance
- ✅ Command history persistence
- ✅ Quick action buttons
- ✅ Stealth mode features
- ✅ Matrix background animation

## 🛠️ Installation

1. Clone or download this repository
2. Upload the PHP files to your web server
3. Ensure PHP has execution permissions
4. Access via web browser

## 📋 Requirements

- PHP 5.6+ (recommended PHP 7.0+)
- Web server (Apache, Nginx, etc.)
- Appropriate file permissions

## 🔧 Usage

### Basic Shell
1. Navigate to `bashos.php` in your browser
2. Enter commands in the terminal interface
3. Use arrow keys for command history

### Advanced Shell
1. Navigate to `bashos2.php` in your browser
2. Use the multi-tab interface:
   - **Terminal**: Execute system commands
   - **File Manager**: File operations
   - **System Info**: View system details
   - **Network**: Network information

## 🔒 Security Considerations

- **Authentication**: Add authentication mechanisms before deployment
- **Access Control**: Implement IP whitelisting if needed
- **Logging**: Monitor and log all activities
- **Cleanup**: Remove files after testing
- **Encryption**: Consider HTTPS for sensitive operations

## 📚 Common Commands

### Windows
```bash
dir                    # List directory contents
ipconfig              # Network configuration
whoami                # Current user
netstat -an           # Network connections
systeminfo            # System information
```

### Linux/Unix
```bash
ls -la                # List directory contents
ifconfig              # Network configuration
whoami                # Current user
netstat -an           # Network connections
uname -a              # System information
```

## ⚖️ Legal Notice

This software is provided for educational purposes only. Users are responsible for ensuring they have proper authorization before using this software on any system. The authors disclaim any responsibility for misuse of this software.

## 🤝 Contributing

Contributions are welcome! Please ensure any additions maintain the educational and ethical nature of this project.

## 📄 License

This project is for educational purposes. Use responsibly and in accordance with applicable laws.

---

**Remember: With great power comes great responsibility. Use these tools ethically and legally.**
