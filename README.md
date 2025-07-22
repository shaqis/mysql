# MySQL to CSV Exporter with Secure Login

A secure web application for exporting MySQL database tables to CSV format with authentication and comprehensive security features.

## 🚀 Features

- **🔐 Secure Authentication**: Password-hashed login system with CSRF protection
- **📊 CSV Export**: Export MySQL tables to CSV format with automatic download
- **🛡️ Security Features**: Session management, input validation, SQL injection protection
- **⚙️ Configuration**: Environment-based configuration using `.env` files
- **🔧 CLI Tools**: Password hash generator for secure credential setup
- **📱 Responsive UI**: Clean and modern web interface

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Composer (for dependency management)

## 🛠️ Installation & Setup

### 1. Clone or Download the Project

```bash
# Clone the repository
git clone <repository-url>
cd mysql-csv-exporter

# Or download and extract the files to your web server directory
# (e.g., htdocs, public_html, or /var/www/html)
```

### 2. Install Dependencies

```bash
# Install PHP dependencies (if composer.json exists)
composer install

# If composer is not installed, install it first:
# curl -sS https://getcomposer.org/installer | php
# sudo mv composer.phar /usr/local/bin/composer
```

### 3. Configure Environment Variables

Create a `.env` file in the project root:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_username
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

# Application Authentication
APP_USER=admin
APP_PASS_HASH=your_generated_password_hash

# Export Configuration
EXPORT_TABLE=your_table_name
```

**Important**: Replace all placeholder values with your actual configuration.

### 4. Generate Secure Password Hash

Use the provided CLI tool to generate a secure password hash:

```bash
php make_hash.php your_secure_password
```

**Example:**
```bash
php make_hash.php MySecurePassword123!
# Output: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

Copy the generated hash and set it as `APP_PASS_HASH` in your `.env` file.

**Note:** Keep your original password secure as you'll need it to log in to the application.

### 5. Set File Permissions

```bash
# Ensure web server can read the files
chmod 644 *.php

# Ensure .env file is secure and not publicly accessible
chmod 600 .env

# Make the hash generator executable
chmod +x make_hash.php
```

### 6. Configure Web Server

#### Apache Configuration
Ensure your `.htaccess` file (if needed) includes:

```apache
# Protect .env file
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Enable PHP execution
AddHandler application/x-httpd-php .php
```

#### Nginx Configuration
Add to your server block:

```nginx
# Protect .env file
location ~ /\.env {
    deny all;
}

# PHP processing
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Adjust PHP version as needed
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## 🚀 Usage

### 1. Access the Application

Open your web browser and navigate to:
```
http://your-domain.com/index.php
```

### 2. Login

- Enter your configured username (`APP_USER`)
- Enter your password
- Click "Login"

### 3. Export CSV

After successful authentication, the CSV export will start automatically and download to your device.

## 🔒 Security Features

### Authentication & Session Management
- **Password Hashing**: Uses PHP's `password_hash()` with `PASSWORD_DEFAULT`
- **CSRF Protection**: Token-based protection against Cross-Site Request Forgery
- **Session Security**: Automatic session timeout (30 minutes) and regeneration
- **Secure Comparison**: Uses `hash_equals()` for timing attack prevention

### Database Security
- **Prepared Statements**: Prevents SQL injection attacks
- **Input Validation**: Regex-based table name validation
- **Error Handling**: Secure error messages without information disclosure
- **Connection Security**: Environment-based database credentials

### Data Protection
- **CSV Sanitization**: Prevents formula injection in Excel/Google Sheets
- **Output Encoding**: HTML special characters encoding
- **Content Security Policy**: CSP headers for XSS protection

## 📁 File Structure

```
mysql-csv-exporter/
├── index.php          # Login page with CSRF protection
├── login.php          # Authentication handler
├── export.php         # CSV export functionality
├── make_hash.php      # CLI password hash generator
├── composer.json      # PHP dependencies
├── composer.lock      # Locked dependency versions
├── .env              # Environment configuration (create this)
├── .gitignore        # Git ignore rules
└── vendor/           # Composer dependencies
```

## ⚙️ Configuration Options

### Environment Variables

| Variable | Description | Required | Default |
|----------|-------------|----------|---------|
| `DB_HOST` | MySQL server hostname | Yes | - |
| `DB_NAME` | Database name | Yes | - |
| `DB_USER` | Database username | Yes | - |
| `DB_PASS` | Database password | Yes | - |
| `DB_CHARSET` | Database character set | No | `utf8mb4` |
| `APP_USER` | Login username | Yes | - |
| `APP_PASS_HASH` | Hashed password | Yes | - |
| `EXPORT_TABLE` | Table to export | Yes | - |

### Session Configuration

The application uses the following session settings:
- **Timeout**: 30 minutes (1800 seconds)
- **Regeneration**: Every 30 minutes
- **Secure**: CSRF token validation

## 🔧 Troubleshooting

### Common Issues

#### 1. "Configuration error" Message
- Check that all required environment variables are set in `.env`
- Verify database credentials are correct
- Ensure `.env` file is readable by the web server

#### 2. "Invalid username or password" Error
- Verify `APP_USER` and `APP_PASS_HASH` in `.env`
- Regenerate password hash using `make_hash.php`
- Check for extra spaces or special characters in credentials
- Ensure you're using the original password (not the hash) to log in

#### 3. Database Connection Failed
- Verify MySQL server is running
- Check database credentials in `.env`
- Ensure database user has SELECT permissions on the target table

#### 4. CSV Download Not Starting
- Check browser download settings
- Verify table name exists in database
- Check PHP memory limits for large datasets

#### 5. CSRF Token Errors
- Clear browser cookies and cache
- Ensure session is working properly
- Check server time synchronization

### Debug Mode

To enable debug logging, add to your `.env`:

```env
DEBUG=true
```

### Log Files

Check your web server error logs for detailed error messages:
- **Apache**: `/var/log/apache2/error.log`
- **Nginx**: `/var/log/nginx/error.log`
- **PHP**: `/var/log/php_errors.log`

## 🚀 Production Deployment

### Security Checklist

- [ ] Use HTTPS/SSL encryption
- [ ] Set proper file permissions
- [ ] Configure firewall rules
- [ ] Enable error logging
- [ ] Set up monitoring
- [ ] Regular security updates
- [ ] Database backup strategy

### Performance Optimization

- [ ] Enable PHP OPcache
- [ ] Configure MySQL query cache
- [ ] Use CDN for static assets
- [ ] Implement caching strategies

### Monitoring

Consider implementing:
- Application performance monitoring
- Security event logging
- Database query monitoring
- User access logging

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section above
- Review the security documentation

## 🔄 Version History

- **v1.0.0**: Initial release with basic CSV export functionality
- **v1.1.0**: Added CSRF protection and improved security
- **v1.2.0**: Environment-based configuration and CLI tools

---

**⚠️ Security Notice**: Always keep your `.env` file secure and never commit it to version control. Regularly update dependencies and monitor for security vulnerabilities.
