# Customer Reservation Confirmation Guide

## Overview

A new customer-facing page has been created that allows customers to confirm their reservations on the day of their booking by scanning a QR code. This feature provides a seamless check-in experience for your restaurant guests.

## Features

### 1. **QR Code Scanner**

- Customers can use their device camera to scan the QR code from their reservation confirmation
- Real-time scanning with visual feedback
- Automatic verification upon successful scan

### 2. **Manual Code Entry**

- Alternative option to manually enter the reservation code
- Useful when camera access is not available or QR code is damaged

### 3. **Reservation Details Display**

- After successful verification, displays complete reservation information:
  - Guest name, email, and phone
  - Reservation date and time
  - Number of guests
  - Table preference
  - Special requests
  - Reservation status
  - Unique reservation code

### 4. **Beautiful UI**

- Luxury-themed design matching your brand
- Animated background with floating particles
- Responsive layout for all devices
- Print-friendly confirmation page

## How It Works

### For Restaurant Staff:

1. **Generate QR Codes**
   - When a reservation is created, a unique QR code is automatically generated
   - Format: `RES-{id}-{timestamp}`
   - View QR codes by clicking the QR icon on any reservation in the list
   - QR codes can be printed or sent via email to customers

2. **Share with Customers**
   - Include the QR code in confirmation emails
   - Print on reservation confirmations
   - Display at check-in desk

### For Customers:

1. **Access the Confirmation Page**
   - Navigate to: `http://your-domain.com/confirm`
   - Or scan a QR code that links to this page

2. **Scan QR Code**
   - Click "Start Camera" button
   - Point camera at the QR code
   - System automatically verifies and displays reservation details

3. **Alternative: Manual Entry**
   - If camera is unavailable, enter the reservation code manually
   - Format: `RES-123-1234567890`
   - Click "Verify Code"

4. **View Confirmation**
   - See all reservation details
   - Print confirmation if needed
   - Scan another code for additional reservations

## Technical Implementation

### Files Created/Modified:

1. **`frontend/src/components/CustomerConfirmation.vue`**
   - Main customer confirmation component
   - QR scanner integration
   - Manual code entry form
   - Reservation details display

2. **`frontend/src/views/HomePage.vue`**
   - Separated home page content from App.vue
   - Contains reservation management interface

3. **`frontend/src/router/index.js`**
   - Added routes for home (`/`) and confirmation (`/confirm`) pages

4. **`frontend/src/App.vue`**
   - Updated to use router-view
   - Conditional navbar and footer display

5. **`frontend/src/main.js`**
   - Added router integration

6. **`backend/database.sql`**
   - Added `qr_code` field to reservations table
   - Added index for faster QR code lookups

7. **`backend/api.php`**
   - Updated to generate and return QR codes
   - QR code generation on reservation creation
   - QR code included in all API responses

8. **`frontend/src/components/ReservationList.vue`**
   - Updated QR code generation to use backend codes
   - Enhanced QR modal with instructions

## Database Migration

If you have an existing database, run this SQL to add the QR code field:

```sql
ALTER TABLE reservations
ADD COLUMN qr_code VARCHAR(255) AFTER special_requests,
ADD INDEX idx_qr_code (qr_code);

-- Update existing reservations with QR codes
UPDATE reservations
SET qr_code = CONCAT('RES-', id, '-', UNIX_TIMESTAMP(created_at))
WHERE qr_code IS NULL;
```

## API Endpoints

### Get Reservation by ID

```
GET /api/reservations/{id}
```

Response includes `qrCode` field:

```json
{
  "success": true,
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
    "createdAt": "2026-03-16 14:00:00"
  }
}
```

## Security Considerations

1. **QR Code Format**: Uses reservation ID + timestamp for uniqueness
2. **Verification**: Backend validates reservation exists before displaying details
3. **No Sensitive Data**: QR codes don't contain personal information directly
4. **HTTPS Recommended**: Use HTTPS in production for secure transmission

## Usage Tips

1. **Email Integration**: Include QR code in automated confirmation emails
2. **Check-in Kiosk**: Set up a tablet at entrance with `/confirm` page loaded
3. **Mobile-Friendly**: Page works perfectly on smartphones
4. **Print Option**: Customers can print confirmation for their records
5. **Multiple Scans**: Can scan multiple reservations in one session

## Troubleshooting

### Camera Not Working

- Ensure browser has camera permissions
- Use HTTPS (required for camera access in most browsers)
- Try manual code entry as alternative

### QR Code Not Scanning

- Ensure good lighting
- Hold camera steady
- Try manual code entry
- Verify QR code is not damaged

### Reservation Not Found

- Check reservation code format
- Verify reservation exists in database
- Ensure backend API is running

## Future Enhancements

Potential improvements for future versions:

- Email/SMS notifications with QR codes
- Check-in status tracking
- Waitlist integration
- Multi-language support
- Analytics dashboard for check-ins

## Support

For technical support or questions, please refer to the main project README or contact your development team.
