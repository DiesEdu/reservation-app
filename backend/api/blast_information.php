<?php

// Configure allowed origins - only allow specific domains
$allowedOrigins = ['http://localhost:5173', 'http://localhost:8000', 'https://reserve.resonanz.id'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
// Load composer autoloader for Endroid QR Code
require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Get the request method and parse the URL
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Debug: log the actual path being processed (remove in production)
// error_log("Request path: " . $path);

// Remove /api prefix if present
$path = preg_replace('#^/api/?#', '/', $path); // remove leading /api only
$path = rtrim($path, '/'); // normalize trailing slash
$path = $path === '' ? '/' : $path;

// Debug: log after removing /api
// error_log("Path after remove /api: " . $path);

if ($path === '/blast-info-email/ticket' && $method === 'POST') {
    // Debug: log if route matched
    // error_log("Route matched, reservation ID: " . $matches[1]);

    // Validate content type for POST requests
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
        return;
    }
    createBlastInfo();
} elseif ($path === '/blast-info-wa/ticket' && $method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
        return;
    }
    createBlastInfoWhatsapp();
} elseif ($path === '/blast-info-generate/ticket' && $method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
        return;
    }
    createBlastInfoGenerateTicket();
} else {
    // Debug: Show what path was actually received
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint not found',
        'debug' => [
            'received_path' => $path,
            'method' => $method,
            'expected_pattern' => '/blast-info-email/ticket'
        ]
    ]);
}

/**
 * Generate ticket PDFs for reservations that don't have one yet.
 * Reuses the local ticket generator (equivalent to GET /reservations/{id}/ticket),
 * converts the image to PDF, saves it under backend/upload/pdf, and updates
 * reservations.generate_ticket with the relative file path.
 */
function createBlastInfoGenerateTicket()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Ensure output directory exists
    $basePath = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
    $outputDir = $basePath . '/upload/pdf';
    if (!is_dir($outputDir)) {
        if (!mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to prepare PDF output directory'
            ]);
            return;
        }
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, table_preference
            FROM reservations
            WHERE generate_ticket IS NULL
        ");
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$reservations) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'No pending reservations to generate tickets',
                'generated' => 0,
                'failed' => []
            ]);
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE reservations SET generate_ticket = ? WHERE id = ?");

        $generated = 0;
        $failed = [];

        foreach ($reservations as $reservation) {
            $id = $reservation['id'];
            $name = $reservation['name'];
            $table = $reservation['table_preference'];

            // Generate ticket image (same as calling /reservations/{id}/ticket)
            $imageData = generateTicketImage($id);

            if (!$imageData) {
                $failed[] = $id;
                continue;
            }

            $pdfPath = $outputDir . "/{$table}_{$name}.pdf";
            $saved = saveImageAsPdf($imageData, $pdfPath);

            if ($saved) {
                // Store the timestamp when ticket was generated
                $updateStmt->execute([date('Y-m-d H:i:s'), $id]);
                $generated++;
            } else {
                $failed[] = $id;
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Ticket PDF generation completed',
            'generated' => $generated,
            'failed' => $failed
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        error_log('createBlastInfoGenerateTicket error: ' . $e->getMessage());
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage()
        ]);
        error_log('createBlastInfoGenerateTicket error: ' . $e->getMessage());
    }
}

function createBlastInfo()
{
    // $auth = requireAuth();

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // if (!$auth) {
        //     http_response_code(404);
        //     echo json_encode([
        //         'success' => false,
        //         'error' => 'Please login first!'
        //     ]);
        //     return;
        // }

        // Fetch all reservations that have not been emailed yet
        $stmt = $pdo->prepare("
            SELECT id, name, position, company, table_preference, qr_code, email
            FROM reservations
            WHERE send_email IS NULL OR send_email = ''
        ");
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$reservations) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'No pending reservations to email',
                'sent' => 0,
                'failed' => 0
            ]);
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE reservations SET send_email = NOW() WHERE id = ?");

        $sentCount = 0;
        $failed = [];

        foreach ($reservations as $reservation) {
            $sent = sendInformationEmail($reservation['email'], $reservation['name'], $reservation['id']);

            if ($sent) {
                $updateStmt->execute([$reservation['id']]);
                $sentCount++;
            } else {
                $failed[] = $reservation['id'];
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Blast email process completed',
            'sent' => $sentCount,
            'failed' => $failed
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred while processing your request'
        ]);
        // Log the actual error for debugging (don't expose to user)
        error_log('createBlastInfo error: ' . $e->getMessage());
    }
}

