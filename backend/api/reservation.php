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

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';
require_once __DIR__ . '/../utils/reservationUtil.php';

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
} elseif (preg_match('#^/reservations/(\d+)/ticket$#', $path, $matches) && $method === 'GET') {
    // Render reservation ticket as PNG
    renderReservationTicket((int) $matches[1]);
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
} elseif ($path === '/reservations/verification-status' && $method === 'POST') {
    // Check verification status without mutating record
    checkReservationVerificationStatus();
} elseif ($path === '/reservations/import' && $method === 'POST') {
    // Bulk import reservations from Excel
    importReservationsFromExcel();
} elseif ($path === '/reservations/table-preferences' && $method === 'GET') {
    // Get distinct table names used in reservations
    getTablePreferences();
} elseif ($path === '/reservations/summary' && $method === 'GET') {
    // Get reservations summary counts
    getReservationSummary();
} elseif ($path === '/reservations/update-sales' && $method === 'POST') {
    // Update sales_connection from Excel
    updateSalesConnectionFromExcel();
} elseif ($path === '/reservations/sales-connections' && $method === 'GET') {
    // Get distinct sales_connection values
    getSalesConnections();
} elseif ($path === '/reservations/analytics' && $method === 'GET') {
    // Get reservations analytics data for charts
    getReservationAnalytics();
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

    // Filters and pagination
    $status = $_GET['status'] ?? null;
    $verified = $_GET['verified'] ?? null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $table = isset($_GET['table']) ? trim($_GET['table']) : null;
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 20; // default 20, cap at 100
    $offset = ($page - 1) * $perPage;

    try {
        // Build WHERE clause dynamically
        $where = [];
        $params = [];

        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($verified !== null && $verified !== '') {
            $where[] = 'verified = ?';
            $params[] = (int) $verified;
        }

        if ($search !== null && $search !== '') {
            $where[] = '(name LIKE ? OR seat_code LIKE ?)';
            $pattern = '%' . $search . '%';
            $params[] = $pattern;
            $params[] = $pattern;
        }

        if ($table !== null && $table !== '') {
            $where[] = 'seat_code = ?';
            $params[] = $table;
        }

        $salesConnection = isset($_GET['salesConnection']) ? trim($_GET['salesConnection']) : null;
        if ($salesConnection !== null && $salesConnection !== '') {
            $where[] = 'sales_connection = ?';
            $params[] = $salesConnection;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total count for pagination
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservations {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch current page
        $dataSql = "SELECT * FROM reservations {$whereSql} ORDER BY date ASC, time ASC LIMIT ? OFFSET ?";
        $dataStmt = $pdo->prepare($dataSql);

        // Bind search/status params first (if any)
        $bindIndex = 1;
        foreach ($params as $value) {
            $dataStmt->bindValue($bindIndex, $value, PDO::PARAM_STR);
            $bindIndex++;
        }
        $dataStmt->bindValue($bindIndex, (int) $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue($bindIndex + 1, (int) $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $reservations = $dataStmt->fetchAll();

        // Format the data to match frontend expectations
        $formatted = array_map(function ($res) {
            return [
                'id' => (int) $res['id'],
                'name' => $res['name'],
                'company' => $res['company'],
                'position' => $res['position'],
                'salesConnection' => $res['sales_connection'],
                'date' => $res['date'],
                'time' => substr($res['time'], 0, 5),
                'seatCode' => $res['seat_code'],
                'status' => $res['status'],
                'qrCode' => $res['qr_code'],
                'verified' => (bool) $res['verified'],
                'verifiedAt' => $res['verified_at'],
                'createdAt' => $res['created_at']
            ];
        }, $reservations);

        echo json_encode([
            'success' => true,
            'data' => $formatted,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
                'search' => $search,
                'status' => $status ?? 'all'
            ]
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
 * GET distinct table preferences
 */
function getTablePreferences()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->query("SELECT DISTINCT seat_code FROM reservations WHERE seat_code IS NOT NULL AND seat_code <> '' ORDER BY seat_code ASC");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => $tables
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch seat codes: ' . $e->getMessage()
        ]);
    }
}

/**
 * GET reservations summary: totals and counts by status
 */
function getReservationSummary()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Using boolean expressions in SUM works in MySQL (true = 1, false = 0)
        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS total_reservations,
                SUM(status = 'confirmed') AS confirmed_count,
                SUM(verified = '1') AS verified_count
            FROM reservations
        ");

        $row = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'data' => [
                'totalReservations' => (int) ($row['total_reservations'] ?? 0),
                'confirmed' => (int) ($row['confirmed_count'] ?? 0),
                'verified' => (int) ($row['verified_count'] ?? 0),
            ],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch summary: ' . $e->getMessage(),
        ]);
    }
}

/**
 * GET analytics data for dashboard charts
 */
