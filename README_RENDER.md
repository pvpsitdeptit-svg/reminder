# Render Deployment Guide

## Free Tier Setup for Faculty Management System

### Overview
Render's free tier doesn't include managed MySQL, so we'll use external free MySQL services and deploy the PHP application on Render's free web service.

## 🚀 Quick Deployment Steps

### 1. Choose a Free MySQL Database

**Option A: TiDB Cloud (Recommended)**
- Free tier available
- MySQL-compatible
- Sign up: https://tidbcloud.com/

**Option B: FreeMySQLHosting**
- Completely free MySQL hosting
- Sign up: https://www.freemysqlhosting.net/

**Option C: Railway MySQL**
- Free tier with Railway
- Sign up: https://railway.app/

### 2. Prepare Your Database

After signing up for a MySQL service:
1. Create a new database named `reminder_db`
2. Note your connection details:
   - Host
   - Port
   - Username
   - Password
   - Database name

### 3. Deploy to Render

**Step 1: Connect Your Repository**
- Push your code to GitHub/GitLab
- Connect your repository to Render

**Step 2: Configure Environment Variables**
In your Render dashboard, add these environment variables:

```
DB_HOST=your_mysql_host
DB_NAME=reminder_db
DB_USER=your_mysql_username
DB_PASSWORD=your_mysql_password
DB_PORT=3306

FIREBASE_PROJECT_ID=your_firebase_project_id
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
```

**Step 3: Deploy**
- Render will automatically detect `render.yaml`
- Build and deploy your application
- Access at: `https://your-app-name.onrender.com`

## 📋 Required Files for Render

1. **render.yaml** - Render service configuration
2. **Dockerfile.render** - Optimized Dockerfile for Render
3. **config/database_render.php** - Database configuration
4. **apache.conf** - Apache configuration

## 🔧 Configuration Details

### Environment Variables Setup

**Database Variables:**
- `DB_HOST`: Your MySQL host (e.g., `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
- `DB_NAME`: Database name (usually `reminder_db`)
- `DB_USER`: Your MySQL username
- `DB_PASSWORD`: Your MySQL password
- `DB_PORT`: MySQL port (usually `3306` or `4000` for TiDB)

**Firebase Variables:**
- `FIREBASE_PROJECT_ID`: Your Firebase project ID
- `FIREBASE_DATABASE_URL`: Your Firebase Realtime Database URL

### Port Configuration
- Render free tier uses port `10000`
- Apache is configured to listen on port `10000`
- No need to change port in your application code

## 🌐 Free MySQL Options Comparison

| Service | Free Tier Limits | Pros | Cons |
|---------|------------------|------|------|
| **TiDB Cloud** | 5GB storage, 1 vCPU | MySQL-compatible, reliable | Requires credit card |
| **FreeMySQLHosting** | 50MB storage | No credit card needed | Limited storage, ads |
| **Railway** | 500hrs/month | Easy setup | Requires GitHub account |

## 🔒 Security Considerations

1. **Environment Variables**: Never commit credentials to Git
2. **Firebase Security**: Update Firebase rules for production
3. **Database Access**: Use SSL connections if available
4. **Error Handling**: Don't expose database errors in production

## 🐛 Troubleshooting

### Common Issues:

**Database Connection Failed:**
- Verify environment variables in Render dashboard
- Check if MySQL service allows external connections
- Ensure database user has proper permissions

**502 Bad Gateway:**
- Check Render build logs
- Verify Docker build completed successfully
- Ensure Apache is running on port 10000

**Firebase Connection Issues:**
- Verify Firebase project credentials
- Check Firebase security rules
- Ensure Realtime Database is enabled

### Debug Commands:

```bash
# Check Render logs
# In Render dashboard: Logs tab

# Test database connection locally
php -r "require 'config/database_render.php'; echo 'DB connected!';"
```

## 📈 Scaling Considerations

When you need to scale beyond free tier:

1. **Database**: Upgrade to paid MySQL plan
2. **Application**: Upgrade to Render Standard plan
3. **CDN**: Add Cloudflare for static assets
4. **Monitoring**: Add error tracking (Sentry, etc.)

## 🎯 Production Checklist

- [ ] Configure all environment variables
- [ ] Test database connection
- [ ] Verify Firebase configuration
- [ ] Set up custom domain (optional)
- [ ] Configure SSL (automatic on Render)
- [ ] Test all application features
- [ ] Set up monitoring/alerts
- [ ] Backup strategy for database

## 📞 Support

- **Render Docs**: https://render.com/docs
- **TiDB Cloud Docs**: https://docs.pingcap.com/tidb-cloud/
- **Firebase Docs**: https://firebase.google.com/docs

Your Faculty Management System is ready for Render deployment! 🚀
