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

    $stmt = $pdo->prepare("SELECT name, position, company, seat_code, table_color, qr_code FROM reservations WHERE id = ?");
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
 * @param array $reservation Must include name, position, company, seat_code, qr_code
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

    $fontPathCustom = __DIR__ . '/../templates/fonts/Granville.otf';

    $textColor = imagecolorallocate($image, 20, 20, 20);
    $shadowColor = imagecolorallocatealpha($image, 255, 255, 255, 80);

    $name = $reservation['name'];
    $position = $reservation['position'];
    $company = $reservation['company'];
    $seatTitle = 'Seat';
    $seatCode = $reservation['seat_code'];
    $tableColor = $reservation['table_color'] ?? 'White';
    $qrData = $reservation['qr_code'];

    // Vertical layout: name -> QR -> table
    $nameY = (int) ($height * 0.21);
    $positionY = (int) ($height * 0.245);
    $companyY = (int) ($height * 0.28);
    $qrGapBottom = (int) ($height * 0.03);
    $seatTitleY = null;
    $seatY = null;

    $useSmallerFont = strlen($name) > 30 || strlen($position) > 30 || strlen($company) > 30;
    $nameFontSize = $useSmallerFont ? 40 : 55;
    $positionFontSize = $useSmallerFont ? 35 : 45;
    $companyFontSize = $useSmallerFont ? 35 : 45;

    drawCenteredGdText($image, $fontPathCustom, $nameFontSize, $nameY, ucwords($name), $textColor, false, true);

    // Draw position if available
    drawCenteredGdText($image, $fontPathCustom, $positionFontSize, $positionY, ucwords($position), $textColor, false, true);

    // Draw company if available
    drawCenteredGdText($image, $fontPathCustom, $companyFontSize, $companyY, ucwords($company), $textColor, false, true);

    // Add QR code (uses reservation.qr_code value)
    if (!empty($qrData)) {
        $qrImage = buildQrImage($qrData, 500);

        if ($qrImage) {
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
            $qrX = (int) (($width - $qrWidth) / 2);
            $qrY = (int) ($height * 0.32);

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

            // Set seat Y relative to QR bottom
            $seatTitleY = $qrY + $qrHeight + $qrGapBottom;
            $seatY = $qrY + $qrHeight + $qrGapBottom + (int) ($height * 0.08);
        } else {
            error_log("Failed to generate QR code for reservation ID: " . $reservation['qr_code']);
        }
    }

    // Draw seat text after QR placement
    if ($seatY === null) {
        // Fallback if no QR: place below name with a gap
        $seatY = $nameY + (int) ($height * 0.12);
    }

    drawCenteredGdText($image, $fontPathCustom, 35, $seatTitleY, ucwords($seatTitle), $textColor, true);

    drawCenteredGdTextWithBackground($image, $fontPathCustom, 140, $seatY, ucwords($seatCode), $textColor, getTableColorRgb($tableColor), false, true);

    return $image;
}

function getTableColorRgb($colorName)
{
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $colorName)) {
        $hex = ltrim($colorName, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    return [255, 255, 255];
}

function drawCenteredGdTextWithBackground($image, $fontPath, $fontSize, $y, $text, $textColor, $bgColorRgb, $isItalic = false, $isBold = false)
{
    if ($isBold) {
        $fontPath = __DIR__ . '/../templates/fonts/Granville_Bold.otf';
    } elseif ($isItalic) {
        $fontPath = __DIR__ . '/../templates/fonts/Granville_Italic.otf';
    } else {
        $fontPath = __DIR__ . '/../templates/fonts/Granville.otf';
    }

    $canUseTtf = function_exists('imagettftext') && file_exists($fontPath);

    $imageWidth = imagesx($image);

    if ($canUseTtf) {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = (int) (($imageWidth - $textWidth) / 2);
        $widthBg = 360;
        
        $padding = 20;
        $radius = 15;
        $bgX1 = (($imageWidth / 2) - $widthBg);
        $bgY1 = $y - $textHeight - $padding;
        $bgX2 = (($imageWidth / 2) + $widthBg);
        $bgY2 = $y + $padding;
        
        $bgColor = imagecolorallocate($image, $bgColorRgb[0], $bgColorRgb[1], $bgColorRgb[2]);
        
        imagefilledrectangle($image, $bgX1 + $radius, $bgY1, $bgX2 - $radius, $bgY2, $bgColor);
        imagefilledrectangle($image, $bgX1, $bgY1 + $radius, $bgX2, $bgY2 - $radius, $bgColor);
        
        imagefilledarc($image, $bgX1 + $radius, $bgY1 + $radius, $radius * 2, $radius * 2, 180, 270, $bgColor, IMG_ARC_PIE);
        imagefilledarc($image, $bgX2 - $radius, $bgY1 + $radius, $radius * 2, $radius * 2, 270, 360, $bgColor, IMG_ARC_PIE);
        imagefilledarc($image, $bgX1 + $radius, $bgY2 - $radius, $radius * 2, $radius * 2, 90, 180, $bgColor, IMG_ARC_PIE);
        imagefilledarc($image, $bgX2 - $radius, $bgY2 - $radius, $radius * 2, $radius * 2, 0, 90, $bgColor, IMG_ARC_PIE);
        
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);
    }
}

/**
 * Draw centered GD text fallback (no TTF)
 */
function drawCenteredGdText($image, $fontPath, $fontSize, $y, $text, $color, $isItalic = false, $isBold = false)
{
    if ($isBold) {
    $fontPath = __DIR__ . '/../templates/fonts/Granville_Bold.otf';
} elseif ($isItalic) {
    $fontPath = __DIR__ . '/../templates/fonts/Granville_Italic.otf';
} else {
    $fontPath = __DIR__ . '/../templates/fonts/Granville.otf';
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