function sendInformationEmail($email, $username, $reservationId)
{
    $subject = "Halal Bihalal - Connected in Harmony";

    // Generate ticket image for this reservation
    $ticketImage = generateTicketImage($reservationId);

    $message = "
    Hello {$username},
    
    Thank you for your confirmation! Please find your ticket attached to this email.
    
    Best regards,
    The Resonanz Team
    ";

    $headers = "From: admin@reserve.resonanz.id";

    // If we have a ticket image, attach it
    if ($ticketImage) {
        return sendEmailWithAttachment($email, $subject, $message, $headers, $ticketImage, "ticket-{$reservationId}.jpg");
    }

    return mail($email, $subject, $message, $headers);
}

/**
 * Blast WhatsApp messages for reservations not yet messaged
 */
function createBlastInfoWhatsapp()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, phone
            FROM reservations
            WHERE (send_whatsapp IS NULL) 
              AND (phone IS NOT NULL AND phone <> '')
        ");
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$reservations) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'No pending reservations to WhatsApp',
                'sent' => 0,
                'failed' => 0
            ]);
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE reservations SET send_whatsapp = NOW() WHERE id = ?");

        $sentCount = 0;
        $failed = [];

        foreach ($reservations as $reservation) {
            $phone = normalizePhoneToE164($reservation['phone']);
            if (!$phone) {
                $failed[] = $reservation['id'];
                continue;
            }

            $sent = sendInformationWhatsapp($phone, $reservation['name'], $reservation['id']);

            if ($sent) {
                $updateStmt->execute([$reservation['id']]);
                $sentCount++;
            } else {
                $failed[] = $reservation['id'];
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Blast WhatsApp process completed',
            'sent' => $sentCount,
            'failed' => $failed
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred while processing your request'
        ]);
        error_log('createBlastInfoWhatsapp error: ' . $e->getMessage());
    }
}

/**
 * Normalize Indonesian local numbers to E.164 with +62 prefix when they start with 0.
 * Leaves already-E.164 numbers untouched. Returns null if empty after cleanup.
 */
function normalizePhoneToE164($phone)
{
    $phone = trim($phone ?? '');
    if ($phone === '') {
        return null;
    }

    // Remove spaces, dashes, parentheses
    $clean = preg_replace('/[\\s\\-()]/', '', $phone);

    if (strpos($clean, '+') === 0) {
        return $clean; // already has country code
    }

    if (strpos($clean, '0') === 0) {
        return '+62' . substr($clean, 1);
    }

    return $clean;
}

/**
 * Send WhatsApp message using HitlChat API
 * Expects env var HITLCHAT_TOKEN (fallback WA_ACCESS_TOKEN) to be set.
 *
 * @param string $phoneNumber E.164 formatted number (e.g., +628123456789)
 * @param string $username     Recipient name
 * @param int    $reservationId Reservation identifier (for logging)
 * @return bool Success flag
 */
