# 🔄 Mystic Cookie Refresher

Advanced Roblox cookie refresher with Discord OAuth, database integration, and comprehensive analytics.

## ✨ Features

### Core Features
- 🔐 Discord OAuth 2.0 authentication
- 🔄 Secure Roblox cookie refreshing
- 📊 Real-time analytics dashboard with Chart.js visualizations
- 🎨 Modern dark theme with glassmorphism design
- 🚀 Enhanced UI with loading animations and progress indicators
- 📱 Fully responsive design

### Security & Performance
- 🛡️ Advanced rate limiting with database backend
- 🔒 IP ban system for abuse prevention
- ⚡ Database connection pooling and auto-reconnect
- 📝 Comprehensive request logging
- ✅ Client-side cookie validation

### Analytics & Monitoring
- 📈 Line charts for refresh trends (7-day history)
- 🥧 Pie charts for success/failure rates
- 📊 Bar charts for hourly usage patterns
- 🎯 User leaderboard (anonymized)
- 💾 Export data as JSON/CSV
- 🔄 Auto-refresh every 30 seconds

## 🗄️ Database Setup

This application uses MySQL/MariaDB for persistent storage. Follow these steps to set up the database:

### Prerequisites
- MySQL 5.7+ or MariaDB 10.3+
- PHP 8.1+ with PDO and PDO_MySQL extensions

### Installation Steps

1. **Create the database:**
```sql
CREATE DATABASE refresh_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Create a database user (recommended):**
```sql
CREATE USER 'refresh_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON refresh_tool.* TO 'refresh_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Import the schema:**
```bash
mysql -u root -p refresh_tool < config/schema.sql
```

Or via PHP:
```bash
mysql -u root -p refresh_tool < config/schema.sql
```

4. **Configure environment variables:**

Copy the example configuration:
```bash
cp config/.env.example config/.env
```

Edit `config/.env` with your database credentials:
```env
DB_HOST=localhost
DB_NAME=refresh_tool
DB_USER=refresh_user
DB_PASS=your_secure_password
DB_PORT=3306

DISCORD_CLIENT_ID=your_discord_client_id
DISCORD_CLIENT_SECRET=your_discord_client_secret
DISCORD_REDIRECT_URI=http://localhost/callback.php
```

5. **Migrate existing data (optional):**

If you have existing JSON-based data, migrate it:
```bash
php config/migrate.php
```

The migration script will:
- Create backups of your JSON files
- Import rate limits, refresh history, and stats
- Preserve all existing data

### Database Schema

The application uses 6 main tables:

- **`users`** - Discord user profiles and statistics
- **`refresh_history`** - Complete log of all refresh attempts
- **`rate_limits`** - IP-based rate limiting and bans
- **`analytics`** - Aggregated statistics for dashboard
- **`queue_jobs`** - Background job queue (async processing)
- **`sessions`** - Optional session storage in database

### Automated Maintenance

The schema includes MySQL events for automatic maintenance:

- **Cleanup rate limits** - Runs hourly to remove expired entries
- **Update analytics** - Runs every 5 minutes to refresh statistics

To enable events (if not already enabled):
```sql
SET GLOBAL event_scheduler = ON;
```

Add to your MySQL config file (`my.cnf`):
```ini
[mysqld]
event_scheduler = ON
```

### Optional: Queue Worker

For asynchronous job processing, run the queue worker:

```bash
php public/api/queue_worker.php
```

Or set up as a systemd service:

```ini
[Unit]
Description=Refresh Tool Queue Worker
After=mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/Refresh-Tool
ExecStart=/usr/bin/php /path/to/Refresh-Tool/public/api/queue_worker.php
Restart=always

[Install]
WantedBy=multi-user.target
```

## 🚀 Deployment

### Local Development

1. Clone the repository:
```bash
git clone https://github.com/RblxIsAwesome/Refresh-Tool.git
cd Refresh-Tool
```

2. Install dependencies:
```bash
composer install
```

3. Set up database (see Database Setup section above)

4. Configure your web server to point to the `public/` directory

