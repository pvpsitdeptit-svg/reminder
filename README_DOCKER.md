# Docker Deployment Guide

## Quick Start

### Prerequisites
- Docker installed on your system
- Docker Compose installed

### Deployment Steps

1. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Access the application:**
   - Web Application: http://localhost:8080
   - MySQL Database: localhost:3306

### Database Configuration

**MySQL Connection Details:**
- Host: db (within Docker network) or localhost:3306 (from host)
- Database: reminder_db
- Username: reminder_user
- Password: reminder_password
- Root Password: root_password

### Environment Variables

You can customize the configuration by modifying the `docker-compose.yml` file:

```yaml
environment:
  MYSQL_ROOT_PASSWORD: your_root_password
  MYSQL_DATABASE: your_database_name
  MYSQL_USER: your_username
  MYSQL_PASSWORD: your_password
```

### Useful Commands

**Start containers:**
```bash
docker-compose up -d
```

**Stop containers:**
```bash
docker-compose down
```

**View logs:**
```bash
docker-compose logs -f web
docker-compose logs -f db
```

**Access container shell:**
```bash
docker-compose exec web bash
docker-compose exec db mysql -u reminder_user -p reminder_db
```

### Important Notes

1. **Firebase Configuration:** Make sure your Firebase credentials in `config/firebase.php` are properly configured for production use.

2. **File Permissions:** The Docker container automatically sets proper file permissions.

3. **Data Persistence:** MySQL data is persisted in Docker volumes.

4. **Port Configuration:** The web server runs on port 8080 by default to avoid conflicts with other services.

### Production Considerations

For production deployment, consider:

1. **Environment Variables:** Move sensitive data to environment variables
2. **SSL/TLS:** Configure HTTPS with SSL certificates
3. **Backup Strategy:** Implement regular database backups
4. **Security:** Update default passwords and secure Firebase credentials
5. **Resource Limits:** Set appropriate memory and CPU limits in docker-compose.yml

### Troubleshooting

**If you get permission errors:**
```bash
sudo chown -R www-data:www-data /path/to/your/project
```

**If the database connection fails:**
- Ensure the MySQL container is running
- Check the database credentials in your PHP files
- Verify the database name and user permissions

**If Apache doesn't start:**
- Check the Apache logs: `docker-compose logs web`
- Ensure port 8080 is not already in use