function sendInformationWhatsapp($phoneNumber, $username, $reservationId)
{
    $accessToken = getenv('HITLCHAT_TOKEN') ?: getenv('WA_ACCESS_TOKEN');

    if (empty($accessToken)) {
        error_log('WhatsApp config missing: set HITLCHAT_TOKEN or WA_ACCESS_TOKEN');
        return false;
    }

    // Message text mirrors the email content
    $textPayload = [
        'type' => 'text',
        'recipientNumber' => $phoneNumber,
        'messageText' => "Hello {$username},\n\nThank you for your confirmation! Please find your ticket attached.\n\nBest regards,\nThe Resonanz Team"
    ];

    $textSent = sendWhatsAppPayload($accessToken, $textPayload);

    // Attempt to send ticket image if available
    $ticketImage = generateTicketImage($reservationId);
    $imageSent = true;

    if ($ticketImage) {
        $imagePayload = [
            'type' => 'image',
            'recipientNumber' => $phoneNumber,
            'fileName' => "ticket-{$reservationId}.jpg",
            'mediaBase64' => base64_encode($ticketImage),
            'caption' => "Ticket for {$username} (ID: {$reservationId})"
        ];
        $imageSent = sendWhatsAppPayload($accessToken, $imagePayload);
    }

    return $textSent && $imageSent;
}

/**
 * Low-level helper to send a message payload via HitlChat API
 */
function sendWhatsAppPayload($accessToken, $payload)
{
    $url = "https://api-portal.hitlchat.io/api/v1/public/messages/send";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('WhatsApp cURL error: ' . curl_error($ch));
        $ch = null; // release handle (curl_close is deprecated in PHP 8.5)
        return false;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $ch = null; // release handle

    if ($statusCode >= 200 && $statusCode < 300) {
        return true;
    }

    error_log('WhatsApp send failed with status ' . $statusCode . ' response: ' . $response);
    return false;
}

/**
 * Generate ticket image and return as string
 */
