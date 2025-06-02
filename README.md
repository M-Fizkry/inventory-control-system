# Inventory Control System

A web-based inventory management system built with PHP and MySQL that manages raw materials, work in progress items, and finished goods.

## Features

- Multi-language support (English and Bahasa Indonesia)
- User authentication and role-based access control
- Dashboard with stock overview and charts
- Bill of Materials (BOM) management
- Production planning with three-tier system
- User management
- System settings configuration

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Create a MySQL database:
```sql
CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p inventory_system < database.sql
```

3. Configure the database connection in `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_system');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

4. Set up your web server to point to the project directory.

5. Ensure the following directories are writable by the web server:
   - assets/images/
   - logs/ (create if not exists)

## Default Login

- Username: admin
- Password: admin123

**Important**: Change the default password after first login!

## Directory Structure

```
├── ajax/                  # AJAX handlers
├── assets/               # Static assets
│   ├── css/             # CSS files
│   ├── js/              # JavaScript files
│   └── images/          # Uploaded images
├── config/              # Configuration files
├── includes/            # PHP includes
├── pages/              # Page templates
└── logs/               # System logs
```

## Features Overview

### Dashboard
- Stock level overview
- Charts showing current, minimum, and maximum stock levels
- Quick status indicators for low and high stock items

### Bill of Materials (BOM)
- Define product structures
- Manage component relationships
- Calculate material requirements

### Production Planning
- Three-tier production plan system:
  * Plan 1: 9110
  * Plan 2: 9210, 9220, 9230
  * Plan 3: 9310, 9320, 9330
- Track production progress
- Manage production status

### User Management
- Create and manage users
- Define user roles
- Set access permissions
- Control feature access

### Settings
- System name configuration
- Logo customization
- Language preferences
- System localization

## Security Features

- Password hashing using bcrypt
- CSRF protection
- SQL injection prevention
- XSS protection
- Session security
- Input validation

## Maintenance

### Database Backup
Regular database backups are recommended. Example command:
```bash
mysqldump -u your_username -p inventory_system > backup_$(date +%Y%m%d).sql
```

### Log Rotation
Set up log rotation for the logs directory to manage log file sizes.

### Updates
1. Backup your database and files
2. Pull the latest changes
3. Run any new database migrations
4. Clear browser cache

## Troubleshooting

### Common Issues

1. Database Connection Error
   - Verify database credentials in config.php
   - Check if MySQL service is running
   - Ensure database exists and is accessible

2. Permission Issues
   - Check directory permissions for assets/images/
   - Verify web server user has write access to required directories

3. Session Issues
   - Check PHP session configuration
   - Verify session directory permissions

### Error Logging

Errors are logged in the logs directory. Check these files for troubleshooting:
- error.log: PHP errors
- access.log: Access logs
- debug.log: Debug information (when debug mode is enabled)

## Support

For issues and support:
1. Check the troubleshooting section
2. Review error logs
3. Contact system administrator

## License

This software is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.
