# Faculty Management System

A comprehensive faculty leave management system with Firebase integration and CSV upload capabilities.

## Features

- **Faculty Leave Management**: Add, edit, and manage faculty leave records
- **CSV Upload/Download**: Bulk upload faculty data with template support
- **Leave Balance Reports**: Detailed leave balance and history tracking
- **Firebase Integration**: Real-time database for leave records
- **Admin Dashboard**: Centralized management interface
- **Leave Types Support**: CL, EL, ML, HPL, OD, CCL, LOP
- **Session Management**: FN, AN, Full day options for applicable leave types

## Quick Start

### Local Development (XAMPP)

1. Clone the repository
2. Place in `htdocs/reminder/` directory
3. Configure Firebase in `config/firebase.php`
4. Access at `http://localhost/reminder`

### Docker Deployment

```bash
docker-compose up -d --build
```

### Render Deployment

See `README_RENDER.md` for detailed Render deployment instructions.

## Configuration

### Firebase Setup
1. Create Firebase project
2. Enable Realtime Database
3. Update `config/firebase.php` with your credentials

### Database
- Uses Firebase Realtime Database for primary storage
- Optional MySQL support for additional features

## File Structure

```
reminder/
├── config/
│   └── firebase.php          # Firebase configuration
├── templates/
│   └── *.csv                 # CSV templates
├── index.php                 # Main dashboard
├── manage_leave_availed.php  # Leave management
├── leave_balance_report.php # Balance reports
├── templates.php             # Template management
├── upload_faculty_leaves.php # CSV upload handler
├── Dockerfile                # Docker configuration
├── Dockerfile.render         # Render-specific Dockerfile
├── docker-compose.yml        # Docker Compose setup
├── render.yaml              # Render deployment config
└── README_RENDER.md         # Render deployment guide
```

## Deployment Options

### 1. Local XAMPP
- Traditional PHP development
- MySQL database support
- Easy debugging

### 2. Docker
- Containerized deployment
- MySQL included
- Portable environment

### 3. Render (Free Tier)
- Free web hosting
- External MySQL required
- Automatic SSL

## Leave Types

| Type | Full Name | Session Options |
|------|-----------|-----------------|
| CL   | Casual Leave | FN, AN, Full Day |
| EL   | Earned Leave | Full Day |
| ML   | Medical Leave | Full Day |
| HPL  | Half-pay Leave | Full Day |
| OD   | On Duty | Full Day |
| CCL  | Child Care Leave | FN, AN, Full Day |
| LOP  | Loss of Pay | Full Day |

## Support

For deployment issues:
- Docker: See `README_DOCKER.md`
- Render: See `README_RENDER.md`
- Local: Check XAMPP configuration

## License

MIT License
