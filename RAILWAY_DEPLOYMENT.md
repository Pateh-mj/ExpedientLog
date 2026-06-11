# Railway Deployment Guide

This document outlines the complete process to deploy **ExpedientLog** on Railway.

## Overview

ExpedientLog is a PHP-based web application that manages expedient logs and tickets. It uses:
- **PHP 8.2** with Apache
- **MySQL 8.0+** for data persistence
- **Object Storage (S3)** for persistent file uploads (optional but recommended)

---

## Prerequisites

- GitHub account with this repository pushed
- Railway account (https://railway.app)
- Basic knowledge of environment variables and database setup

---

## Step 1: Prepare & Push Repository to GitHub

### 1.1 Commit Docker Files
From the repository root, commit the containerization setup:

```bash
git add Dockerfile .dockerignore .env.example
git commit -m "chore: add Docker and Railway deployment files

- Add Dockerfile with PHP 8.2 + Apache
- Add .dockerignore to optimize image size
- Update .env.example with DB and S3 config examples
- Set public/ as document root
- Configure storage directory permissions"
git push origin main
```

### 1.2 Verify on GitHub
Navigate to https://github.com/Pateh-mj/ExpedientLog and confirm all files are pushed.

---

## Step 2: Create Railway Project & Connect Repository

### 2.1 Create a New Project on Railway
1. Go to [railway.app](https://railway.app)
2. Click **New Project**
3. Select **Deploy from GitHub**
4. Authorize Railway to access your GitHub account
5. Select the **ExpedientLog** repository
6. Railway will auto-detect the `Dockerfile` and begin building

### 2.2 Monitor Build
- Railway will start building the Docker image
- Check the **Build Logs** to ensure no errors occur
- Once built, the service will be deployed

---

## Step 3: Add Managed MySQL Database

### 3.1 Provision MySQL Plugin
1. In the Railway project dashboard, click **Add Service** (or **+** button)
2. Select **MySQL** from the available plugins
3. Railway will automatically provision a MySQL instance with:
   - Random strong password
   - Connection variables set as environment variables

### 3.2 View Database Credentials
1. Click on the **MySQL** service card
2. Go to the **Variables** tab
3. Note the auto-generated variables (e.g., `MYSQL_HOST`, `MYSQL_PASSWORD`, etc.)

---

## Step 4: Configure Environment Variables

### 4.1 Map Database Variables
Railway's MySQL plugin provides variables that differ from your app's expected names. Set these in your **ExpedientLog** service environment:

In the **ExpedientLog** service → **Variables** tab, add:

| Variable | Value | Source |
|----------|-------|--------|
| `DB_HOST` | `${{ services.mysql.host }}` | Railway MySQL host |
| `DB_PORT` | `${{ services.mysql.port }}` | Railway MySQL port |
| `DB_NAME` | `${{ services.mysql.MYSQL_DATABASE }}` | Railway DB name |
| `DB_USER` | `${{ services.mysql.MYSQL_USER }}` | Railway DB user |
| `DB_PASS` | `${{ services.mysql.MYSQL_PASSWORD }}` | Railway DB password |

*Tip: Use Railway's template syntax `${{ services.<service>.<variable> }}` to reference other services.*

### 4.2 Set Application Variables
| Variable | Value |
|----------|-------|
| `APP_NAME` | `ExpedientLog` |
| `APP_DEBUG` | `false` |
| `APP_TIMEZONE` | `Africa/Lusaka` |
| `APP_ENV` | `production` |

### 4.3 Optional: Object Storage (S3)
For persistent file uploads (not ephemeral):
| Variable | Value |
|----------|-------|
| `S3_BUCKET` | Your S3 bucket name |
| `S3_KEY` | AWS Access Key ID |
| `S3_SECRET` | AWS Secret Access Key |
| `S3_REGION` | e.g., `us-east-1` |

*(See the application code for integration with S3 uploads.)*

---

## Step 5: Initialize Database Schema

### 5.1 Option A: Using Railway SQL Editor (Easiest)
1. Click on the **MySQL** service
2. Go to the **Data** tab
3. Open the **SQL Editor**
4. Copy and paste the contents of `schema.sql` from your repository
5. Execute the SQL

### 5.2 Option B: Using MySQL CLI (Local)
From your local machine:

```bash
# Download schema.sql (if not already local)
git clone https://github.com/Pateh-mj/ExpedientLog.git
cd ExpedientLog

# Set variables from Railway (get from MySQL service variables tab)
MYSQL_HOST="<your-railway-host>"
MYSQL_PORT="<your-railway-port>"
MYSQL_USER="<your-railway-user>"
MYSQL_PASSWORD="<your-railway-password>"
MYSQL_DATABASE="<your-railway-database>"

# Run schema
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < schema.sql
```

### 5.3 Verify
After running, confirm tables exist:

```sql
SHOW TABLES;
```

---

## Step 6: Deploy & Verify

### 6.1 Trigger Deployment
Once environment variables are set:
1. Go back to the **ExpedientLog** service
2. Click **Redeploy** (or the deployment will auto-trigger on env change)
3. Monitor the **Deployment Logs**

### 6.2 Check Logs
1. Click the **Logs** tab
2. Look for:
   - `apache2-foreground` started successfully
   - No PHP errors in stderr
   - Database connection confirmed

### 6.3 Access Your App
1. Railway assigns a public URL (e.g., `https://expedientlog-production.railway.app`)
2. Click the **View** button or copy the public URL
3. Verify the app loads and you can log in

---

## Step 7: File Uploads & Persistent Storage

### 7.1 Understand Railway's Ephemeral Filesystem
- Railway's default filesystem is **ephemeral**: files are lost on redeploy
- Uploaded files in `storage/uploads/` will be deleted

### 7.2 Options for Persistent Storage

**Option A: Use S3 (Recommended)**
- Configure AWS S3 credentials (see Step 4.3)
- Update file upload code in [src/Core/FileUpload.php](src/Core/FileUpload.php) to push files to S3
- Files persist across deployments

**Option B: Use Railway's Persistent Volume (Advanced)**
- Create a Railway volume and mount it to `/var/www/html/storage/uploads`
- Files persist but are not backed up by default

**Option C: Accept Ephemeral Storage**
- If uploads are temporary (e.g., processing then deleting), no action needed

---

## Troubleshooting

### App Not Starting
- Check **Logs** for PHP errors
- Verify all `DB_*` variables are set correctly
- Ensure database schema was initialized

### Database Connection Failed
- Confirm `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` match Railway's MySQL variables
- Test connection locally with the same credentials

### 403 Forbidden or 404 Errors
- Verify `.htaccess` is present and Apache `mod_rewrite` is enabled
- The Dockerfile enables `mod_rewrite`, so this should work

### Logs Not Appearing
- SSH into the container via Railway and check `/var/www/html/storage/php_errors.log`
- Set `APP_DEBUG=true` temporarily (then reset to `false` in production)

---

## Rollback & Scaling

### Rollback to Previous Version
1. In Railway, click on **Deployments**
2. Select a previous successful deployment
3. Click **Reactivate**

### Scale Horizontally
1. In the **ExpedientLog** service settings, increase **Replicas**
2. Railway will load-balance incoming requests

---

## Monitoring & Maintenance

### Enable Health Checks
Railway can auto-restart unhealthy instances. Ensure your app responds to HTTP 200 on `/` or a health endpoint.

### Review Logs Regularly
- Monitor **Logs** for errors or warnings
- Set up external log aggregation if needed

### Update Dependencies
- Keep PHP, MySQL, and libraries up to date
- Test updates in a staging environment first

---

## Success Criteria

Your deployment is successful when:
✅ App is accessible at the public Railway URL  
✅ Database tables are present and queries work  
✅ Login/authentication functions correctly  
✅ File uploads work (or expected error if S3 not configured)  
✅ Logs show no critical errors  

---

## Additional Resources

- [Railway Documentation](https://docs.railway.app)
- [Railway PHP Deployment Guide](https://docs.railway.app/deploy/deployments/php)
- [PHP on Railway](https://railway.app/starters/php)

---

**Last Updated:** 2026-06-12  
**Status:** Ready for Deployment
