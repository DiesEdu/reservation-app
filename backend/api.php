<?php
/**
 * Reservations API
 * Handles GET, POST, PUT, DELETE operations for reservations
 */

// Set CORS headers to allow frontend access
header('Access-Control-Allow-Origin: *'); // Allow all origins for development
// For production, use specific origin: header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Get the request method and parse the URL
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// Route handling
if ($path === '/reservations' || $path === '/reservations/') {
    // GET all reservations or POST new reservation
    if ($method === 'GET') {
        getReservations();
    } elseif ($method === 'POST') {
        createReservation();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} elseif (preg_match('#^/reservations/(\d+)$#', $path, $matches)) {
    // Single reservation: GET, PUT, DELETE
    $id = (int) $matches[1];
    if ($method === 'GET') {
        getReservation($id);
    } elseif ($method === 'PUT') {
        updateReservation($id);
    } elseif ($method === 'DELETE') {
        deleteReservation($id);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} elseif ($path === '/reservations/status' && $method === 'PUT') {
    // Update reservation status
    updateReservationStatus();
} elseif ($path === '/reservations/verify' && $method === 'POST') {
    // Verify reservation with QR code
    verifyReservation();
} elseif ($path === '/reservations/import' && $method === 'POST') {
    // Bulk import reservations from Excel
    importReservationsFromExcel();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

/**
 * GET all reservations
 */
function getReservations()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check for status filter
    $status = $_GET['status'] ?? null;

    try {
        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $stmt = $pdo->prepare("SELECT * FROM reservations WHERE status = ? ORDER BY date ASC, time ASC");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("SELECT * FROM reservations ORDER BY date ASC, time ASC");
        }

        $reservations = $stmt->fetchAll();

        // Format the data to match frontend expectations
        $formatted = array_map(function ($res) {
            return [
                'id' => (int) $res['id'],
                'name' => $res['name'],
                'email' => $res['email'],
                'phone' => $res['phone'],
                'date' => $res['date'],
                'time' => substr($res['time'], 0, 5), // Remove seconds from time
                'guests' => (int) $res['guests'],
                'table' => $res['table_preference'],
                'status' => $res['status'],
                'specialRequests' => $res['special_requests'],
                'qrCode' => $res['qr_code'],
                'verified' => (bool) $res['verified'],
                'verifiedAt' => $res['verified_at'],
                'createdAt' => $res['created_at']
            ];
        }, $reservations);

        echo json_encode([
            'success' => true,
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch reservations: ' . $e->getMessage()
        ]);
    }
}

/**
 * GET single reservation by ID
 */
function getReservation($id)
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Reservation not found'
            ]);
            return;
        }

        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'guests' => (int) $reservation['guests'],
            'table' => $reservation['table_preference'],
            'status' => $reservation['status'],
            'specialRequests' => $reservation['special_requests'],
            'qrCode' => $reservation['qr_code'],
            'verified' => (bool) $reservation['verified'],
            'verifiedAt' => $reservation['verified_at'],
            'createdAt' => $reservation['created_at']
        ];

        echo json_encode([
            'success' => true,
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch reservation: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Create new reservation
 */
function createReservation()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['name', 'email', 'phone', 'date', 'time', 'guests', 'table'];
    $missing = [];

    foreach ($required as $field) {
        if (empty($input[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO reservations
            (name, email, phone, date, time, guests, table_preference, status, special_requests)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");

        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['phone'],
            $input['date'],
            $input['time'],
            (int) $input['guests'],
            $input['table'],
            $input['specialRequests'] ?? ''
        ]);

        $id = (int) $pdo->lastInsertId();

        // Fetch the created reservation
        $selectStmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $selectStmt->execute([$id]);
        $reservation = $selectStmt->fetch();

        // Generate QR code (format: RES-{id}-{timestamp})
        $qrCode = 'RES-' . $id . '-' . strtotime($reservation['created_at']);

        // Update reservation with QR code
        $updateStmt = $pdo->prepare("UPDATE reservations SET qr_code = ? WHERE id = ?");
        $updateStmt->execute([$qrCode, $id]);

        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'guests' => (int) $reservation['guests'],
            'table' => $reservation['table_preference'],
            'status' => $reservation['status'],
            'specialRequests' => $reservation['special_requests'],
            'qrCode' => $qrCode,
            'verified' => false,
            'verifiedAt' => null,
            'createdAt' => $reservation['created_at']
        ];

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully',
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create reservation: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Import reservations from Excel (.xlsx)
 */
function importReservationsFromExcel()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Validate uploaded file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'File upload failed or no file provided'
        ]);
        return;
    }

    $file = $_FILES['file'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extension !== 'xlsx') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Only .xlsx files are supported'
        ]);
        return;
    }

    $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('reservations_', true) . '.xlsx';

    if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to store uploaded file'
        ]);
        return;
    }

    try {
        $spreadsheet = IOFactory::load($tmpPath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();

        // Build header map from the first row
        $headerMap = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $value = strtolower(trim((string) $sheet->getCell($columnLetter . '1')->getValue()));
            if (!empty($value)) {
                $headerMap[$col] = $value;
            }
        }

        $requiredHeaders = ['name', 'email', 'phone', 'table_preference'];
        $missingHeaders = array_diff($requiredHeaders, array_values($headerMap));
        if (!empty($missingHeaders)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required columns: ' . implode(', ', $missingHeaders)
            ]);
            unlink($tmpPath);
            return;
        }

        $inserted = 0;
        $errors = [];

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("
            INSERT INTO reservations
            (name, email, phone, date, time, guests, table_preference, status, special_requests)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");

        $qrStmt = $pdo->prepare("UPDATE reservations SET qr_code = ? WHERE id = ?");

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowValues = [];

            foreach ($headerMap as $colIndex => $field) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cell = $sheet->getCell($columnLetter . $row);
                $value = $cell->getValue();
                if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $value = $value->getPlainText();
                }
                $rowValues[$field] = is_string($value) ? trim($value) : $value;
            }

            // Skip completely empty rows
            if (
                (empty($rowValues['name'])) &&
                (empty($rowValues['email'])) &&
                (empty($rowValues['phone'])) &&
                (empty($rowValues['table_preference']))
            ) {
                continue;
            }

            $name = $rowValues['name'] ?? '';
            $email = $rowValues['email'] ?? '';
            $phone = $rowValues['phone'] ?? '';
            $tablePreference = $rowValues['table_preference'] ?? ($rowValues['table'] ?? '');

            if (!$name || !$email || !$phone || !$tablePreference) {
                $errors[] = "Row {$row}: missing required fields";
                continue;
            }

            // Optional columns with sensible defaults
            $dateValue = $rowValues['date'] ?? date('Y-m-d');
            if (is_numeric($dateValue)) {
                $dateValue = ExcelDate::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } elseif (!empty($dateValue)) {
                $timestamp = strtotime($dateValue);
                $dateValue = $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
            } else {
                $dateValue = date('Y-m-d');
            }

            $timeValue = $rowValues['time'] ?? '00:00:00';
            if (is_numeric($timeValue)) {
                $timeValue = ExcelDate::excelToDateTimeObject($timeValue)->format('H:i:s');
            } elseif (!empty($timeValue)) {
                $timestamp = strtotime($timeValue);
                $timeValue = $timestamp ? date('H:i:s', $timestamp) : '00:00:00';
            } else {
                $timeValue = '00:00:00';
            }

            $guests = isset($rowValues['guests']) && $rowValues['guests'] !== '' ? (int) $rowValues['guests'] : 2;
            $specialRequests = $rowValues['special_requests'] ?? '';

            $insertStmt->execute([
                $name,
                $email,
                $phone,
                $dateValue,
                $timeValue,
                $guests,
                $tablePreference,
                $specialRequests
            ]);

            $newId = (int) $pdo->lastInsertId();
            $qrCode = 'RES-' . $newId . '-' . time();
            $qrStmt->execute([$qrCode, $newId]);

            $inserted++;
        }

        $pdo->commit();
        unlink($tmpPath);

        $response = [
            'success' => true,
            'message' => 'Import completed',
            'inserted' => $inserted
        ];

        if (!empty($errors)) {
            $response['partial'] = true;
            $response['errors'] = $errors;
        }

        echo json_encode($response);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to import: ' . $e->getMessage()
        ]);
    }
}

