# InfinityFree Deployment Guide

Deploy **ExpedientLog** to InfinityFree (free tier, no card required).

---

## Quick Summary

1. Create InfinityFree account (free)
2. Upload files via FTP
3. Create MySQL database via cPanel
4. Run `schema.sql` 
5. Configure `.env`
6. Done! 🚀

---

## Step 1: Create InfinityFree Account

1. Go to [infinityfree.net](https://www.infinityfree.net)
2. Sign up (no credit card needed)
3. Create a free account and verify email
4. You'll get:
   - Free domain (optional, or use existing)
   - FTP credentials
   - cPanel access
   - MySQL database access

---

## Step 2: Get FTP & Database Credentials

### From Your InfinityFree Dashboard:
1. Click **Manage** on your account
2. Go to **FTP Accounts**
3. Note down:
   - **FTP Host**
   - **FTP Username**
   - **FTP Password**

4. Go to **MySQL Databases**
5. Create a new database:
   - **Database Name:** `exp_log`
6. Note down:
   - **Database Host** (usually `localhost`)
   - **Database Name** (`exp_log`)
   - **Database User** (auto-generated)
   - **Database Password** (auto-generated)

---

## Step 3: Upload Files via FTP

### Using FileZilla (Free & Easy):
1. Download [FileZilla](https://filezilla-project.org/)
2. Open → Site Manager → New Site
3. Enter:
   - **Protocol:** FTP
   - **Host:** (from InfinityFree FTP Host)
   - **Username:** (from InfinityFree FTP Username)
   - **Password:** (from InfinityFree FTP Password)
4. Click Connect
5. Navigate to the `htdocs` or `public_html` folder
6. Drag & drop all files from your project (except `.git`, `node_modules`, `vendor`)
   - Upload: `public/`, `src/`, `config/`, `storage/`, `schema.sql`, etc.

---

## Step 4: Create Database Tables

### Option A: Using InfinityFree cPanel
1. Go to your InfinityFree **cPanel** (Account Dashboard → cPanel)
2. Find **phpMyAdmin**
3. Log in with your database credentials
4. Select the `exp_log` database
5. Click the **SQL** tab
6. Copy & paste contents of `schema.sql`
7. Click **Go** to execute

### Option B: Using Command Line (if SSH available)
```bash
mysql -h localhost -u DB_USER -p DB_NAME < schema.sql
# Enter password when prompted
```

---

## Step 5: Configure .env File

### Create `.env` in the project root (uploaded via FTP):

```
APP_NAME=ExpedientLog
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Africa/Lusaka
APP_URL=https://your-domain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=exp_log
DB_USER=database_user_from_infinityfree
DB_PASS=database_password_from_infinityfree
```

**Upload this file via FTP** to your project root.

---

## Step 6: Set Permissions (Important!)

Via cPanel File Manager or FTP:
1. Right-click `storage/` folder → **Permissions**
2. Set to **755** (or **777** for uploads)
3. Recursively apply

This allows the app to write uploaded files and logs.

---

## Step 7: Test Your App

1. Go to `https://your-domain.com` (or InfinityFree's provided domain)
2. You should see the **ExpedientLog login page**
3. Check logs in `storage/php_errors.log` if issues

---

## Troubleshooting

### "Service Temporarily Unavailable"
- Check `storage/php_errors.log` for errors
- Verify `DB_HOST`, `DB_USER`, `DB_PASS` are correct
- Make sure `schema.sql` was executed (tables exist in database)

### Database Connection Failed
- Log into cPanel → phpMyAdmin → select `exp_log`
- Run: `SELECT 1;` to test database works
- Verify credentials in `.env` match exactly

### 404 / Page Not Found
- Verify `.htaccess` file is uploaded (enables URL rewriting)
- Check file permissions on `public/` folder
- Ensure `APP_URL` in `.env` matches your actual domain

### Files Can't Upload
- Set `storage/uploads` permissions to **777**
- Check disk space quota in InfinityFree dashboard

---

## Using a Custom Domain (Optional)

1. Buy domain (GoDaddy, Namecheap, etc.)
2. In InfinityFree → **Domains** → **Point Domain**
3. Update nameservers at your domain registrar
4. Wait 24-48 hours for DNS propagation
5. Update `APP_URL` in `.env`

---

## Limits & Notes

**InfinityFree Free Tier Includes:**
- 5GB Disk Space
- Unlimited Bandwidth
- MySQL database
- 24/7 Uptime guarantee
- Free SSL (HTTPS)

**Limitations:**
- May have fair-use policy for CPU/memory
- File uploads stored locally (not cloud backup)
- 1-2 second startup time on cold boot

---

## Backup Your Database

### Regularly backup via cPanel phpMyAdmin:
1. Go to phpMyAdmin → select `exp_log`
2. Click **Export**
3. Select format: **SQL**
4. Click **Go** to download

---

## Upgrade to Paid (When Ready)

If you outgrow free tier:
- InfinityFree offers paid plans
- Or use Render, Railway, or Heroku (more powerful)

---

## Success Checklist ✅

✅ Files uploaded via FTP to `htdocs` or `public_html`  
✅ Database `exp_log` created with tables (from schema.sql)  
✅ `.env` file configured with correct DB credentials  
✅ `storage/` and `storage/uploads` have write permissions  
✅ `.htaccess` file present in project root  
✅ App loads at `https://your-domain.com`  
✅ Can log in and navigate app  
✅ No errors in `storage/php_errors.log`  

---

## Resources

- [InfinityFree Documentation](https://docs.infinityfree.net)
- [InfinityFree cPanel Guide](https://docs.infinityfree.net/docs/cpanel-guide)
- [FileZilla FTP Client](https://filezilla-project.org/)
- [ExpedientLog README](README.md)

---

**Last Updated:** 2026-06-12  
**Database:** MySQL  
**Hosting:** InfinityFree  
**Status:** Ready for Deployment
