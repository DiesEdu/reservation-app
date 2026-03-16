# Backend Server Setup Guide

## Quick Start

The customer confirmation page requires the backend API to be running to verify reservations.

### Starting the Backend Server

#### Option 1: PHP Built-in Server (Development)

```bash
cd backend
php -S localhost:8000 router.php
```

The API will be available at: `http://localhost:8000/api`

**Important:** Use `router.php` as the entry point to enable proper routing.

#### Option 2: Using XAMPP/WAMP/MAMP

1. Copy the `backend` folder to your web server directory:
   - XAMPP: `C:\xampp\htdocs\reservation-app\backend`
   - WAMP: `C:\wamp64\www\reservation-app\backend`
   - MAMP: `/Applications/MAMP/htdocs/reservation-app/backend`

2. Start Apache and MySQL from your control panel

3. Access via: `http://localhost/reservation-app/backend/api.php`

4. Update API_URL in frontend if needed:
   ```javascript
   // frontend/src/stores/reservations.js
   const API_URL = "http://localhost/reservation-app/backend/api.php";
   ```

### Database Setup

1. **Create the database:**

   ```bash
   mysql -u root -p < backend/database.sql
   ```

   Or manually in phpMyAdmin/MySQL Workbench:
   - Create database: `reservation_app`
   - Import `backend/database.sql`

2. **Configure database connection:**

   Edit `backend/config.php`:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'reservation_app');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   ```

3. **Update existing database** (if you have data):

   ```sql
   ALTER TABLE reservations
   ADD COLUMN qr_code VARCHAR(255) AFTER special_requests,
   ADD INDEX idx_qr_code (qr_code);

   -- Generate QR codes for existing reservations
   UPDATE reservations
   SET qr_code = CONCAT('RES-', id, '-', UNIX_TIMESTAMP(created_at))
   WHERE qr_code IS NULL;
   ```

### Testing the API

#### Test if backend is running:

```bash
curl http://localhost:8000/api/reservations
```

Expected response:

```json
{
  "success": true,
  "data": [...]
}
```

#### Test single reservation:

```bash
curl http://localhost:8000/api/reservations/1
```

Expected response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "qrCode": "RES-1-1710604800",
    ...
  }
}
```

### Common Issues

#### 1. "Endpoint not found" Error

**Cause:** Backend server is not running

**Solution:**

```bash
cd backend
php -S localhost:8000 router.php
```

#### 2. Database Connection Error

**Cause:** Database credentials are incorrect or MySQL is not running

**Solution:**

- Check `backend/config.php` credentials
- Ensure MySQL is running
- Verify database exists: `SHOW DATABASES;`

#### 3. CORS Errors

**Cause:** Frontend and backend on different domains

**Solution:** Add CORS headers in `backend/api.php`:

```php
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

#### 4. 404 on API Routes

**Cause:** .htaccess not working or mod_rewrite not enabled

**Solution:**

- Enable mod_rewrite in Apache
- Or use direct path: `http://localhost:8000/api.php/reservations`

### Full Development Setup

#### Terminal 1 - Backend:

```bash
cd backend
php -S localhost:8000 router.php
```

#### Terminal 2 - Frontend:

```bash
cd frontend
npm run dev
```

#### Access Points:

- Frontend: `http://localhost:5174`
- Customer Confirmation: `http://localhost:5174/confirm`
- Backend API: `http://localhost:8000/api`

### Production Deployment

1. **Backend:**
   - Upload to web server
   - Configure virtual host
   - Update database credentials
   - Enable HTTPS
   - Set proper file permissions

2. **Frontend:**

   ```bash
   cd frontend
   npm run build
   ```

   - Upload `dist` folder to web server
   - Update API_URL to production URL
   - Configure web server for SPA routing

3. **Update API URLs:**

   ```javascript
   // frontend/src/stores/reservations.js
   const API_URL = "https://your-domain.com/api";

   // frontend/src/components/CustomerConfirmation.vue
   const API_URL = "https://your-domain.com/api";
   ```

### Environment Variables (Optional)

Create `.env` files for different environments:

**backend/.env:**

```
DB_HOST=localhost
DB_NAME=reservation_app
DB_USER=root
DB_PASS=your_password
```

**frontend/.env.development:**

```
VITE_API_URL=http://localhost:8000/api
```

**frontend/.env.production:**

```
VITE_API_URL=https://your-domain.com/api
```

Then use in code:

```javascript
const API_URL = import.meta.env.VITE_API_URL;
```

### Troubleshooting Checklist

- [ ] Backend server is running (`php -S localhost:8000`)
- [ ] MySQL/MariaDB is running
- [ ] Database `reservation_app` exists
- [ ] Database credentials in `config.php` are correct
- [ ] `qr_code` column exists in `reservations` table
- [ ] Frontend API_URL matches backend URL
- [ ] No firewall blocking port 8000
- [ ] CORS headers are set (if needed)

### Need Help?

Check the console for errors:

- **Frontend:** Browser DevTools Console (F12)
- **Backend:** Terminal where PHP server is running

Common error messages and solutions are in the main documentation.
