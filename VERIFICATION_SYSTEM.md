# Reservation Verification System

## Overview

The verification system tracks when customers check in using their QR codes. It creates a complete audit trail of all verification attempts and marks reservations as verified.

## Database Schema

### Reservations Table (Updated)

```sql
- verified: BOOLEAN (default: FALSE)
- verified_at: DATETIME (nullable)
```

### Reservation Verifications Table (New)

```sql
- id: INT (primary key)
- reservation_id: INT (foreign key)
- qr_code: VARCHAR(255)
- verified_at: DATETIME
- verification_method: ENUM('qr_scan', 'manual_entry')
- ip_address: VARCHAR(45)
- user_agent: TEXT
```

## How It Works

### 1. Customer Scans QR Code

```
Customer → Scan QR Code → Frontend sends to API
```

### 2. Backend Verification Process

```
1. Validate QR code format
2. Find reservation by QR code
3. Check if already verified
4. Update reservation.verified = TRUE
5. Log verification in reservation_verifications table
6. Return reservation details
```

### 3. Verification Tracking

- **First verification**: Updates reservation, creates log entry
- **Subsequent scans**: Returns data with "already verified" message
- **Audit trail**: All scans logged with timestamp, IP, method

## API Endpoint

### POST `/api/reservations/verify`

**Request:**

```json
{
  "qrCode": "RES-1-1710604800",
  "method": "qr_scan" // or "manual_entry"
}
```

**Response (Success - First Time):**

```json
{
  "success": true,
  "message": "Reservation verified successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "date": "2026-03-20",
    "time": "19:00",
    "guests": 4,
    "table": "Window Table",
    "status": "confirmed",
    "specialRequests": "Anniversary dinner",
    "qrCode": "RES-1-1710604800",
    "verified": true,
    "verifiedAt": "2026-03-16 14:30:00",
    "createdAt": "2026-03-15 10:00:00"
  }
}
```

**Response (Already Verified):**

```json
{
  "success": true,
  "message": "This reservation was already verified",
  "data": {
    ...same as above...,
    "alreadyVerified": true
  }
}
```

**Response (Error):**

```json
{
  "success": false,
  "error": "Invalid QR code or reservation not found"
}
```

## Frontend Integration

The [`CustomerConfirmation.vue`](frontend/src/components/CustomerConfirmation.vue) component:

1. **Scans QR code** or accepts manual entry
2. **Calls verification API** with code and method
3. **Displays reservation details** after successful verification
4. **Shows verification status** (new or already verified)

## Database Migration

### For Existing Databases:

```bash
mysql -u root -p reservation_app < backend/migration_add_verification.sql
```

Or manually:

```sql
-- Add verified fields
ALTER TABLE reservations
ADD COLUMN verified BOOLEAN NOT NULL DEFAULT FALSE,
ADD COLUMN verified_at DATETIME NULL,
ADD INDEX idx_verified (verified);

-- Create verifications table
CREATE TABLE reservation_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    qr_code VARCHAR(255) NOT NULL,
    verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verification_method ENUM('qr_scan', 'manual_entry') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);
```

## Features

### ✅ Verification Tracking

- Records every verification attempt
- Tracks verification method (QR scan vs manual entry)
- Logs IP address and user agent
- Timestamps all verifications

### ✅ Duplicate Prevention

- Detects if reservation already verified
- Returns appropriate message
- Still allows viewing details

### ✅ Audit Trail

- Complete history in `reservation_verifications` table
- Can track multiple scans of same reservation
- Useful for analytics and security

### ✅ Security

- Validates QR code format
- Checks reservation exists
- Logs all access attempts
- Tracks IP addresses

## Usage Examples

### Check Verification Status

```sql
-- See all verified reservations
SELECT * FROM reservations WHERE verified = TRUE;

-- See verification history
SELECT
    r.name,
    r.date,
    r.time,
    rv.verified_at,
    rv.verification_method,
    rv.ip_address
FROM reservations r
JOIN reservation_verifications rv ON r.id = rv.reservation_id
ORDER BY rv.verified_at DESC;

-- Count verifications per reservation
SELECT
    reservation_id,
    COUNT(*) as verification_count,
    MIN(verified_at) as first_verified,
    MAX(verified_at) as last_verified
FROM reservation_verifications
GROUP BY reservation_id;
```

### Analytics Queries

```sql
-- Verification rate by date
SELECT
    DATE(date) as reservation_date,
    COUNT(*) as total_reservations,
    SUM(verified) as verified_count,
    ROUND(SUM(verified) / COUNT(*) * 100, 2) as verification_rate
FROM reservations
WHERE date >= CURDATE()
GROUP BY DATE(date);

-- Verification method breakdown
SELECT
    verification_method,
    COUNT(*) as count,
    ROUND(COUNT(*) / (SELECT COUNT(*) FROM reservation_verifications) * 100, 2) as percentage
FROM reservation_verifications
GROUP BY verification_method;

-- Peak verification times
SELECT
    HOUR(verified_at) as hour,
    COUNT(*) as verifications
FROM reservation_verifications
GROUP BY HOUR(verified_at)
ORDER BY verifications DESC;
```

## Admin Dashboard Integration

You can add verification status to the reservation list:

```vue
<!-- In ReservationList.vue -->
<div v-if="reservation.verified" class="verified-badge">
  <i class="bi bi-check-circle-fill"></i>
  Checked In
  <small>{{ formatTime(reservation.verifiedAt) }}</small>
</div>
```

## Benefits

1. **Customer Experience**
   - Quick check-in process
   - No manual lookup needed
   - Instant confirmation

2. **Staff Efficiency**
   - Automated verification
   - No manual checking required
   - Real-time status updates

3. **Analytics**
   - Track check-in rates
   - Identify no-shows
   - Analyze peak times

4. **Security**
   - Audit trail of all access
   - IP tracking
   - Duplicate detection

5. **Compliance**
   - Complete verification history
   - Timestamp all actions
   - Traceable records

## Troubleshooting

### Verification Not Working

1. **Check database migration**:

   ```sql
   SHOW COLUMNS FROM reservations LIKE 'verified';
   SHOW TABLES LIKE 'reservation_verifications';
   ```

2. **Check API endpoint**:

   ```bash
   curl -X POST http://localhost:8000/api/reservations/verify \
     -H "Content-Type: application/json" \
     -d '{"qrCode":"RES-1-1710604800","method":"qr_scan"}'
   ```

3. **Check backend logs**: Look at terminal where PHP server is running

### Common Issues

**"Reservation not found"**

- QR code format incorrect
- Reservation doesn't exist
- Database not migrated

**"Already verified" message**

- Normal behavior for duplicate scans
- Check `verified_at` timestamp
- View verification history

**Database errors**

- Run migration script
- Check foreign key constraints
- Verify table structure

## Future Enhancements

Potential improvements:

- [ ] Email notification on verification
- [ ] SMS confirmation
- [ ] Check-in time window validation
- [ ] No-show tracking
- [ ] Waitlist auto-promotion
- [ ] Staff notification system
- [ ] Real-time dashboard updates
- [ ] Export verification reports

## Support

For issues or questions:

1. Check database migration completed
2. Verify API endpoint accessible
3. Review console logs
4. Check network requests in browser DevTools