/**
 * PUT - Update reservation (full update)
 */
function updateReservation($id)
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    // Check if reservation exists
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Reservation not found'
        ]);
        return;
    }

    // Validate required fields
    $required = ['name', 'email', 'phone', 'date', 'time', 'guests', 'table', 'status'];
    $missing = [];

    foreach ($required as $field) {
        if (!isset($input[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET name = ?, email = ?, phone = ?, date = ?, time = ?, 
                guests = ?, table_preference = ?, status = ?, special_requests = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['phone'],
            $input['date'],
            $input['time'],
            (int) $input['guests'],
            $input['table'],
            $input['status'],
            $input['specialRequests'] ?? '',
            $id
        ]);

        // Fetch updated reservation
        $selectStmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $selectStmt->execute([$id]);
        $reservation = $selectStmt->fetch();

        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'guests' => (int) $reservation['guests'],
            'table' => $reservation['table_preference'],
            'status' => $reservation['status'],
            'specialRequests' => $reservation['special_requests'],
            'createdAt' => $reservation['created_at']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Reservation updated successfully',
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update reservation: ' . $e->getMessage()
        ]);
    }
}

/**
 * PUT - Update reservation status only
 */
function updateReservationStatus()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing id or status'
        ]);
        return;
    }

    $id = (int) $input['id'];
    $status = $input['status'];

    if (!in_array($status, ['pending', 'confirmed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status value'
        ]);
        return;
    }

    // Check if reservation exists
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Reservation not found'
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode([
            'success' => true,
            'message' => 'Reservation status updated successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Verify reservation with QR code
 */
function verifyReservation()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['qrCode'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'QR code is required'
        ]);
        return;
    }

    $qrCode = $input['qrCode'];
    $verificationMethod = $input['method'] ?? 'qr_scan';

    try {
        // Find reservation by QR code
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE qr_code = ?");
        $stmt->execute([$qrCode]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid QR code or reservation not found'
            ]);
            return;
        }

        // Check if reservation status is confirmed
        if ($reservation['status'] !== 'confirmed') {
            http_response_code(400);
            $errorMessage = '';
            if ($reservation['status'] === 'pending') {
                $errorMessage = 'Your reservation is still pending confirmation. Please wait for confirmation before verifying.';
            } elseif ($reservation['status'] === 'cancelled') {
                $errorMessage = 'Your reservation has been cancelled. Please contact us for assistance.';
            } else {
                $errorMessage = 'Your reservation is not confirmed. Status: ' . $reservation['status'];
            }
            echo json_encode([
                'success' => false,
                'error' => $errorMessage
            ]);
            return;
        }

        // Check if already verified
        if ($reservation['verified']) {
            // Return reservation data even if already verified
            $formatted = [
                'id' => (int) $reservation['id'],
                'name' => $reservation['name'],
                'email' => $reservation['email'],
                'phone' => $reservation['phone'],
                'date' => $reservation['date'],
                'time' => substr($reservation['time'], 0, 5),
                'guests' => (int) $reservation['guests'],
                'table' => $reservation['table_preference'],
                'status' => $reservation['status'],
                'specialRequests' => $reservation['special_requests'],
                'qrCode' => $reservation['qr_code'],
                'verified' => true,
                'verifiedAt' => $reservation['verified_at'],
                'createdAt' => $reservation['created_at'],
                'alreadyVerified' => true
            ];

            echo json_encode([
                'success' => true,
                'message' => 'This reservation was already verified',
                'data' => $formatted
            ]);
            return;
        }

        // Update reservation as verified
        $updateStmt = $pdo->prepare("
            UPDATE reservations
            SET verified = TRUE, verified_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$reservation['id']]);

        // Log verification
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $logStmt = $pdo->prepare("
            INSERT INTO reservation_verifications
            (reservation_id, qr_code, verification_method, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $reservation['id'],
            $qrCode,
            $verificationMethod,
            $ipAddress,
            $userAgent
        ]);

        // Return updated reservation data
        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'guests' => (int) $reservation['guests'],
            'table' => $reservation['table_preference'],
            'status' => $reservation['status'],
            'specialRequests' => $reservation['special_requests'],
            'qrCode' => $reservation['qr_code'],
            'verified' => true,
            'verifiedAt' => date('Y-m-d H:i:s'),
            'createdAt' => $reservation['created_at']
        ];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Reservation verified successfully',
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to verify reservation: ' . $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Delete reservation
 */
function deleteReservation($id)
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if reservation exists
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Reservation not found'
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => 'Reservation deleted successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete reservation: ' . $e->getMessage()
        ]);
    }
}
