# Render Deployment Guide (PostgreSQL)

This document outlines the complete process to deploy **ExpedientLog** on Render using **PostgreSQL**.

## Overview

ExpedientLog is a PHP-based web application that manages expedient logs and tickets. On Render, it will run as:
- **Web Service:** PHP 8.2 with Apache (Docker)
- **Managed Database:** PostgreSQL 14+ (Render's database service)
- **Persistent Storage:** Render Disk for uploaded files

---

## Prerequisites

- GitHub account with this repository pushed
- Render account (https://render.com)
- PostgreSQL client (optional, for local schema setup)

---

## Step 1: Repository Ready ✅

Your repository is prepared with:
- ✅ `Dockerfile` configured for PHP 8.2 + Apache + PostgreSQL support
- ✅ `schema.sql` migrated to PostgreSQL syntax
- ✅ [src/Core/DB.php](src/Core/DB.php) updated to use PostgreSQL PDO driver
- ✅ `.env.example` configured for PostgreSQL
- ✅ Latest commit pushed to GitHub main branch

---

## Step 2: Create Render Account & Connect GitHub

### 2.1 Sign Up / Log In
1. Go to [render.com](https://render.com)
2. Click **Sign Up** (or log in if you have an account)
3. Choose **GitHub** as auth provider
4. Authorize Render to access your GitHub repositories

### 2.2 Create New Web Service
1. From the Render dashboard, click **New +** → **Web Service**
2. Select **Build and deploy from a Git repository**
3. Search for **ExpedientLog** (Pateh-mj/ExpedientLog)
4. Click **Connect** next to your repository

---

## Step 3: Configure Web Service

### 3.1 Basic Settings
In the service creation form, set:

| Setting | Value |
|---------|-------|
| **Name** | `expedientlog` (or your choice) |
| **Environment** | `Docker` |
| **Branch** | `main` |
| **Root Directory** | `.` (leave blank or use root) |
| **Dockerfile Path** | `./Dockerfile` (default) |

### 3.2 Instance Type
- Start with **Starter** ($7/month) or **Standard** for production load
- Can upgrade later

### 3.3 Create Service
Click **Create Web Service**. Render will begin building the Docker image.

**Leave environment variables blank for now** — we'll add them after the database is created.

---

## Step 4: Create Managed PostgreSQL Database

### 4.1 Provision PostgreSQL
1. From the Render dashboard, click **New +** → **PostgreSQL**
2. Configure:

| Setting | Value |
|---------|-------|
| **Name** | `expedientlog-db` |
| **Database** | `exp_log` |
| **User** | `postgres` (auto-generated or custom) |
| **Region** | Same as web service (e.g., Ohio, Oregon) |
| **PostgreSQL Version** | 14+ (default recommended) |
| **Pricing Plan** | **Starter** ($15/month) or higher |

3. Click **Create Database**
4. Render will provision PostgreSQL (takes ~5-10 minutes)

### 4.2 Get Connection Details
Once the database is running:
1. Click on the **expedientlog-db** service
2. Go to the **Connections** tab
3. Copy the **Internal Database URL**:
   ```
   postgresql://postgres:your_password@internal-hostname:5432/exp_log
   ```

Extract the values:
- **Host:** `internal-hostname` (the part after `@`)
- **Port:** `5432`
- **Database:** `exp_log`
- **User:** `postgres`
- **Password:** (the part after `:` before `@`)

---

## Step 5: Set Environment Variables on Web Service

### 5.1 Add Variables
1. Go to your **expedientlog** web service
2. Click **Environment** (left sidebar)
3. Add the following variables:

#### Database Variables
```
DB_HOST=<internal-host-from-db-connections-tab>
DB_PORT=5432
DB_NAME=exp_log
DB_USER=postgres
DB_PASS=<password-from-database>
```

#### Application Variables
```
APP_NAME=ExpedientLog
APP_DEBUG=false
APP_TIMEZONE=Africa/Lusaka
APP_ENV=production
```

#### Optional: S3 for Persistent Uploads (Recommended)
```
S3_BUCKET=your-bucket-name
S3_KEY=your-aws-access-key
S3_SECRET=your-aws-secret-key
S3_REGION=us-east-1
```

### 5.2 Save & Redeploy
1. Click **Save Changes**
2. Render will automatically redeploy with the new environment variables
3. Check **Logs** to confirm the service starts without errors

---

## Step 6: Initialize Database Schema

### 6.1 Option A: Using psql (Recommended for Render)
From your local machine:

```bash
# Get credentials from Render database Connections tab
# IMPORTANT: For external connections, add .postgres.render.com suffix
POSTGRES_HOST="dpg-d8ll6mk8aovs73dmqhb0-a.postgres.render.com"
POSTGRES_PORT="5432"
POSTGRES_USER="postgres"
POSTGRES_PASSWORD="your_password_from_render"
POSTGRES_DATABASE="exp_log"

# Run the schema
export PGPASSWORD="$POSTGRES_PASSWORD"
psql -h "$POSTGRES_HOST" -p "$POSTGRES_PORT" -U "$POSTGRES_USER" -d "$POSTGRES_DATABASE" -f schema.sql
```

**Note:** The hostname format depends on where you're connecting from:
- **From Render web service (internal):** `dpg-xxxxx.internal`
- **From local machine (external):** `dpg-xxxxx.postgres.render.com`

### 6.2 Option B: Using DBeaver or pgAdmin (GUI)
1. Download and install [DBeaver](https://dbeaver.io) or use [pgAdmin](https://www.pgadmin.org)
2. Create a new PostgreSQL connection using the Render database credentials (from Step 4.2)
3. Open the SQL editor and paste the contents of `schema.sql`
4. Execute the SQL

### 6.3 Verify Tables
Log into your database and run:

```bash
# Use the same host as above (.postgres.render.com for external connections)
POSTGRES_HOST="dpg-d8ll6mk8aovs73dmqhb0-a.postgres.render.com"
POSTGRES_PORT="5432"
POSTGRES_USER="postgres"
POSTGRES_PASSWORD="your_password_from_render"
POSTGRES_DATABASE="exp_log"

export PGPASSWORD="$POSTGRES_PASSWORD"
psql -h "$POSTGRES_HOST" -p "$POSTGRES_PORT" -U "$POSTGRES_USER" -d "$POSTGRES_DATABASE" -c "SELECT table_name FROM information_schema.tables WHERE table_schema='public';"
```

You should see:
```
      table_name
─────────────────
 users
 tickets
 announcements
(3 rows)
```

---

## Step 7: Verify Deployment

### 7.1 Check Web Service Status
1. Go to the **expedientlog** web service on Render dashboard
2. Check the **Status** indicator:
   - 🟢 **Running** = Service is live
   - 🟡 **Deploying** = Still building/starting
   - 🔴 **Failed** = Check logs

### 7.2 View Logs
1. Click the **Logs** tab
2. Look for:
   - `apache2-foreground` starting successfully
   - No fatal PHP errors
   - No PDO connection errors

**Example healthy log output:**
```
AH00094: Command line: 'apache2 -D FOREGROUND'
```

### 7.3 Access Your App
1. Go to the **Render Dashboard** → **expedientlog** service
2. Look for the **URL** at the top (e.g., `https://expedientlog.onrender.com`)
3. Click the link or copy it to your browser
4. You should see the ExpedientLog login page

### 7.4 Test Functionality
- ✅ Load the homepage / login page
- ✅ Verify database connection (check logs for errors)
- ✅ Test authentication (create test user or log in)
- ✅ Navigate to dashboard, announcements, etc.

---

## Step 8: Persistent Storage for File Uploads

### 8.1 Understanding Render's Filesystem
- Render web services have **ephemeral** storage by default
- Files in `/var/www/html/storage/uploads` are lost on redeploy/restart

### 8.2 Add a Persistent Disk

1. Go to the **expedientlog** web service
2. Click **Disks** (left sidebar)
3. Click **Add Disk**
4. Configure:

| Setting | Value |
|---------|-------|
| **Name** | `expedientlog-uploads` |
| **Mount Path** | `/var/www/html/storage/uploads` |
| **Size** | 10GB ($5/month per 10GB) |

5. Click **Create Disk**

### 8.3 Verify Disk is Mounted
- Service will automatically redeploy
- Check logs for successful start
- Uploaded files will now persist across restarts/redeploys

### 8.4 Alternative: Use S3 (Recommended for Scale)
Instead of a Render Disk:
1. Configure AWS S3 credentials (see Step 5.1)
2. Update file upload code in [src/Core/FileUpload.php](src/Core/FileUpload.php) to use S3
3. More cost-effective for large files
4. Better for multi-instance deployments

---

## Step 9: Custom Domain (Optional)

### 9.1 Add Custom Domain
1. Go to the **expedientlog** web service
2. Click **Settings** (left sidebar)
3. Scroll to **Custom Domain**
4. Enter your domain (e.g., `logs.example.com`)
5. Render will provide DNS instructions (CNAME)
6. Update your DNS provider to point to Render

### 9.2 SSL/TLS
- Render automatically provisions free SSL certificates via Let's Encrypt
- Your app will be accessible via `https://logs.example.com`

---

## Troubleshooting

### Service Fails to Start
**Check logs:**
1. Go to **Logs** tab
2. Look for errors like `DB connection failed` or PHP syntax errors
3. Common issues:
   - `DB_HOST` or `DB_PASS` incorrect → verify in database Connections tab
   - Port is `5432` (not `3306` like MySQL)
   - Missing PostgreSQL PDO extension → Dockerfile includes it

**Solution:**
- Fix environment variables
- Click **Rerun latest deploy** to redeploy

### Can't Connect to Database
1. Verify database service is **Running** (check database dashboard)
2. **Confirm hostname format:**
   - **For Render web service:** Use `dpg-xxxxx.internal` (copy from Internal Database URL)
   - **For local machine:** Use `dpg-xxxxx.postgres.render.com` (copy from External Database URL)
3. Verify you're using user `postgres` (not `root`)
4. Verify port is `5432` (PostgreSQL default)
5. Test locally:
   ```bash
   psql -h dpg-xxxxx.postgres.render.com -p 5432 -U postgres -d exp_log -c "SELECT 1;"
   ```
   (It will prompt for password — paste the password from Render)

### 403 Forbidden or 404 Errors
- Verify `.htaccess` is in the repo (it is)
- Dockerfile enables `mod_rewrite` (correct)
- Try accessing `https://yoururl/public/index.php` directly
- Check router in [src/Core/Router.php](src/Core/Router.php)

### Database Timezone Issues
- PostgreSQL timezone is set via `SET timezone` in [src/Core/DB.php](src/Core/DB.php)
- Uses `APP_TIMEZONE` environment variable
- Default: `Africa/Lusaka` (UTC+2)

### Files Lost After Restart
- Confirm persistent disk is mounted at `/var/www/html/storage/uploads`
- Check **Disks** tab on web service
- Use S3 for more reliability (Step 8.4)

---

## Monitoring & Maintenance

### 1. Auto-Deploy from GitHub
- Already enabled by default
- Any commit to `main` triggers redeploy

### 2. Monitor Service Health
- Render dashboard shows CPU, memory, request metrics
- Set up alerts: **Render Dashboard** → **Account Settings** → **Notifications**

### 3. Database Backups
- Render's managed PostgreSQL includes daily backups (retention by plan)
- Download backups from database service dashboard if needed

### 4. Scaling
- Increase instance size if traffic grows
- Database can be scaled separately
- Use Render analytics to monitor performance

---

## Cost Estimation (as of 2026)

| Service | Tier | Monthly Cost |
|---------|------|--------------|
| Web Service (PHP + Apache) | Starter | $7 |
| PostgreSQL Database | Starter | $15 |
| Persistent Disk (10GB) | - | $5 |
| **Total** | | **~$27/month** |

*Higher tiers and S3 storage will increase costs.*

---

## Success Criteria ✅

Your deployment is successful when:

✅ Web service status is **Running** (green)  
✅ PostgreSQL database is **Running** (green)  
✅ App loads at your Render URL (https://...)  
✅ Can navigate to login page  
✅ Database tables exist (verified via psql)  
✅ Logs show no critical errors  
✅ File uploads work (or S3 configured)  
✅ Persistent disk mounted (if using disk for uploads)  

---

## Next Steps Summary

1. ✅ **Repository updated** (PostgreSQL schema, DB.php, Dockerfile)
2. ✅ **Code pushed to GitHub**
3. **Create Render web service** from GitHub integration
4. **Create Render PostgreSQL database**
5. **Set environment variables** on web service (DB_HOST, DB_PASS, etc.)
6. **Initialize schema** (`schema.sql`) via psql or GUI
7. **Verify** app is running and accessible
8. **Add persistent disk** for file uploads (or use S3)

---

## PostgreSQL vs MySQL: Key Differences

| Feature | MySQL | PostgreSQL |
|---------|-------|------------|
| Default port | 3306 | 5432 |
| Default user | root | postgres |
| Boolean type | TINYINT(1) | BOOLEAN |
| Auto-increment | AUTO_INCREMENT | SERIAL |
| String escaping | Backticks | Double quotes or unquoted |
| Timezone | SET time_zone | SET timezone |
| JSON support | Limited | Excellent |
| Transactions | ACID | ACID (stricter) |

---

## Additional Resources

- [Render Documentation](https://render.com/docs)
- [Render PostgreSQL Guide](https://render.com/docs/postgresql)
- [Render Docker Guide](https://render.com/docs/docker)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [PHP PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php)

---

**Last Updated:** 2026-06-12  
**Database:** PostgreSQL  
**Status:** Ready for Render Deployment


