# ✅ Merge & Deploy Checklist

Print this out or keep it open while you work!

---

## Phase 1: Merge on GitHub ⏱️ 2 minutes

- [ ] Go to: https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics
- [ ] Click "Create pull request"
- [ ] Review the changes (optional)
- [ ] Click "Merge pull request"
- [ ] Click "Confirm merge"
- [ ] ✅ Branch merged successfully!

---

## Phase 2: Deploy to Server ⏱️ 5 minutes

### Option A: Automated Setup (Recommended)

- [ ] SSH into your server: `ssh user@server.com`
- [ ] Navigate to project: `cd /path/to/Refresh-Tool`
- [ ] Switch to main: `git checkout main`
- [ ] Pull changes: `git pull origin main`
- [ ] Make script executable: `chmod +x setup.sh`
- [ ] Run setup: `./setup.sh`
- [ ] Answer prompts:
  - [ ] MySQL host (usually `localhost`)
  - [ ] MySQL user (usually `root`)
  - [ ] MySQL password
  - [ ] Database name (use `refresh_tool`)
- [ ] ✅ Database and config created!

### Option B: Railway (Cloud Hosting)

- [ ] Go to: https://railway.app/new
- [ ] Click "Deploy from GitHub repo"
- [ ] Select `RblxIsAwesome/Refresh-Tool`
- [ ] Add MySQL database service
- [ ] Add environment variables (see Phase 3)
- [ ] ✅ Auto-deploys!

---

## Phase 3: Discord OAuth ⏱️ 5 minutes

- [ ] Go to: https://discord.com/developers/applications
- [ ] Click "New Application"
- [ ] Name it: "Refresh Tool" (or your choice)
- [ ] Go to "OAuth2" → "General"
- [ ] Copy Client ID: `___________________________`
- [ ] Copy Client Secret: `___________________________`
- [ ] Click "Add Redirect"
- [ ] Enter: `http://your-domain.com/callback.php`
- [ ] Click "Save Changes"
- [ ] Update `config/.env` on server:
  ```bash
  nano config/.env
  ```
- [ ] Add these lines:
  ```
  DISCORD_CLIENT_ID=paste_your_id_here
  DISCORD_CLIENT_SECRET=paste_your_secret_here
  DISCORD_REDIRECT_URI=http://your-domain.com/callback.php
  ```
- [ ] Save file (Ctrl+X, Y, Enter)
- [ ] ✅ Discord OAuth configured!

---

## Phase 4: Test Deployment ⏱️ 5 minutes

- [ ] Visit: `http://your-domain.com/`
- [ ] See login page? ✅
- [ ] Click "Login with Discord"
- [ ] Discord asks for authorization? ✅
- [ ] Click "Authorize"
- [ ] Redirected to dashboard? ✅
- [ ] Enter a test cookie
- [ ] Cookie refresh works? ✅
- [ ] Visit: `http://your-domain.com/analytics.php`
- [ ] Analytics page loads? ✅
- [ ] Charts display (might be empty at first)? ✅
- [ ] Open browser console (F12)
- [ ] No JavaScript errors? ✅

---

## Phase 5: Verify Database ⏱️ 2 minutes

- [ ] SSH into server
- [ ] Connect to MySQL: `mysql -u root -p`
- [ ] Use database: `USE refresh_tool;`
- [ ] Show tables: `SHOW TABLES;`
- [ ] Should see 6 tables:
  - [ ] users
  - [ ] refresh_history
  - [ ] rate_limits
  - [ ] analytics
  - [ ] queue_jobs
  - [ ] sessions
- [ ] Check user logged in: `SELECT * FROM users;`
- [ ] Exit MySQL: `exit`
- [ ] ✅ Database working!

---

## Phase 6: Optional Enhancements

### Set up HTTPS (Recommended for production)

- [ ] Install Certbot: `sudo apt install certbot`
- [ ] Get certificate: `sudo certbot --apache -d your-domain.com`
  - OR for Nginx: `sudo certbot --nginx -d your-domain.com`
- [ ] Test auto-renewal: `sudo certbot renew --dry-run`
- [ ] Update Discord redirect to `https://`
- [ ] ✅ SSL enabled!

### Set up Queue Worker (Optional)

- [ ] Test worker: `php public/api/queue_worker.php`
- [ ] If works, press Ctrl+C
- [ ] Create systemd service (see DEPLOYMENT_CHECKLIST.md)
- [ ] Enable service: `sudo systemctl enable refresh-queue`
- [ ] Start service: `sudo systemctl start refresh-queue`
- [ ] Check status: `sudo systemctl status refresh-queue`
- [ ] ✅ Queue worker running!

### Set up Database Backups

- [ ] Create backup script:
  ```bash
  mysqldump -u root -p refresh_tool > backup_$(date +%Y%m%d).sql
  ```
- [ ] Set up cron job: `crontab -e`
- [ ] Add daily backup:
  ```
  0 2 * * * mysqldump -u root -p'password' refresh_tool > /backups/refresh_$(date +\%Y\%m\%d).sql
  ```
- [ ] ✅ Automated backups!

---

## 🎉 Completion Checklist

- [ ] ✅ Pull request merged
- [ ] ✅ Code deployed to server
- [ ] ✅ Database created and populated
- [ ] ✅ Discord OAuth configured
- [ ] ✅ Login works
- [ ] ✅ Cookie refresh works
- [ ] ✅ Analytics dashboard works
- [ ] ✅ No errors in logs or console
- [ ] ✅ (Optional) HTTPS enabled
- [ ] ✅ (Optional) Queue worker running
- [ ] ✅ (Optional) Backups configured

---

## 📊 Final Verification

Visit these URLs and verify they work:

- [ ] `http://your-domain.com/` - Login page
- [ ] `http://your-domain.com/dashboard.php` - Main dashboard
- [ ] `http://your-domain.com/analytics.php` - Analytics
- [ ] `http://your-domain.com/api/stats.php` - Stats API (JSON)

---

## 🆘 Troubleshooting

### Problem: Can't merge PR
- **Solution:** Make sure you're logged into GitHub and have write access

### Problem: Database connection failed
- **Solution:** Check credentials in `config/.env`
- **Solution:** Verify MySQL is running: `sudo systemctl status mysql`

### Problem: Discord OAuth not working
- **Solution:** Verify redirect URI matches exactly (http vs https, trailing slash)
- **Solution:** Check Client ID and Secret are correct

### Problem: Page shows errors
- **Solution:** Check logs: `tail -f logs/database_errors.log`
- **Solution:** Check web server logs: `tail -f /var/log/apache2/error.log`

### Problem: Analytics shows no data
- **Solution:** Normal on first install, data will populate as you use it
- **Solution:** Visit dashboard and refresh a cookie first

---

## 📚 Need More Help?

Check these files in your repository:
- `QUICK_DEPLOY.md` - Simple 3-step guide
- `MERGE_AND_DEPLOY.md` - Complete guide with all links
- `DEPLOYMENT_CHECKLIST.md` - Detailed step-by-step
- `README.md` - Full documentation

Or open an issue:
https://github.com/RblxIsAwesome/Refresh-Tool/issues/new

---

**Estimated Total Time: 20-30 minutes**

**Status:** 
- Start time: ___:___
- End time: ___:___
- Notes: _________________________________

---

**🎊 Congratulations! Your upgraded Refresh Tool is now live! 🎊**