function generateTicketImage($reservationId)
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Add validation for $id
        if (!is_numeric($reservationId) || $reservationId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT name, position, company, table_preference, qr_code FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            return null;
        }

        $templatePath = __DIR__ . '/../templates/plain-reservation.png';
        if (!file_exists($templatePath)) {
            return null;
        }

        $image = imagecreatefrompng($templatePath);
        if (!$image) {
            return null;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        $fontPathCustom = __DIR__ . '/../templates/fonts/IBMPlexSerif-Regular.ttf';

        $fontPath = resolveTicketFont();
        $canUseTtf = $fontPath && function_exists('imagettftext');

        $textColor = imagecolorallocate($image, 20, 20, 20);
        $shadowColor = imagecolorallocatealpha($image, 255, 255, 255, 80);

        $name = $reservation['name'];
        $position = $reservation['position'];
        $company = $reservation['company'];
        $tableTitle = 'Table';
        $table = $reservation['table_preference'];
        $qrData = $reservation['qr_code'];

        $nameSize = max(28, (int) ($width * 0.035));
        $positionSize = max(20, (int) ($width * 0.025));
        $companySize = max(20, (int) ($width * 0.035));
        $tableTitleSize = max(20, (int) ($width * 0.025));
        $tableSize = max(22, (int) ($width * 0.025));

        // Vertical layout: name -> QR -> table
        $nameY = (int) ($height * 0.72);
        $companyY = (int) ($height * 0.77);
        $positionY = (int) ($height * 0.80);
        $qrGapBottom = (int) ($height * 0.01);
        $tableTitleY = null;
        $tableY = null; // set after QR position is known

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $nameSize, 100, $nameY, $fontPath, $name, $textColor, $shadowColor);
            drawCenteredTtfText($image, $nameSize, $nameY, $fontPath, $name, $textColor, $shadowColor);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 25, 100, $nameY, strtoupper($name), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 15, $nameY, strtoupper($name), $textColor);
        }

        // Draw position if available
        if ($canUseTtf) {
            // drawLeftedTtfText($image, $positionSize, 100, $positionY, $fontPath, $position, $textColor, $shadowColor);
            drawCenteredTtfText($image, $positionSize, $positionY, $fontPath, $position, $textColor, $shadowColor, true);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 20, 100, $positionY, strtoupper($position), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 10, $positionY, strtoupper($position), $textColor, true);
        }

        // Draw company if available
        if ($canUseTtf && !empty($company)) {
            // drawLeftedTtfText($image, $companySize, 100, $companyY, $fontPath, $company, $textColor, $shadowColor);
            drawCenteredTtfText($image, $companySize, $companyY, $fontPath, $company, $textColor, $shadowColor);
        } elseif (!empty($company)) {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 20, 100, $companyY, strtoupper($company), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 10, $companyY, strtoupper($company), $textColor);
        }

        // Add QR code (uses reservation.qr_code value)
        if (!empty($qrData)) {
            $qrImage = buildQrImage($qrData, 190);

            if ($qrImage) {
                $qrWidth = imagesx($qrImage);
                $qrHeight = imagesy($qrImage);
                // $qrX = (int) (700);
                $qrX = (int) (($width - $qrWidth) / 2);
                $qrY = (int) ($height * 0.34);

                imagecopy(
                    $image,
                    $qrImage,
                    $qrX,
                    $qrY,
                    0,
                    0,
                    $qrWidth,
                    $qrHeight
                );

                // Set table Y relative to QR bottom
                $tableTitleY = $qrY + $qrHeight + $qrGapBottom;
                $tableY = $qrY + $qrHeight + $qrGapBottom + (int) ($height * 0.07);
            } else {
                error_log("Failed to generate QR code for reservation ID: " . $reservationId);
            }
        }

        // Draw table text after QR placement
        if ($tableY === null) {
            // Fallback if no QR: place below name with a gap
            $tableY = $nameY + (int) ($height * 0.12);
        }

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
            drawCenteredTtfText($image, $tableTitleSize, $tableTitleY, $fontPath, $table, $textColor, $shadowColor, true);
        } else {
            // drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 15, $tableTitleY, strtoupper($tableTitle), $textColor, true);
        }

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
            drawCenteredTtfText($image, $tableSize, $tableY, $fontPath, $table, $textColor, $shadowColor);
        } else {
            // drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 45, $tableY, strtoupper($table), $textColor);
        }

        // Capture output to string instead of direct output
        ob_start();
        imagejpeg($image, null, 85); // Use JPEG with 85% quality to reduce file size
        $imageData = ob_get_clean();

        return $imageData;

    } catch (Exception $e) {
        error_log('generateTicketImage error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Send email with attachment
 */
function sendEmailWithAttachment($to, $subject, $message, $headers, $attachmentData, $filename)
{
    // Generate a unique boundary for the email
    $boundary = md5(uniqid(time()));

    // Prepare headers
    $headers .= "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    // Build the email body with attachment
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: image/jpeg; name=\"{$filename}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $body .= chunk_split(base64_encode($attachmentData)) . "\r\n";
    $body .= "--{$boundary}--\r\n";

    return mail($to, $subject, $body, $headers);
}

function renderReservationTicket($id)
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Add validation for $id
        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid reservation ID'
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT name, position, company, table_preference, qr_code FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Reservation not found'
            ]);
            return;
        }

        $templatePath = __DIR__ . '/../templates/plain-reservation.png';
        if (!file_exists($templatePath)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Ticket template not found'
            ]);
            return;
        }

        $image = imagecreatefrompng($templatePath);
        if (!$image) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load ticket template'
            ]);
            return;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        $fontPathCustom = __DIR__ . '/../templates/fonts/IBMPlexSerif-Regular.ttf';

        $fontPath = resolveTicketFont();
        $canUseTtf = $fontPath && function_exists('imagettftext');

        $textColor = imagecolorallocate($image, 20, 20, 20);
        $shadowColor = imagecolorallocatealpha($image, 255, 255, 255, 80);

        $name = $reservation['name'];
        $position = $reservation['position'];
        $company = $reservation['company'];
        $tableTitle = 'Table';
        $table = $reservation['table_preference'];
        $qrData = $reservation['qr_code'];

        $nameSize = max(28, (int) ($width * 0.035));
        $positionSize = max(20, (int) ($width * 0.025));
        $companySize = max(20, (int) ($width * 0.035));
        $tableTitleSize = max(20, (int) ($width * 0.025));
        $tableSize = max(22, (int) ($width * 0.025));

        // Vertical layout: name -> QR -> table
        $nameY = (int) ($height * 0.72);
        $companyY = (int) ($height * 0.77);
        $positionY = (int) ($height * 0.80);
        $qrGapBottom = (int) ($height * 0.01);
        $tableTitleY = null;
        $tableY = null; // set after QR position is known

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $nameSize, 100, $nameY, $fontPath, $name, $textColor, $shadowColor);
            drawCenteredTtfText($image, $nameSize, $nameY, $fontPath, $name, $textColor, $shadowColor);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 25, 100, $nameY, strtoupper($name), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 15, $nameY, strtoupper($name), $textColor);
        }

        // Draw position if available
        if ($canUseTtf) {
            // drawLeftedTtfText($image, $positionSize, 100, $positionY, $fontPath, $position, $textColor, $shadowColor);
            drawCenteredTtfText($image, $positionSize, $positionY, $fontPath, $position, $textColor, $shadowColor, true);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 20, 100, $positionY, strtoupper($position), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 10, $positionY, strtoupper($position), $textColor, true);
        }

        // Draw company if available
        if ($canUseTtf && !empty($company)) {
            // drawLeftedTtfText($image, $companySize, 100, $companyY, $fontPath, $company, $textColor, $shadowColor);
            drawCenteredTtfText($image, $companySize, $companyY, $fontPath, $company, $textColor, $shadowColor);
        } elseif (!empty($company)) {
            // Fallback to built-in GD font if TTF support is missing
            // drawLeftedGdText($image, $fontPathCustom, 20, 100, $companyY, strtoupper($company), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 10, $companyY, strtoupper($company), $textColor);
        }

        // Add QR code (uses reservation.qr_code value)
        if (!empty($qrData)) {
            $qrImage = buildQrImage($qrData, 190);

            if ($qrImage) {
                $qrWidth = imagesx($qrImage);
                $qrHeight = imagesy($qrImage);
                // $qrX = (int) (700);
                $qrX = (int) (($width - $qrWidth) / 2);
                $qrY = (int) ($height * 0.34);

                imagecopy(
                    $image,
                    $qrImage,
                    $qrX,
                    $qrY,
                    0,
                    0,
                    $qrWidth,
                    $qrHeight
                );

                // Set table Y relative to QR bottom
                $tableTitleY = $qrY + $qrHeight + $qrGapBottom;
                $tableY = $qrY + $qrHeight + $qrGapBottom + (int) ($height * 0.07);
            } else {
                error_log("Failed to generate QR code for reservation ID: " . $id);
            }
        }

        // Draw table text after QR placement
        if ($tableY === null) {
            // Fallback if no QR: place below name with a gap
            $tableY = $nameY + (int) ($height * 0.12);
        }

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
            drawCenteredTtfText($image, $tableTitleSize, $tableTitleY, $fontPath, $table, $textColor, $shadowColor, true);
        } else {
            // drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 15, $tableTitleY, strtoupper($tableTitle), $textColor, true);
        }

        if ($canUseTtf) {
            // drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
            drawCenteredTtfText($image, $tableSize, $tableY, $fontPath, $table, $textColor, $shadowColor);
        } else {
            // drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
            drawCenteredGdText($image, $fontPathCustom, 45, $tableY, strtoupper($table), $textColor);
        }

        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="ticket-' . $id . '.jpg"'); // Fixed quotes
        imagejpeg($image, null, 85);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate ticket: ' . $e->getMessage()
        ]);
    }
}

