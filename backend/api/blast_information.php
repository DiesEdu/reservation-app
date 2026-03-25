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
$path = str_replace('/api', '', $path);

// Debug: log after removing /api
// error_log("Path after remove /api: " . $path);

if (preg_match('#^/blast-info-email/(\d+)/ticket$#', $path, $matches) && $method === 'POST') {
    // Debug: log if route matched
    // error_log("Route matched, reservation ID: " . $matches[1]);

    // Validate content type for POST requests
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
        return;
    }
    createBlastInfo((int) $matches[1]);
} else {
    // Debug: Show what path was actually received
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint not found',
        'debug' => [
            'received_path' => $path,
            'method' => $method,
            'expected_pattern' => '/blast-info-email/{id}/ticket'
        ]
    ]);
}

function createBlastInfo($reservationId)
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

        // Add validation for $id
        // Debug: log what we received
        // error_log("Received reservationId: " . $reservationId . " (type: " . gettype($reservationId) . ")");

        if (!is_numeric($reservationId) || (int) $reservationId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid reservation ID',
                'debug' => [
                    'received_value' => $reservationId,
                    'received_type' => gettype($reservationId),
                    'is_numeric' => is_numeric($reservationId),
                    'casted_value' => (int) $reservationId
                ]
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT name, position, company, table_preference, qr_code, email FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
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

        // Send verification email with ticket attached
        $sent = sendInformationEmail($reservation['email'], $reservation['name'], $reservationId);

        if (!$sent) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Unable to send email'
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully'
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
    $subject = "Premiere Dinner Ticket - {$username}";

    // Generate ticket image for this reservation
    $ticketImage = generateTicketImage($reservationId);

    $message = "
    Hello {$username},
    
    Thank you for your reservation! Please find your ticket attached to this email.
    
    Best regards,
    The Resonanz Team
    ";

    $headers = "From: admin@reserve.resonanz.id";

    // If we have a ticket image, attach it
    if ($ticketImage) {
        return sendEmailWithAttachment($email, $subject, $message, $headers, $ticketImage, "ticket-{$reservationId}.png");
    }

    return mail($email, $subject, $message, $headers);
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

        $templatePath = __DIR__ . '/../templates/ticket-halal_bihalal.png';
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

        $fontPathCustom = __DIR__ . '/../fonts/IBMPlexSerif-Regular.ttf';

        $fontPath = resolveTicketFont();
        $canUseTtf = $fontPath && function_exists('imagettftext');

        $textColor = imagecolorallocate($image, 20, 20, 20);
        $shadowColor = imagecolorallocatealpha($image, 255, 255, 255, 80);

        $name = $reservation['name'];
        $position = $reservation['position'];
        $company = $reservation['company'];
        $table = $reservation['table_preference'];
        $qrData = $reservation['qr_code'];

        $nameSize = max(28, (int) ($width * 0.035));
        $positionSize = max(20, (int) ($width * 0.025));
        $companySize = max(20, (int) ($width * 0.035));
        $tableSize = max(22, (int) ($width * 0.025));

        // Vertical layout: name -> QR -> table
        $nameY = (int) ($height * 0.36);
        $companyY = (int) ($height * 0.43);
        $positionY = (int) ($height * 0.50);
        $qrGapBottom = (int) ($height * 0.05);
        $tableY = null; // set after QR position is known

        if ($canUseTtf) {
            drawLeftedTtfText($image, $nameSize, 100, $nameY, $fontPath, $name, $textColor, $shadowColor);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 25, 100, $nameY, strtoupper($name), $textColor);
        }

        // Draw position if available
        if ($canUseTtf && !empty($position)) {
            drawLeftedTtfText($image, $positionSize, 100, $positionY, $fontPath, $position, $textColor, $shadowColor);
        } elseif (!empty($position)) {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 20, 100, $positionY, strtoupper($position), $textColor);
        }

        // Draw company if available
        if ($canUseTtf && !empty($company)) {
            drawLeftedTtfText($image, $companySize, 100, $companyY, $fontPath, $company, $textColor, $shadowColor);
        } elseif (!empty($company)) {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 20, 100, $companyY, strtoupper($company), $textColor);
        }

        // Add QR code (uses reservation.qr_code value)
        if (!empty($qrData)) {
            $qrImage = buildQrImage($qrData, 190);

            if ($qrImage) {
                $qrWidth = imagesx($qrImage);
                $qrHeight = imagesy($qrImage);
                $qrX = (int) (700);
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
                $tableY = $qrY + $qrHeight + $qrGapBottom;
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
            drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
        } else {
            drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
        }

        // Capture output to string instead of direct output
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

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
    $body .= "Content-Type: image/png; name=\"{$filename}\"\r\n";
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

        $templatePath = __DIR__ . '/../templates/ticket-halal_bihalal.png';
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

        $fontPathCustom = __DIR__ . '/../fonts/IBMPlexSerif-Regular.ttf';

        $fontPath = resolveTicketFont();
        $canUseTtf = $fontPath && function_exists('imagettftext');

        $textColor = imagecolorallocate($image, 20, 20, 20);
        $shadowColor = imagecolorallocatealpha($image, 255, 255, 255, 80);

        $name = $reservation['name'];
        $position = $reservation['position'];
        $company = $reservation['company'];
        $table = $reservation['table_preference'];
        $qrData = $reservation['qr_code'];

        $nameSize = max(28, (int) ($width * 0.035));
        $positionSize = max(20, (int) ($width * 0.025));
        $companySize = max(20, (int) ($width * 0.035));
        $tableSize = max(22, (int) ($width * 0.025));

        // Vertical layout: name -> QR -> table
        $nameY = (int) ($height * 0.36);
        $companyY = (int) ($height * 0.43);
        $positionY = (int) ($height * 0.50);
        $qrGapBottom = (int) ($height * 0.05);
        $tableY = null; // set after QR position is known

        if ($canUseTtf) {
            drawLeftedTtfText($image, $nameSize, 100, $nameY, $fontPath, $name, $textColor, $shadowColor);
        } else {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 25, 100, $nameY, strtoupper($name), $textColor);
        }

        // Draw position if available
        if ($canUseTtf && !empty($position)) {
            drawLeftedTtfText($image, $positionSize, 100, $positionY, $fontPath, $position, $textColor, $shadowColor);
        } elseif (!empty($position)) {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 20, 100, $positionY, strtoupper($position), $textColor);
        }

        // Draw company if available
        if ($canUseTtf && !empty($company)) {
            drawLeftedTtfText($image, $companySize, 100, $companyY, $fontPath, $company, $textColor, $shadowColor);
        } elseif (!empty($company)) {
            // Fallback to built-in GD font if TTF support is missing
            drawLeftedGdText($image, $fontPathCustom, 20, 100, $companyY, strtoupper($company), $textColor);
        }

        // Add QR code (uses reservation.qr_code value)
        if (!empty($qrData)) {
            $qrImage = buildQrImage($qrData, 190);

            if ($qrImage) {
                $qrWidth = imagesx($qrImage);
                $qrHeight = imagesy($qrImage);
                $qrX = (int) (700);
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
                $tableY = $qrY + $qrHeight + $qrGapBottom;
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
            drawLeftedTtfText($image, $tableSize, 700, $tableY, $fontPath, $table, $textColor, $shadowColor);
        } else {
            drawLeftedGdText($image, $fontPathCustom, 15, 700, $tableY, strtoupper($table), $textColor);
        }

        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="ticket-' . $id . '.png"'); // Fixed quotes
        imagepng($image);
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
    $candidates = [
        'C:\\Windows\\Fonts\\arial.ttf',        // Fixed Windows path
        'C:\\Windows\\Fonts\\arialbd.ttf',      // Fixed Windows path
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/System/Library/Fonts/Helvetica.ttc'   // macOS path
    ];

    foreach ($candidates as $font) {
        if (file_exists($font)) {
            return $font;
        }
    }

    return null;
}

/**
 * Draw centered TTF text with a subtle shadow
 */
function drawLeftedTtfText($image, $fontSize, $xVal, $y, $fontPath, $text, $color, $shadowColor)
{
    $angle = 0;
    $x = (int) ($xVal);

    // Shadow for readability on busy backgrounds
    imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $text);
    imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $text);
}

/**
 * Draw centered GD text fallback (no TTF)
 */

function drawLeftedGdText($image, $fontPath, $fontSize, $xVal, $y, $text, $color)
{
    // Get text bounding box
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = $bbox[2] - $bbox[0];

    // Center horizontally (or adjust as needed)
    $imageWidth = imagesx($image);
    $x = $xVal;

    // Draw text
    imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
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

?>