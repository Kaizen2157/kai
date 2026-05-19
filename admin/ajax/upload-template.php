<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../db.php';

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$type = trim($_POST['type'] ?? 'minimal');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Template name is required']);
    exit();
}

if (empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Category is required']);
    exit();
}

if (!in_array($type, ['minimal', 'creative', 'corporate', 'modern'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid template type']);
    exit();
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit();
}

$file = $_FILES['file'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'docx') {
    echo json_encode(['success' => false, 'message' => 'Only .docx files are allowed']);
    exit();
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
    exit();
}

$uploadDir = __DIR__ . '/../templates/files/';
$previewDir = __DIR__ . '/../templates/previews/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0755, true);
}

$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
$safeName = $safeName ?: 'template';
$uniqueName = time() . '_' . $safeName . '.docx';
$filePath = $uploadDir . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

// Generate preview
$previewName = pathinfo($uniqueName, PATHINFO_FILENAME) . '.jpg';
$previewPath = $previewDir . $previewName;
$previewGenerated = generateDocxPreview($filePath, $previewPath, $name);

$stmt = $conn->prepare("INSERT INTO templates (name, category, type, file_path, file_size, status, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())");
$stmt->bind_param("ssssii", $name, $category, $type, $uniqueName, $file['size'], $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Template uploaded successfully!' . ($previewGenerated ? '' : ' (Preview not generated)'),
        'id' => $conn->insert_id
    ]);
} else {
    unlink($filePath);
    if ($previewGenerated) unlink($previewPath);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

function generateDocxPreview($docxPath, $previewPath, $templateName) {
    // Try LibreOffice conversion first (best quality)
    $libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
    
    if (file_exists($libreOfficePath)) {
        return convertWithLibreOffice($docxPath, $previewPath, $libreOfficePath);
    }
    
    // Fallback to GD-based preview
    if (extension_loaded('gd')) {
        $textContent = extractTextFromDocx($docxPath);
        return createPreviewImage($previewPath, $templateName, $textContent);
    }
    
    return false;
}

function convertWithLibreOffice($docxPath, $previewPath, $libreOfficePath) {
    $docxDir = dirname($docxPath);
    $pdfPath = $docxDir . '\\' . pathinfo(basename($docxPath), PATHINFO_FILENAME) . '.pdf';
    
    // Convert DOCX to PDF
    $command = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . $docxDir . '" "' . $docxPath . '"';
    exec($command . ' 2>&1', $output, $returnCode);
    sleep(1);
    
    if (!file_exists($pdfPath)) {
        error_log('PDF not created: ' . $pdfPath);
        return false;
    }
    
    // Try Ghostscript to convert PDF to JPG
    $gsPath = 'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe';
    if (file_exists($gsPath)) {
        $gsCommand = '"' . $gsPath . '" -dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -r150 -dUseCropBox -sOutputFile="' . $previewPath . '" "' . $pdfPath . '"';
        exec($gsCommand . ' 2>&1', $gsOutput, $gsReturn);
        
        // Clean up PDF
        if (file_exists($pdfPath)) unlink($pdfPath);
        
        if (file_exists($previewPath) && filesize($previewPath) > 500) {
            return true;
        }
    }
    
    // Clean up PDF if JPG failed
    if (file_exists($pdfPath)) unlink($pdfPath);
    
    return false;
}

function extractTextFromDocx($docxPath) {
    $text = '';
    
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            if ($xmlContent) {
                $xmlContent = strip_tags($xmlContent);
                $text = trim(preg_replace('/\s+/', ' ', $xmlContent));
            }
            $zip->close();
        }
    }
    
    if (empty($text)) {
        $text = "Professional Resume Template\n\nSections:\n- Contact Information\n- Professional Summary\n- Work Experience\n- Education\n- Skills\n- Certifications";
    }
    
    return $text;
}

function createPreviewImage($previewPath, $templateName, $textContent) {
    $width = 800;
    $padding = 40;
    $lineHeight = 26;
    $maxCharsPerLine = 65;
    $maxLines = 35;
    
    $wrappedText = wordwrap($textContent, $maxCharsPerLine, "\n", true);
    $lines = explode("\n", $wrappedText);
    $lines = array_slice($lines, 0, $maxLines);
    
    $totalLines = count($lines);
    $height = ($totalLines * $lineHeight) + ($padding * 3) + 100;
    $height = max($height, 600);
    
    $image = @imagecreatetruecolor($width, $height);
    if (!$image) {
        return false;
    }
    
    $bgColor = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 51, 51, 51);
    $lightTextColor = imagecolorallocate($image, 120, 120, 120);
    $headerBgColor = imagecolorallocate($image, 3, 0, 69);
    $accentColor = imagecolorallocate($image, 0, 180, 216);
    $sectionColor = imagecolorallocate($image, 0, 119, 182);
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    imagefilledrectangle($image, 0, 0, $width, 90, $headerBgColor);
    imagefilledrectangle($image, 0, 90, $width, 94, $accentColor);
    
    $displayName = strtoupper($templateName);
    imagestring($image, 5, $padding, 20, $displayName, $whiteColor);
    imagestring($image, 2, $padding, 45, 'Professional Resume Template', imagecolorallocate($image, 180, 210, 240));
    imagestring($image, 1, $padding, 62, 'Created with Resumazing.com', imagecolorallocate($image, 140, 180, 220));
    
    $y = 120;
    $sectionCount = 0;
    
    foreach ($lines as $index => $line) {
        $line = trim($line);
        if (empty($line)) {
            $y += 8;
            continue;
        }
        
        $x = $padding;
        $isHeader = (strlen($line) < 40 && 
                    (strtoupper($line) === $line || 
                     preg_match('/^[A-Z][a-z]+(\s+[A-Z][a-z]+){0,3}$/', $line) ||
                     strpos($line, ':') !== false));
        
        if ($isHeader && $sectionCount < 6) {
            $y += 8;
            imagestring($image, 4, $x, $y, strtoupper(substr($line, 0, 35)), $sectionColor);
            imagefilledrectangle($image, $x, $y + 17, $x + 50, $y + 19, $accentColor);
            $y += $lineHeight + 4;
            $sectionCount++;
        } else {
            $displayText = substr($line, 0, $maxCharsPerLine);
            imagestring($image, 3, $x, $y, $displayText, $textColor);
            $y += $lineHeight;
        }
        
        if ($y > $height - 40) break;
    }
    
    $footerY = $height - 35;
    imagestring($image, 1, $padding, $footerY, 'Resumazing - Free Resume Templates', $lightTextColor);
    
    $result = @imagejpeg($image, $previewPath, 85);
    imagedestroy($image);
    
    return $result;
}