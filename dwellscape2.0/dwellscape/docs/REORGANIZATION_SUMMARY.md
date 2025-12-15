# Project Reorganization Summary

## ✅ Completed Reorganization

The project folder structure has been successfully organized for better maintainability and clarity.

## 📁 New Folder Structure

### Created Folders:
- **`api/`** - All API endpoints and webhooks
- **`docs/`** - All documentation files
- **`database/`** - SQL files and database scripts
- **`scripts/`** - Utility scripts, test files, and batch files

## 📦 Files Moved

### API Endpoints → `api/`
- `get_available_rooms.php`
- `get_bookings.php`
- `get_booked_dates.php`
- `create_payment.php`
- `check_payment.php`
- `check_room_availability.php`
- `webhook_paymongo.php`
- `save_suggestion.php`

### Documentation → `docs/`
- `README.md`
- `README_PAYMONGO_SETUP.md`
- `README_EMAIL_SETUP.md`
- `NGROK_SETUP.md`
- `QUICK_WEBHOOK_SETUP.md`
- `WEBHOOK_SETUP_GUIDE.md`
- `IMAGE_FIXES_SUMMARY.md`
- `PROJECT_STRUCTURE.md` (new)

### SQL Files → `database/`
- `database_dump.sql`
- `database_migration_bookings.sql`
- `add_name_fields.sql`

### Utility Scripts → `scripts/`
- `test_*.php` files
- `check_*.php` files (utility scripts, not API)
- `create_*.php` files (setup scripts)
- `add_*.php` files
- `fix_*.php` files
- `setup_*.php` files
- `seed_*.php` files
- `restore_*.php` files
- `upload_*.php` files
- `start_ngrok.bat`

## 🔄 Updated File References

### JavaScript Fetch Calls Updated:
- `bookings.php` - All API calls now use `api/` prefix
- `dashboard.php` - All API calls now use `api/` prefix

### PHP Require Paths Updated:
- All files in `api/` now use `__DIR__ . '/../config/'` for relative paths
- Ensures proper path resolution regardless of where files are called from

### Documentation Updated:
- All webhook URLs updated to include `api/` path
- All setup guides reflect new structure
- Created `PROJECT_STRUCTURE.md` for reference

## ⚠️ Important: Update Webhook URL

**You need to update your Paymongo webhook URL:**

1. Go to: https://dashboard.paymongo.com/settings/webhooks
2. Edit your existing webhook
3. Update URL to: `https://your-ngrok-url.ngrok.io/dwellscape/api/webhook_paymongo.php`
4. Save changes

## 📝 Files That Stayed in Root

These files remain in the root directory for clean URLs:
- `home.php` - Homepage
- `dashboard.php` - User dashboard
- `bookings.php` - Booking page
- `login.php`, `signup.php` - Authentication
- `profile.php` - User profile
- `gallery.php`, `about.php`, `contact.php` - Public pages
- `payment-success.php`, `payment-failed.php` - Payment pages
- Other main application pages

## ✅ Benefits of New Structure

1. **Better Organization** - Related files grouped together
2. **Easier Maintenance** - Clear separation of concerns
3. **Cleaner Root** - Main pages only in root
4. **Better Security** - API endpoints separated
5. **Easier Navigation** - Logical folder structure

## 🧪 Testing Checklist

After reorganization, test:
- [ ] Homepage loads correctly
- [ ] Booking page works
- [ ] Room selection works
- [ ] Payment creation works
- [ ] Webhook receives events (check Paymongo dashboard)
- [ ] All API endpoints respond correctly

## 📚 Documentation

See `docs/PROJECT_STRUCTURE.md` for complete folder structure documentation.

