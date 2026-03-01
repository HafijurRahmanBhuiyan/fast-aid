# FastAid - Emergency Medical Assistance System

A comprehensive emergency medical assistance platform connecting patients in need with verified medical volunteers. Built with PHP, MySQL, and Bootstrap.

## Features

### For Patients
- View nearby approved volunteers (filtered by location)
- Select a volunteer and send emergency requests
- Real-time request status tracking with AJAX
- Profile management with change password option
- SMS notifications on request updates

> **Note**: Google Maps integration is optional and disabled by default.

### For Volunteers
- Accept emergency requests from patients
- Complete requests and track history
- Profile management with change password option
- Real-time request notifications

> **Note**: Google Maps integration is optional and disabled by default. See "Google Maps Setup" below.

### For Admins
- Approve/reject volunteer registrations
- View all patients and volunteers
- Monitor all service requests
- Email notifications sent to approved volunteers

## Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: Bootstrap 5.3, Font Awesome 6.4
- **Maps**: Google Maps API (optional, disabled by default)
- **Security**: CSRF protection, prepared statements, session security

## Installation

### 1. Database Setup

Create a MySQL database and import the schema:

```bash
mysql -u root -p fastaid_db < database.sql
```

For enhanced features, also import:
```bash
mysql -u root -p fastaid_db < database_enhanced.sql
```

### 2. Configuration

Edit `config/database.php`:
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'fastaid_db');
```

### 3. Google Maps API (Optional)

Google Maps is disabled by default. To enable it:

1. Get an API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Enable "Maps JavaScript API" and "Places API"
3. Replace `YOUR_GOOGLE_MAPS_API_KEY` in:
   - `patient/dashboard.php` (line 32)
   - `volunteer/dashboard.php` (line 37)

The dashboards work fully without Google Maps.

### 4. Run the Application

1. git repo: `https://github.com/HafijurRahmanBhuiyan/fast-aid`
2. From here clone the code on your local device
3. Put it into the htdocs file (xampp --> htdocs)
4. Access at: `http://localhost/fast-aid`

## Default Login

### Admin
- Email: `admin@fastaid.com`
- Password: `admin123`

### Or Register New Users
- Patients and volunteers can self-register
- Volunteers require admin approval

## Project Structure

```
fast-aid/
├── config/
│   ├── database.php      # Database & security functions
│   └── notifications.php # Email/SMS service
├── api/
│   └── requests.php      # AJAX endpoints
├── includes/
│   └── logout.php       # Logout handler
├── admin/
│   ├── dashboard.php    # Admin dashboard
│   ├── volunteers.php  # Volunteer management
│   ├── patients.php    # Patient list
│   ├── requests.php    # Service requests
│   └── approve_volunteer.php
├── patient/
│   ├── dashboard.php    # Patient dashboard
│   └── profile.php      # Profile management
├── volunteer/
│   ├── dashboard.php    # Volunteer dashboard
│   ├── requests.php     # Request history
│   ├── profile.php      # Profile management
│   ├── accept_request.php
│   └── complete_request.php
├── assets/
│   └── css/
│       └── style.css    # Custom styles
├── index.php            # Landing page
├── signin.php           # Login
├── signup.php           # Registration
└── database.sql        # Database schema
```

## Security Features

- **CSRF Protection**: All forms use tokens
- **Prepared Statements**: SQL injection prevention
- **Session Security**: HttpOnly cookies, secure parameters
- **Input Validation**: Server-side validation
- **Role-based Access**: Dashboard authorization checks
- **Password Hashing**: bcrypt for password storage

## API Endpoints

| Action | Method | Description |
|--------|--------|-------------|
| `create_request` | POST | Create emergency request |
| `get_request_status` | POST | Get current request status |
| `accept_request` | POST | Volunteer accepts request |
| `complete_request` | POST | Volunteer completes request |
| `get_volunteers` | GET | List nearby volunteers |
| `update_profile` | POST | Update user profile |
| `change_password` | POST | Change password |
| `get_volunteer_stats` | GET | Get volunteer statistics |

## AJAX Features

- Real-time status polling (every 5 seconds)
- Dynamic volunteer list loading
- Profile updates without page refresh
- Password change with validation

## Notifications

### Email
- Volunteer approval notification
- HTML templates included

### SMS
- Emergency request alerts
- Request accepted/completed notifications
- Currently logs to error log (integrate Twilio for production)

## Google Maps Integration (Optional)

Google Maps is optional and disabled by default. The dashboards work without it.

When enabled, features include:
- Interactive map display
- User location detection
- Places autocomplete for addresses
- Markers for patients and volunteers

To enable, follow the steps in the Installation section above.

## Future Enhancements

- Push notifications
- Mobile app API
- Payment integration
- Review/rating system
- Emergency hotline integration

## Support

For issues or questions, please open an issue on GitHub. Or WhatsApp: 01786444587

## Developer

Name: Hafijur Rahman Bhuiyan
Phone: 01786444587 or 01533013497
