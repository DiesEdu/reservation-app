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
require_once __DIR__ . '/../utils/reservationUtil.php';

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
        $localTz = new DateTimeZone('Asia/Jakarta'); // ensure timestamps use local time

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
            $rundownImagePath = $basePath . '/templates/rundown.png';
            $rundownImageExists = file_exists($rundownImagePath);
            error_log("createBlastInfoGenerateTicket: rundownImagePath = {$rundownImagePath}, exists = " . ($rundownImageExists ? 'true' : 'false'));
            $rundownImageData = $rundownImageExists ? file_get_contents($rundownImagePath) : null;
            $saved = saveImageAsPdf($imageData, $pdfPath, $rundownImageData);

            if ($saved) {
                // Store the timestamp when ticket was generated in local time
                $now = new DateTime('now', $localTz);
                $updateStmt->execute([$now->format('Y-m-d H:i:s'), $id]);
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
    $result = getReservationTicketData($reservationId, 'jpeg');

    if ($result['status'] !== 200 || empty($result['data'])) {
        error_log('generateTicketImage error: ' . ($result['error'] ?? 'unknown error'));
        return null;
    }

    return $result['data'];
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

/**
 * Convert binary image data to a PDF and save it.
 * If $secondImageData is provided, creates a 2-page PDF with that image on page 2.
 * Uses a tiny manual PDF builder with a JPEG stream for broad compatibility.
 */
function saveImageAsPdf($imageData, $outputPath, $secondImageData = null)
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

    // Process second image if provided
    $img2Data = null;
    $width2 = 0;
    $height2 = 0;
    $isPng = false;
    if ($secondImageData) {
        // Suppress libpng warning for PNG with incorrect sRGB profile
        $previousHandler = set_error_handler(function() { return true; });
        $img2 = imagecreatefromstring($secondImageData);
        set_error_handler($previousHandler);
        
        if ($img2) {
            $width2 = imagesx($img2);
            $height2 = imagesy($img2);
            $sourceInfo = getimagesizefromstring($secondImageData);
            $isPng = $sourceInfo && $sourceInfo[2] === IMAGETYPE_PNG;

            // Convert to JPEG for consistent PDF embedding
            ob_start();
            imagejpeg($img2, null, 90);
            $img2Data = ob_get_clean();
        }
    }

    // Build PDF
    $pageCount = $secondImageData && $img2Data ? 2 : 1;
    $offsets = [];
    $pdf = "%PDF-1.3\n";

    // 1: Catalog
    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";

    // 2: Pages
    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj<< /Type /Pages /Kids [" . ($pageCount === 2 ? "3 0 R 6 0 R" : "3 0 R") . "] /Count {$pageCount} >>endobj\n";

    // === Page 1 (ticket) ===
    // 3: Page 1
    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$width} {$height}] /Resources << /ProcSet [/PDF /ImageC] /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>endobj\n";

    // 4: Image XObject 1
    $imgLen = strlen($jpegData);
    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj<< /Type /XObject /Subtype /Image /Name /Im0 /Width {$width} /Height {$height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>stream\n";
    $pdf .= $jpegData . "\nendstream\nendobj\n";

    // 5: Contents 1
    $contentStream = "q\n{$width} 0 0 {$height} 0 0 cm\n/Im0 Do\nQ\n";
    $contentLen = strlen($contentStream);
    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj<< /Length {$contentLen} >>stream\n";
    $pdf .= $contentStream . "endstream\nendobj\n";

    if ($pageCount === 2) {
        // === Page 2 (rundown) ===
        // 6: Page 2
        $offsets[6] = strlen($pdf);
        $pdf .= "6 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$width2} {$height2}] /Resources << /ProcSet [/PDF /ImageC] /XObject << /Im1 7 0 R >> >> /Contents 8 0 R >>endobj\n";

        // 7: Image XObject 2
        $imgLen2 = strlen($img2Data);
        $offsets[7] = strlen($pdf);
        $pdf .= "7 0 obj<< /Type /XObject /Subtype /Image /Name /Im1 /Width {$width2} /Height {$height2} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen2} >>stream\n";
        $pdf .= $img2Data . "\nendstream\nendobj\n";

        // 8: Contents 2
        $contentStream2 = "q\n{$width2} 0 0 {$height2} 0 0 cm\n/Im1 Do\nQ\n";
        $contentLen2 = strlen($contentStream2);
        $offsets[8] = strlen($pdf);
        $pdf .= "8 0 obj<< /Length {$contentLen2} >>stream\n";
        $pdf .= $contentStream2 . "endstream\nendobj\n";

        // xref table
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 9\n0000000000 65535 f \n";
        for ($i = 1; $i <= 8; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<< /Size 9 /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";
    } else {
        // xref table for single page
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";
    }

    return file_put_contents($outputPath, $pdf) !== false;
}

?>
