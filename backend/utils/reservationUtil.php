<?php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Build ticket image data for a reservation.
 * Returns structured result to support both HTTP streaming and binary attachments.
 *
 * @param int    $reservationId Reservation ID
 * @param string $format        'png' (default) or 'jpeg'
 * @return array{status:int,error:?string,data:?string,mime:?string,filename:?string}
 */
function getReservationTicketData($reservationId, $format = 'png')
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    if (!is_numeric($reservationId) || $reservationId <= 0) {
        return [
            'status' => 400,
            'error' => 'Invalid reservation ID',
            'data' => null,
            'mime' => null,
            'filename' => null,
        ];
    }

    $stmt = $pdo->prepare("SELECT name, position, company, table_preference, qr_code FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        return [
            'status' => 404,
            'error' => 'Reservation not found',
            'data' => null,
            'mime' => null,
            'filename' => null,
        ];
    }

    try {
        $image = buildReservationTicketImage($reservation);
    } catch (RuntimeException $e) {
        return [
            'status' => 500,
            'error' => $e->getMessage(),
            'data' => null,
            'mime' => null,
            'filename' => null,
        ];
    }

    ob_start();
    $extension = '.png';
    $mime = 'image/png';
    if (strtolower($format) === 'jpeg' || strtolower($format) === 'jpg') {
        imagejpeg($image, null, 85);
        $extension = '.jpg';
        $mime = 'image/jpeg';
    } else {
        imagepng($image);
    }
    $data = ob_get_clean();

    return [
        'status' => 200,
        'error' => null,
        'data' => $data,
        'mime' => $mime,
        'filename' => "ticket-{$reservationId}{$extension}",
    ];
}

/**
 * Stream reservation ticket image directly to HTTP response.
 */
function renderReservationTicket($id, $format = 'png')
{
    $result = getReservationTicketData($id, $format);

    if ($result['status'] !== 200 || empty($result['data'])) {
        http_response_code($result['status']);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to generate ticket',
        ]);
        return;
    }

    header('Content-Type: ' . $result['mime']);
    header('Content-Disposition: inline; filename="' . $result['filename'] . '"');
    echo $result['data'];
    exit();
}

/**
 * Create a GD image for the reservation ticket using the shared template.
 *
 * @param array $reservation Must include name, position, company, table_preference, qr_code
 * @return resource GD image
 */
function buildReservationTicketImage(array $reservation)
{
    $templatePath = __DIR__ . '/../templates/plain-reservation.png';
    if (!file_exists($templatePath)) {
        throw new RuntimeException('Ticket template not found');
    }

    $image = imagecreatefrompng($templatePath);
    if (!$image) {
        throw new RuntimeException('Failed to load ticket template');
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
    $nameY = (int) ($height * 0.74);
    $companyY = (int) ($height * 0.79);
    $positionY = (int) ($height * 0.82);
    $qrGapBottom = (int) ($height * 0.03);
    $tableTitleY = null;
    $tableY = null; // set after QR position is known

    if ($canUseTtf) {
        drawCenteredTtfText($image, $nameSize, $nameY, $fontPath, $name, $textColor, $shadowColor);
    } else {
        drawCenteredGdText($image, $fontPathCustom, 15, $nameY, strtoupper($name), $textColor, false, true);
    }

    // Draw position if available
    if ($canUseTtf) {
        drawCenteredTtfText($image, $positionSize, $positionY, $fontPath, $position, $textColor, $shadowColor, true);
    } else {
        drawCenteredGdText($image, $fontPathCustom, 10, $positionY, strtoupper($position), $textColor, true);
    }

    // Draw company if available
    if ($canUseTtf && !empty($company)) {
        drawCenteredTtfText($image, $companySize, $companyY, $fontPath, $company, $textColor, $shadowColor);
    } elseif (!empty($company)) {
        drawCenteredGdText($image, $fontPathCustom, 10, $companyY, strtoupper($company), $textColor);
    }

    // Add QR code (uses reservation.qr_code value)
    if (!empty($qrData)) {
        $qrImage = buildQrImage($qrData, 190);

        if ($qrImage) {
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
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
            error_log("Failed to generate QR code for reservation ID: " . $reservation['qr_code']);
        }
    }

    // Draw table text after QR placement
    if ($tableY === null) {
        // Fallback if no QR: place below name with a gap
        $tableY = $nameY + (int) ($height * 0.12);
    }

    if ($canUseTtf) {
        drawCenteredTtfText($image, $tableTitleSize, $tableTitleY, $fontPath, $table, $textColor, $shadowColor, true);
    } else {
        drawCenteredGdText($image, $fontPathCustom, 15, $tableTitleY, strtoupper($tableTitle), $textColor, true);
    }

    if ($canUseTtf) {
        drawCenteredTtfText($image, $tableSize, $tableY, $fontPath, $table, $textColor, $shadowColor);
    } else {
        drawCenteredGdText($image, $fontPathCustom, 45, $tableY, strtoupper($table), $textColor);
    }

    return $image;
}

/**
 * Try to find an available TTF font on the host
 */
function resolveTicketFont()
{
    return null;
}

/**
 * Draw centered TTF text with a subtle shadow
 */
function drawCenteredTtfText($image, $fontSize, $y, $fontPath, $text, $color, $shadowColor, $italic = false)
{
    $width = imagesx($image);
    $angle = $italic ? -12 : 0; // Use negative angle for italic effect
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
function drawCenteredGdText($image, $fontPath, $fontSize, $y, $text, $color, $isItalic = false, $isBold = false)
{
    if ($isBold) {
    $fontPath = __DIR__ . '/../templates/fonts/IBMPlexSerif-Bold.ttf';
} elseif ($isItalic) {
    $fontPath = __DIR__ . '/../templates/fonts/IBMPlexSerif-Italic.ttf';
} else {
    $fontPath = __DIR__ . '/../templates/fonts/IBMPlexSerif-Regular.ttf';
}

    $canUseTtf = function_exists('imagettftext') && file_exists($fontPath);

    $imageWidth = imagesx($image);

    if ($canUseTtf) {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $x = (int) (($imageWidth - $textWidth) / 2);
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
    } else {
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
        $yPos = $y - imagefontheight($font);
        imagestring($image, $font, $x, $yPos, $text, $color);
    }
}

/**
 * Draw lefted TTF text fallback
 */
function drawLeftedTtfText($image, $fontSize, $xVal, $y, $fontPath, $text, $color, $shadowColor)
{
    $angle = 0;
    $x = (int) ($xVal);

    imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $text);
    imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $text);
}

/**
 * Draw lefted GD text fallback (no TTF)
 */
function drawLeftedGdText($image, $fontPath, $fontSize, $xVal, $y, $text, $color)
{
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = $bbox[2] - $bbox[0];

    $imageWidth = imagesx($image);
    $x = $xVal;

    imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
}

/**
 * Build a GD image for a QR code at requested size using Endroid library
 */
function buildQrImage($data, $size = 300)
{
    try {
        $qrCode = new QrCode(
            data: $data,
            size: $size,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $pngData = $result->getString();
        $image = imagecreatefromstring($pngData);

        return $image ?: null;
    } catch (Exception $e) {
        error_log('QR Code generation error: ' . $e->getMessage());
        return null;
    }
}
