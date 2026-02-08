# 🚀 Merge & Deployment Guide

## Step 1: Merge the Pull Request on GitHub

### Direct Link to Create PR:
**👉 Click here:** https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics

### Alternative: Navigate Manually
1. Go to: https://github.com/RblxIsAwesome/Refresh-Tool
2. You should see a yellow banner saying "copilot/add-database-integration-analytics had recent pushes"
3. Click the green "Compare & pull request" button
4. Review the changes
5. Click "Create pull request"
6. Then click "Merge pull request"
7. Click "Confirm merge"

---

## Step 2: Deploy to Your Server

### Option A: Automated Deployment (Easiest - 3 minutes)

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to your project directory
cd /path/to/Refresh-Tool

# Pull the latest changes
git checkout main
git pull origin main

# Run the automated setup script
chmod +x setup.sh
./setup.sh

# Follow the prompts to:
# - Create database
# - Import schema
# - Configure .env file
# - Migrate existing data (optional)
```

### Option B: Manual Deployment (15 minutes)

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to your project directory
cd /path/to/Refresh-Tool

# Pull the latest changes
git checkout main
git pull origin main

# Create the database
mysql -u root -p
CREATE DATABASE refresh_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Import the schema
mysql -u root -p refresh_tool < config/schema.sql

# Configure environment
cp config/.env.example config/.env
nano config/.env  # Edit with your credentials

# Set permissions
chmod -R 755 .
chmod -R 775 logs storage
chown -R www-data:www-data .

# Restart web server
sudo systemctl restart apache2
# OR
sudo systemctl restart nginx php8.1-fpm
```

---

## Step 3: Configure Discord OAuth

### Get Discord Credentials:
**👉 Go to:** https://discord.com/developers/applications

1. Click "New Application"
2. Name it (e.g., "Refresh Tool")
3. Go to "OAuth2" → "General"
4. Copy your **Client ID**
5. Copy your **Client Secret**
6. Click "Add Redirect"
7. Add: `http://your-domain.com/callback.php` (or `https://` if using SSL)
8. Click "Save Changes"

### Update Your .env File:
```bash
nano config/.env
```

Add these values:
```env
DISCORD_CLIENT_ID=your_client_id_here
DISCORD_CLIENT_SECRET=your_client_secret_here
DISCORD_REDIRECT_URI=http://your-domain.com/callback.php
```

---

## Step 4: Test Your Deployment

### Test Checklist:
- [ ] Visit: http://your-domain.com/
- [ ] Login with Discord works
- [ ] Dashboard loads correctly
- [ ] Cookie refresh works
- [ ] Analytics page loads: http://your-domain.com/analytics.php
- [ ] Charts display data
- [ ] No errors in browser console (F12)

---

## 🌐 Railway Deployment (Alternative)

If you're using Railway:

### Direct Link:
**👉 Deploy:** https://railway.app/new

1. Click "Deploy from GitHub repo"
2. Select: `RblxIsAwesome/Refresh-Tool`
3. Click "Deploy Now"

### Configure Environment Variables in Railway:
Go to your project → Variables → Add these:

```
DB_HOST=your_mysql_host
DB_NAME=refresh_tool
DB_USER=your_mysql_user
DB_PASS=your_mysql_password
DISCORD_CLIENT_ID=your_discord_id
DISCORD_CLIENT_SECRET=your_discord_secret
DISCORD_REDIRECT_URI=https://your-app.railway.app/callback.php
```

Railway will auto-deploy when you merge to main!

---

## 📱 Quick Links Reference

### GitHub:
- **Your Repository:** https://github.com/RblxIsAwesome/Refresh-Tool
- **Create PR:** https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics
- **Pull Requests:** https://github.com/RblxIsAwesome/Refresh-Tool/pulls
- **Branches:** https://github.com/RblxIsAwesome/Refresh-Tool/branches

### Discord Developer:
- **Applications:** https://discord.com/developers/applications
- **Documentation:** https://discord.com/developers/docs/topics/oauth2

### Railway (if using):
- **Dashboard:** https://railway.app/dashboard
- **New Project:** https://railway.app/new

---

## 🆘 Troubleshooting

### Database Connection Issues:
```bash
# Test database connection
mysql -u root -p -e "SHOW DATABASES;"

# Check if refresh_tool database exists
mysql -u root -p -e "USE refresh_tool; SHOW TABLES;"
```

### Permission Issues:
```bash
# Fix permissions
sudo chown -R www-data:www-data /path/to/Refresh-Tool
sudo chmod -R 755 /path/to/Refresh-Tool
sudo chmod -R 775 /path/to/Refresh-Tool/logs
sudo chmod -R 775 /path/to/Refresh-Tool/storage
```

### Web Server Not Working:
```bash
# Check Apache status
sudo systemctl status apache2
sudo systemctl restart apache2

# Check Nginx status
sudo systemctl status nginx
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm

# Check error logs
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/nginx/error.log
```

### Discord OAuth Not Working:
1. Check redirect URI matches exactly in Discord app settings
2. Verify client ID and secret in .env file
3. Clear browser cookies and try again

---

## 📞 Need More Help?

- **Documentation:** See `README.md` in your repository
- **Quick Start:** See `QUICKSTART.md` in your repository
- **Deployment Checklist:** See `DEPLOYMENT_CHECKLIST.md` in your repository
- **GitHub Issues:** https://github.com/RblxIsAwesome/Refresh-Tool/issues

---

## ✅ Summary

1. **Merge PR:** https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics
2. **Pull code:** `git pull origin main`
3. **Run setup:** `./setup.sh`
4. **Configure Discord:** https://discord.com/developers/applications
5. **Test it:** Visit your domain and login!

**You're all set! 🎉**