/**
 * Try to find an available TTF font on the host
 */
function resolveTicketFont()
{
    // $candidates = [
    //     'C:\\Windows\\Fonts\\arial.ttf',        // Fixed Windows path
    //     'C:\\Windows\\Fonts\\arialbd.ttf',      // Fixed Windows path
    //     '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    //     '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    //     '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    //     '/System/Library/Fonts/Helvetica.ttc'   // macOS path
    // ];

    // foreach ($candidates as $font) {
    //     if (file_exists($font)) {
    //         return $font;
    //     }
    // }

    return null;
}

/**
 * Draw centered TTF text with a subtle shadow
 */
function drawCenteredTtfText($image, $fontSize, $y, $fontPath, $text, $color, $shadowColor)
{
    $width = imagesx($image);
    $angle = 0;
    $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);
    $textWidth = $bbox[2] - $bbox[0];
    $x = (int) (($width - $textWidth) / 2);

    // Shadow for readability on busy backgrounds
    imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $text);
    imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $text);
}

/**
 * Draw centered GD text fallback (no TTF)
 */

function drawCenteredGdText($image, $fontPath, $fontSize, $y, $text, $color, $isItalic = false)
{
    // Pick TTF font if available; otherwise fall back to GD built-in fonts
    $fontPath = $isItalic
        ? __DIR__ . '/../templates/fonts/IBMPlexSerif-Italic.ttf'
        : __DIR__ . '/../templates/fonts/IBMPlexSerif-Regular.ttf';

    $canUseTtf = function_exists('imagettftext') && file_exists($fontPath);

    $imageWidth = imagesx($image);

    if ($canUseTtf) {
        // Center using TTF
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $x = (int) (($imageWidth - $textWidth) / 2);
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
    } else {
        // GD fallback using built-in bitmap fonts
        $font = 1;
        if ($fontSize >= 25) {
            $font = 5;
        } elseif ($fontSize >= 20) {
            $font = 4;
        } elseif ($fontSize >= 15) {
            $font = 3;
        } elseif ($fontSize >= 10) {
            $font = 2;
        }

        $textWidth = imagefontwidth($font) * strlen($text);
        $x = (int) (($imageWidth - $textWidth) / 2);
        // imagestring draws from top-left; lift to align baseline-ish
        $yPos = $y - imagefontheight($font);
        imagestring($image, $font, $x, $yPos, $text, $color);
    }
}