function getReservationAnalytics()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Status counts
        $statusStmt = $pdo->query("
            SELECT status, COUNT(*) AS count
            FROM reservations
            GROUP BY status
        ");
        $statusCounts = $statusStmt->fetchAll() ?: [];

        // Daily counts (last 7 days, including today)
        $dailyStmt = $pdo->query("
            SELECT date AS day, COUNT(*) AS count
            FROM reservations
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY date
            ORDER BY date ASC
        ");
        $dailyCounts = $dailyStmt->fetchAll() ?: [];

        // Peak hours (0-23)
        $peakStmt = $pdo->query("
            SELECT HOUR(time) AS hour, COUNT(*) AS count
            FROM reservations
            GROUP BY HOUR(time)
            ORDER BY hour ASC
        ");
        $peakRows = $peakStmt->fetchAll() ?: [];

        $peakHours = [];
        for ($h = 0; $h <= 23; $h++) {
            $row = array_values(array_filter($peakRows, fn($r) => (int) $r['hour'] === $h));
            $count = $row ? (int) $row[0]['count'] : 0;
            $peakHours[] = ['hour' => $h, 'count' => $count];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'statusCounts' => $statusCounts,
                'dailyCounts' => $dailyCounts,
                'peakHours' => $peakHours,
            ],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch analytics: ' . $e->getMessage(),
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
            'position' => $reservation['position'],
            'company' => $reservation['company'],
            'salesConnection' => $reservation['sales_connection'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'seatCode' => $reservation['seat_code'],
            'status' => $reservation['status'],
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
    $required = ['name', 'company', 'position', 'salesConnection', 'date', 'time', 'seatCode'];
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
            (name, company, position, sales_connection, date, time, seat_code, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $input['name'],
            $input['company'],
            $input['position'],
            $input['salesConnection'],
            $input['date'],
            $input['time'],
            $input['seatCode']
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
            'position' => $reservation['position'],
            'company' => $reservation['company'],
            'salesConnection' => $reservation['sales_connection'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'seatCode' => $reservation['seat_code'],
            'status' => $reservation['status'],
            'qrCode' => $reservation['qr_code'],
            'verified' => (bool) $reservation['verified'],
            'verifiedAt' => $reservation['verified_at'],
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

        $requiredHeaders = ['name', 'company', 'position', 'seat_code', 'table_color', 'date', 'time'];
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

        $normalizeDate = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            $value = trim((string) $value);
            $dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'Y.m.d', 'd M Y', 'M d Y'];
            foreach ($dateFormats as $format) {
                $dt = DateTime::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            }

            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d', $timestamp) : null;
        };

        $normalizeTime = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i:s');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('H:i:s');
            }

            $value = trim((string) $value);
            $timeFormats = ['H:i', 'H:i:s', 'g:i A', 'g:iA', 'H.i'];
            foreach ($timeFormats as $format) {
                $dt = DateTime::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt->format('H:i:s');
                }
            }

            $timestamp = strtotime($value);
            return $timestamp ? date('H:i:s', $timestamp) : null;
        };

        $inserted = 0;
        $errors = [];

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("
            INSERT INTO reservations
            (name, company, position, date, time, seat_code, table_color, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
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
                (empty($rowValues['company'])) &&
                (empty($rowValues['position'])) &&
                (empty($rowValues['seat_code'])) &&
                (empty($rowValues['table_color']))
            ) {
                continue;
            }

            $name = $rowValues['name'] ?? '';
            $company = $rowValues['company'] ?? '';
            $position = $rowValues['position'] ?? '';
            $seatCode = $rowValues['seat_code'] ?? '';
            $tableColor = $rowValues['table_color'] ?? '';

            if (!$seatCode) {
                $errors[] = "Row {$row}: missing required fields";
                continue;
            }

            // Optional columns with sensible defaults
            $dateValue = $normalizeDate($rowValues['date'] ?? null);
            if (!$dateValue) {
                $errors[] = "Row {$row}: invalid or missing date (expected yyyy-mm-dd)";
                continue;
            }

            $timeValue = $normalizeTime($rowValues['time'] ?? null);
            if (!$timeValue) {
                $errors[] = "Row {$row}: invalid or missing time (expected HH:mm)";
                continue;
            }

            $insertStmt->execute([
                $name,
                $company,
                $position,
                $dateValue,
                $timeValue,
                $seatCode,
                $tableColor
            ]);

            $newId = (int) $pdo->lastInsertId();
            $randomSuffix = strtoupper(bin2hex(random_bytes(2))); // 4-char alphanumeric
            $qrCode = 'RES-' . $newId . '-' . time() . '-' . $randomSuffix;
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
    $required = ['name', 'company', 'position', 'salesConnection', 'date', 'time', 'seatCode', 'status'];
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
            SET name = ?, company = ?, position = ?, sales_connection = ?, date = ?, time = ?, 
                seat_code = ?, status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $input['name'],
            $input['company'],
            $input['position'],
            $input['salesConnection'],
            $input['date'],
            $input['time'],
            $input['seatCode'],
            $input['status'],
            $id
        ]);

        // Fetch updated reservation
        $selectStmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $selectStmt->execute([$id]);
        $reservation = $selectStmt->fetch();

        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'position' => $reservation['position'],
            'company' => $reservation['company'],
            'salesConnection' => $reservation['sales_connection'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'seatCode' => $reservation['seat_code'],
            'status' => $reservation['status'],
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
    $localTz = new DateTimeZone('Asia/Jakarta');
    $now = new DateTime('now', $localTz);

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
                'position' => $reservation['position'],
                'company' => $reservation['company'],
                'salesConnection' => $reservation['sales_connection'],
                'date' => $reservation['date'],
                'time' => substr($reservation['time'], 0, 5),
                'seatCode' => $reservation['seat_code'],
                'status' => $reservation['status'],
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
            SET verified = TRUE, verified_at = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$now->format('Y-m-d H:i:s'), $reservation['id']]);

        // Log verification
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $logStmt = $pdo->prepare("
            INSERT INTO reservation_verifications
            (reservation_id, qr_code, verified_at, verification_method, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $reservation['id'],
            $qrCode,
            $now->format('Y-m-d H:i:s'),
            $verificationMethod,
            $ipAddress,
            $userAgent
        ]);

        // Return updated reservation data
$formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'position' => $reservation['position'],
            'company' => $reservation['company'],
            'salesConnection' => $reservation['sales_connection'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'seatCode' => $reservation['seat_code'],
            'status' => $reservation['status'],
            'qrCode' => $reservation['qr_code'],
            'verified' => (bool) $reservation['verified'],
            'verifiedAt' => $reservation['verified_at'],
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
 * POST - Check reservation verification status without updating it
 */
function checkReservationVerificationStatus()
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

    try {
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

        $formatted = [
            'id' => (int) $reservation['id'],
            'name' => $reservation['name'],
            'position' => $reservation['position'],
            'company' => $reservation['company'],
            'salesConnection' => $reservation['sales_connection'],
            'date' => $reservation['date'],
            'time' => substr($reservation['time'], 0, 5),
            'table' => $reservation['seat_code'],
            'status' => $reservation['status'],
            'qrCode' => $reservation['qr_code'],
            'verified' => (bool) $reservation['verified'],
            'verifiedAt' => $reservation['verified_at'],
            'createdAt' => $reservation['created_at']
        ];

        echo json_encode([
            'success' => true,
            'message' => $reservation['verified']
                ? 'Reservation already verified'
                : 'Reservation not yet verified',
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to check verification status: ' . $e->getMessage()
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

/**
 * POST - Update sales_connection from Excel file
 * Excel must have two columns: "name" and "sales_connection" (case-insensitive headers)
 */
function updateSalesConnectionFromExcel()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

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

    $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('sales_', true) . '.xlsx';

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

        $headerMap = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $value = strtolower(trim((string) $sheet->getCell($columnLetter . '1')->getValue()));
            if (!empty($value)) {
                $headerMap[$col] = $value;
            }
        }

        $nameCol = array_search('name', $headerMap);
        $salesCol = array_search('sales_connection', $headerMap);

        if (!$nameCol || !$salesCol) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Excel must contain "name" and "sales_connection" columns'
            ]);
            unlink($tmpPath);
            return;
        }

        $nameLetter = Coordinate::stringFromColumnIndex($nameCol);
        $salesLetter = Coordinate::stringFromColumnIndex($salesCol);

        $updated = 0;
        $notFoundList = [];
        $errors = [];

        $pdo->beginTransaction();

        $updateStmt = $pdo->prepare("
            UPDATE reservations 
            SET sales_connection = ? 
            WHERE LOWER(name) = LOWER(?)
        ");

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = trim((string) $sheet->getCell($nameLetter . $row)->getValue());
            $salesConnection = trim((string) $sheet->getCell($salesLetter . $row)->getValue());

            if (empty($name)) {
                continue;
            }

            try {
                $updateStmt->execute([$salesConnection, $name]);
                $affected = $updateStmt->rowCount();

                if ($affected > 0) {
                    $updated++;
                } else {
                    $notFoundList[] = [
                        'name' => $name,
                        'sales_connection' => $salesConnection
                    ];
                }
            } catch (PDOException $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
            }
        }

        $pdo->commit();
        unlink($tmpPath);

        $response = [
            'success' => true,
            'message' => 'Sales connection updated successfully',
            'updated' => $updated,
            'notFound' => count($notFoundList),
            'notFoundList' => $notFoundList
        ];

        if (!empty($errors)) {
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
            'error' => 'Failed to update sales connection: ' . $e->getMessage()
        ]);
    }
}

/**
 * GET - Get distinct sales_connection values
 */
function getSalesConnections()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->query("
            SELECT DISTINCT sales_connection 
            FROM reservations 
            WHERE sales_connection IS NOT NULL AND sales_connection != ''
            ORDER BY sales_connection ASC
        ");
        $connections = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => $connections
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch sales connections: ' . $e->getMessage()
        ]);
    }
}
