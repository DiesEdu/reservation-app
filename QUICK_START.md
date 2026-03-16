# Quick Start Guide - Customer Confirmation Feature

## 🚀 Start the Application

### Terminal 1 - Backend Server

```bash
cd backend
php -S localhost:8000 router.php
```

### Terminal 2 - Frontend Server (Already Running)

```bash
cd frontend
npm run dev
```

## 🌐 Access URLs

- **Admin Dashboard**: http://localhost:5174
- **Customer Confirmation**: http://localhost:5174/confirm
- **Backend API**: http://localhost:8000/api

## ✅ Quick Test

1. **Generate a QR Code:**
   - Go to http://localhost:5174
   - Click the QR icon on any reservation
   - A QR code will be displayed

2. **Test Customer Confirmation:**
   - Go to http://localhost:5174/confirm
   - Click "Start Camera" (allow camera permissions)
   - Scan the QR code OR enter manually: `RES-1-{timestamp}`

3. **Manual Code Format:**
   ```
   RES-{reservation_id}-{unix_timestamp}
   Example: RES-1-1710604800
   ```

## 🔧 Backend Fixed Issues

✅ **CORS Headers Added** - Frontend can now access backend API
✅ **Proper Routing** - Using `router.php` for clean URLs
✅ **QR Code Generation** - Automatic on reservation creation
✅ **API Endpoint** - GET `/api/reservations/{id}` returns QR code

## 📸 Camera Permissions

**Chrome/Edge:** Click camera icon in address bar → Allow
**Firefox:** Click camera icon → Allow
**Safari:** Safari menu → Settings for This Website → Camera → Allow

**Note:** Camera works on `localhost` without HTTPS!

## 🗄️ Database Setup (If Needed)

### For New Database:

```bash
mysql -u root -p < backend/database.sql
```

### For Existing Database (Migration):

```bash
mysql -u root -p reservation_app < backend/migration_add_verification.sql
```

Or manually:

```sql
-- Add verification fields
ALTER TABLE reservations
ADD COLUMN qr_code VARCHAR(255),
ADD COLUMN verified BOOLEAN DEFAULT FALSE,
ADD COLUMN verified_at DATETIME NULL;

-- Create verification tracking table
CREATE TABLE reservation_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    qr_code VARCHAR(255) NOT NULL,
    verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verification_method ENUM('qr_scan', 'manual_entry'),
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
);

-- Generate QR codes
UPDATE reservations
SET qr_code = CONCAT('RES-', id, '-', UNIX_TIMESTAMP(created_at))
WHERE qr_code IS NULL;
```

## 🐛 Troubleshooting

### "Endpoint not found" Error

- ✅ **Fixed!** Make sure backend is running with `router.php`
- Command: `php -S localhost:8000 router.php`

### Camera Not Working

- ✅ **Fixed!** Changed to `v-show` instead of `v-if`
- Allow camera permissions in browser
- Use manual entry as fallback

### CORS Errors

- ✅ **Fixed!** CORS headers added to `api.php`
- Backend allows requests from `http://localhost:5174`

## 📁 Key Files Modified

- `backend/api.php` - Added CORS headers + QR code generation
- `backend/database.sql` - Added qr_code field
- `frontend/src/components/CustomerConfirmation.vue` - Customer page
- `frontend/src/router/index.js` - Added /confirm route

## 📚 Full Documentation

- **[CUSTOMER_CONFIRMATION_GUIDE.md](CUSTOMER_CONFIRMATION_GUIDE.md)** - Complete feature guide
- **[CAMERA_PERMISSIONS_GUIDE.md](CAMERA_PERMISSIONS_GUIDE.md)** - Camera setup help
- **[BACKEND_SETUP.md](BACKEND_SETUP.md)** - Detailed backend setup
- **[VERIFICATION_SYSTEM.md](VERIFICATION_SYSTEM.md)** - Verification tracking system

## ✨ Features

✅ QR Code Scanner with camera
✅ Manual code entry fallback
✅ Real-time verification
✅ Beautiful luxury UI
✅ Responsive design
✅ Print-friendly
✅ Complete error handling

## 🎯 Next Steps

1. Start backend: `php -S localhost:8000 router.php`
2. Frontend already running at http://localhost:5174
3. Test at http://localhost:5174/confirm
4. Generate QR codes from admin dashboard
5. Scan or enter codes to verify reservations

---

**Need Help?** Check the detailed guides or console logs for errors.