/**
 * Build a GD image for a QR code at requested size using Endroid library
 */
function buildQrImage($data, $size = 300)
{
    try {
        // Create QR code - in Endroid QR Code v6.x, size and margin are constructor params
        $qrCode = new QrCode(
            data: $data,
            size: $size,
            margin: 10
        );

        // Create writer and get PNG data
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Get PNG string and convert to GD image
        $pngData = $result->getString();
        $image = imagecreatefromstring($pngData);

        return $image ?: null;

    } catch (Exception $e) {
        error_log('QR Code generation error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Convert binary image data to a single-page PDF and save it.
 * Uses a tiny manual PDF builder with a JPEG stream for broad compatibility.
 */
function saveImageAsPdf($imageData, $outputPath)
{
    // Create GD image to get dimensions and re-encode as JPEG (PDF friendly)
    $img = imagecreatefromstring($imageData);
    if (!$img) {
        return false;
    }

    $width = imagesx($img);
    $height = imagesy($img);

    ob_start();
    imagejpeg($img, null, 90);
    $jpegData = ob_get_clean();

    if (!$jpegData) {
        return false;
    }

    $imgLen = strlen($jpegData);
    $contentStream = "q\n{$width} 0 0 {$height} 0 0 cm\n/Im0 Do\nQ\n";
    $contentLen = strlen($contentStream);

    // Build a minimal PDF with one image XObject
    $offsets = [];
    $pdf = "%PDF-1.3\n";

    // 1: Catalog
    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";

    // 2: Pages
    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";

    // 3: Page
    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$width} {$height}] /Resources << /ProcSet [/PDF /ImageC] /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>endobj\n";

    // 4: Image XObject
    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj<< /Type /XObject /Subtype /Image /Name /Im0 /Width {$width} /Height {$height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>stream\n";
    $pdf .= $jpegData . "\nendstream\nendobj\n";

    // 5: Contents
    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj<< /Length {$contentLen} >>stream\n";
    $pdf .= $contentStream . "endstream\nendobj\n";

    // xref table\n";
    $xrefStart = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

    return file_put_contents($outputPath, $pdf) !== false;
}

?>
