# Reservation App Backend

A PHP REST API backend for the luxury restaurant reservation system.

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- mod_rewrite enabled (for clean URLs)

## Setup Instructions

### 1. Database Setup

1. Open MySQL (via phpMyAdmin, MySQL Workbench, or command line)
2. Run the SQL commands from `database.sql` to create the database and tables:

```bash
mysql -u root -p < database.sql
```

Or import the file via phpMyAdmin.

### 2. Configure Database Connection

Edit [`config.php`](config.php) and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'reservation_app');
define('DB_USER', 'root');
define('DB_PASS', 'your_password'); // Your MySQL password
```

### 3. Web Server Configuration

#### Option A: Apache (XAMPP/WAMP)

- Place the `backend` folder in your web server's document root (e.g., `htdocs` or `www`)
- The `.htaccess` file handles URL rewriting

#### Option B: Built-in PHP Server

```bash
cd backend
php -S localhost:8000 router.php
```

**Important:** Use `router.php` for the built-in server to handle URL routing correctly.

### 4. API Endpoints

| Method | Endpoint                           | Description                    |
| ------ | ---------------------------------- | ------------------------------ |
| GET    | `/api/reservations`                | Get all reservations           |
| GET    | `/api/reservations?status=pending` | Get reservations by status     |
| GET    | `/api/reservations/{id}`           | Get single reservation         |
| POST   | `/api/reservations`                | Create new reservation         |
| PUT    | `/api/reservations/{id}`           | Update reservation             |
| PUT    | `/api/reservations/status`         | Update reservation status only |
| DELETE | `/api/reservations/{id}`           | Delete reservation             |

### 5. Example API Usage

#### Get all reservations:

```bash
curl http://localhost/api/reservations
```

#### Create a reservation:

```bash
curl -X POST http://localhost/api/reservations \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1 555 123 4567",
    "date": "2026-03-25",
    "time": "19:00",
    "guests": 4,
    "table": "Window Table",
    "specialRequests": "Anniversary dinner"
  }'
```

#### Update status:

```bash
curl -X PUT http://localhost/api/reservations/status \
  -H "Content-Type: application/json" \
  -d '{"id": 1, "status": "confirmed"}'
```

#### Delete reservation:

```bash
curl -X DELETE http://localhost/api/reservations/1
```

## Frontend Setup

The frontend is already configured to connect to the backend. Ensure:

1. Backend is running on `http://localhost`
2. Start the frontend:

```bash
cd frontend
npm install
npm run dev
```

## Project Structure

```
backend/
├── config.php      # Database and API configuration
├── db.php          # Database connection class
├── api.php         # Main API endpoint handlers
├── database.sql    # Database schema and sample data
├── .htaccess       # Apache URL rewriting
└── env.example     # Environment configuration template
```
