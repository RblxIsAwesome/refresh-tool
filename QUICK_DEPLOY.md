# 🎯 Quick Start - 3 Simple Steps

## Step 1️⃣: Merge on GitHub (2 minutes)

**👉 Click here to create PR:**
https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics

```
You'll see:
┌─────────────────────────────────────┐
│  Comparing changes                  │
│  ┌─────────────────────────────┐   │
│  │ Create pull request         │◄── Click this
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘

Then:
┌─────────────────────────────────────┐
│  Pull Request #123                  │
│  ┌─────────────────────────────┐   │
│  │ Merge pull request          │◄── Click this
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘

Finally:
┌─────────────────────────────────────┐
│  Confirm merge                      │
│  ┌─────────────────────────────┐   │
│  │ Confirm merge               │◄── Click this
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘

✅ Done! Changes are now in main branch
```

---

## Step 2️⃣: Deploy to Server (3 minutes)

**SSH into your server and run:**

```bash
cd /path/to/Refresh-Tool
git checkout main
git pull origin main
chmod +x setup.sh
./setup.sh
```

**The script will ask you:**
```
┌─────────────────────────────────────────┐
│ MySQL Host? [localhost]                 │
│ MySQL User? [root]                      │
│ MySQL Password?                         │
│ Database Name? [refresh_tool]           │
└─────────────────────────────────────────┘

Just answer the questions!

It will automatically:
✅ Create database
✅ Import schema
✅ Configure .env
✅ Migrate data (optional)
```

---

## Step 3️⃣: Configure Discord (5 minutes)

**👉 Go to Discord Developer Portal:**
https://discord.com/developers/applications

```
1. Click "New Application"
   └─ Name it: "Refresh Tool"

2. Go to OAuth2 → General
   └─ Copy Client ID
   └─ Copy Client Secret

3. Add Redirect URI
   └─ Add: http://your-domain.com/callback.php
   └─ Click Save

4. Update config/.env on server
   └─ DISCORD_CLIENT_ID=your_id_here
   └─ DISCORD_CLIENT_SECRET=your_secret_here
   └─ DISCORD_REDIRECT_URI=http://your-domain.com/callback.php
```

---

## ✅ Test It!

```
Visit: http://your-domain.com/
       ↓
Click "Login with Discord"
       ↓
Authorize the app
       ↓
You're in! 🎉

Check analytics: http://your-domain.com/analytics.php
```

---

## 🚀 Alternative: Railway (Cloud Hosting)

If you want cloud hosting instead of your own server:

**👉 Deploy to Railway:**
https://railway.app/new

1. Connect your GitHub (RblxIsAwesome/Refresh-Tool)
2. Add MySQL database service
3. Set environment variables
4. Railway auto-deploys! 🎉

---

## 📱 All Links in One Place

| What | Link |
|------|------|
| **Create PR** | https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics |
| **Your Repo** | https://github.com/RblxIsAwesome/Refresh-Tool |
| **Discord Dev** | https://discord.com/developers/applications |
| **Railway** | https://railway.app/new |
| **Full Guide** | See MERGE_AND_DEPLOY.md in repo |

---

## 🆘 Problems?

### Can't merge?
- Make sure you're logged into GitHub
- You need write access to the repository

### Can't access server?
- Try Railway instead: https://railway.app/new
- It's free and auto-deploys!

### Discord OAuth not working?
- Make sure redirect URI matches exactly
- Include http:// or https://
- No trailing slash

### Database errors?
- Run: `mysql -u root -p -e "SHOW DATABASES;"`
- Check config/.env credentials

---

**That's it! 3 simple steps and you're live! 🎉**

First link to get started:
👉 https://github.com/RblxIsAwesome/Refresh-Tool/compare/copilot/add-database-integration-analytics
