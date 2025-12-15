# Dwellscape Project Structure

This document describes the organized folder structure of the Dwellscape Staycation project.

## 📁 Folder Structure

```
dwellscape/
├── api/                    # API endpoints and webhooks
│   ├── check_payment.php
│   ├── check_room_availability.php
│   ├── create_payment.php
│   ├── get_available_rooms.php
│   ├── get_booked_dates.php
│   ├── get_bookings.php
│   ├── save_suggestion.php
│   └── webhook_paymongo.php
│
├── assets/                 # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── img/
│
├── config/                 # Configuration files
│   ├── database.php
│   ├── email.php
│   └── paymongo.php
│
├── database/               # SQL files and database scripts
│   ├── add_name_fields.sql
│   ├── database_dump.sql
│   └── database_migration_bookings.sql
│
├── docs/                   # Documentation files
│   ├── IMAGE_FIXES_SUMMARY.md
│   ├── NGROK_SETUP.md
│   ├── PROJECT_STRUCTURE.md (this file)
│   ├── QUICK_WEBHOOK_SETUP.md
│   ├── README_EMAIL_SETUP.md
│   ├── README_PAYMONGO_SETUP.md
│   ├── README.md
│   └── WEBHOOK_SETUP_GUIDE.md
│
├── includes/              # Shared PHP includes
│   └── email_sender.php
│
├── pictures/              # Image assets
│   ├── dashboard1.png
│   ├── dashboard2.png
│   ├── dashboard3.png
│   ├── dashboard4.png
│   └── ...
│
├── scripts/                # Utility and setup scripts
│   ├── add_all_gallery_images.php
│   ├── add_name_fields_to_db.php
│   ├── check_images.php
│   ├── create_admin.php
│   ├── fix_gallery_images.php
│   ├── seed_gallery.php
│   ├── seed_rooms.php
│   ├── setup_env.php
│   ├── start_ngrok.bat
│   └── test_webhook_config.php
│
├── uploads/               # User-uploaded files
│   ├── gallery/
│   ├── panorama/
│   ├── profile_pictures/
│   └── rooms/
│
├── admin/                 # Admin panel files
│   ├── admin.php
│   ├── admin_bookings.php
│   ├── admin_rooms.php
│   └── includes/
│
├── vendor/                # Composer dependencies
│   └── phpmailer/
│
├── Root PHP Files         # Main application pages
│   ├── home.php           # Homepage
│   ├── dashboard.php      # User dashboard
│   ├── bookings.php       # Booking page
│   ├── login.php          # Login page
│   ├── signup.php         # Registration page
│   ├── profile.php        # User profile
│   ├── gallery.php        # Gallery page
│   ├── about.php          # About page
│   ├── contact.php        # Contact page
│   ├── amenities.php      # Amenities page
│   ├── virtual-view.php  # Virtual tour
│   ├── payment-success.php # Payment success page
│   ├── payment-failed.php  # Payment failed page
│   └── ...
│
├── .env                   # Environment variables (not in git)
├── .gitignore            # Git ignore rules
├── composer.json         # PHP dependencies
└── README.md            # Main project README
```

## 📂 Directory Descriptions

### `/api/`
Contains all API endpoints and webhook handlers. These files handle AJAX requests and external webhooks.

**Key Files:**
- `create_payment.php` - Creates Paymongo payment links
- `webhook_paymongo.php` - Handles Paymongo webhook events
- `get_available_rooms.php` - Returns available rooms for dates
- `get_bookings.php` - Returns user bookings
- `check_payment.php` - Checks payment status
- `check_room_availability.php` - Verifies room availability

### `/scripts/`
Utility scripts for setup, maintenance, and testing. These are typically run once or for maintenance.

**Key Files:**
- `setup_env.php` - Interactive .env file setup
- `create_admin.php` - Creates admin account
- `seed_rooms.php` - Seeds room data
- `start_ngrok.bat` - Starts ngrok for webhook testing

### `/database/`
SQL files for database setup and migrations.

**Key Files:**
- `database_dump.sql` - Complete database dump for import
- `database_migration_bookings.sql` - Booking-related migrations
- `add_name_fields.sql` - Adds name fields to users table

### `/docs/`
All documentation files including setup guides, API documentation, and troubleshooting.

### `/config/`
Configuration files that contain database connections, API keys, and other settings.

### `/assets/`
Static assets including CSS, JavaScript, and images used across the site.

### `/uploads/`
User-uploaded content organized by type (gallery, profile pictures, room images, etc.).

## 🔗 URL Paths

After reorganization, API endpoints are accessed via:
- `http://localhost/dwellscape/api/get_available_rooms.php`
- `http://localhost/dwellscape/api/create_payment.php`
- `http://localhost/dwellscape/api/webhook_paymongo.php`

Webhook URLs should be updated to:
- Local: `https://your-ngrok-url.ngrok.io/dwellscape/api/webhook_paymongo.php`
- Production: `https://yourdomain.com/api/webhook_paymongo.php`

## 📝 Notes

- All API files use `__DIR__ . '/../config/'` for relative paths
- Main pages remain in root for clean URLs
- Documentation is centralized in `/docs/`
- Utility scripts are separated from production code

## 🔄 Migration Notes

If you're updating from the old structure:
1. Update webhook URLs in Paymongo dashboard
2. Update any hardcoded API paths in JavaScript
3. Update documentation references
4. Test all API endpoints