5. Set up Discord OAuth application:
   - Go to https://discord.com/developers/applications
   - Create a new application
   - Add a redirect URI: `http://localhost/callback.php`
   - Copy Client ID and Secret to `.env`

6. Start your web server and visit `http://localhost`

### Deploy to Railway

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app)

**Requirements:**
- PHP 8.1+
- MySQL addon

**Environment Variables:**
Set these in Railway:
```
DB_HOST=your_mysql_host
DB_NAME=refresh_tool
DB_USER=your_mysql_user
DB_PASS=your_mysql_password
DISCORD_CLIENT_ID=your_client_id
DISCORD_CLIENT_SECRET=your_client_secret
DISCORD_REDIRECT_URI=https://your-domain.railway.app/callback.php
```

## 📁 File Structure

```
/config
  - database.php         # PDO connection handler
  - schema.sql          # Complete MySQL schema
  - migrate.php         # Migration script from JSON
  - .env.example        # Environment configuration template

/public
  - index.php           # Landing page
  - login.php           # Discord OAuth login
  - callback.php        # OAuth callback handler
  - dashboard.php       # Main cookie refresher interface
  - analytics.php       # Analytics dashboard
  - favicon.svg         # Application favicon
  
  /api
    - refresh.php       # Cookie refresh endpoint
    - rate_limit.php    # Rate limiting system
    - stats.php         # Statistics API
    - queue_worker.php  # Background job processor
  
  /assets
    /css
      - animations.css  # UI animations and effects
    /js
      - validator.js    # Cookie format validator
      - analytics-charts.js  # Chart.js integration

/storage
  - *.json              # Legacy file-based storage (fallback)

/logs
  - database_errors.log # Database error logs
```

## 🔒 Security Features

- **Rate Limiting**: 3 requests per 60 seconds per IP
- **IP Banning**: Automatic 1-hour ban after excessive violations
- **Prepared Statements**: All database queries use parameterized queries
- **Input Validation**: Client and server-side cookie validation
- **Session Security**: 30-day persistent sessions with secure cookies
- **Error Handling**: User-friendly errors, technical details in logs
- **Database Fallback**: Continues operation if database is unavailable

## 📊 API Endpoints

### Public Endpoints

- `GET /api/stats.php` - Get public statistics
  - Returns: Total refreshes, success rate, hourly/daily stats, leaderboard

### Protected Endpoints (require authentication)

- `POST /api/refresh.php` - Refresh a Roblox cookie
  - Body: `cookie=<roblox_cookie>`
  - Returns: Refreshed cookie and user data

## 🎨 Customization

### Theming

Edit CSS variables in `dashboard.php` and `analytics.php`:

```css
:root {
    --bg-primary: #070A12;
    --bg-secondary: #050712;
    --text-primary: rgba(255, 255, 255, 0.92);
    --accent-blue: #7CB6FF;
    --success: #29c27f;
    --error: #f05555;
}
```

### Analytics Refresh Interval

Edit `analytics-charts.js`:
```javascript
// Default: 30 seconds (30000ms)
startAutoRefresh() {
    this.refreshInterval = setInterval(() => {
        this.updateCharts();
    }, 30000); // Change this value
}
```

## 🐛 Troubleshooting

### Database Connection Issues

1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify credentials in `config/.env`
3. Check firewall: `sudo ufw allow 3306`
4. Review logs: `tail -f logs/database_errors.log`

### Migration Issues

If migration fails:
1. Backups are in `storage/backup_YYYY-MM-DD_HH-mm-ss/`
2. Run migration again: `php config/migrate.php`
3. Check MySQL error logs: `tail -f /var/log/mysql/error.log`

### Rate Limiting Too Strict

Adjust limits in `public/api/rate_limit.php`:
```php
checkRateLimit(3, 60); // Change to: checkRateLimit(10, 60)
// 10 requests per 60 seconds
```

## 📝 License

This project is open source and available for educational purposes.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Support

For issues and questions, please open an issue on GitHub.

---

Made with ❤️ by the community
