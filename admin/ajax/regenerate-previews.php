<?php
require_once __DIR__ . '/../../db.php';

$uploadDir = __DIR__ . '/../templates/files/';
$previewDir = __DIR__ . '/../templates/previews/';

$libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
$gsPath = 'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe';

$templates = $conn->query("SELECT * FROM templates");
$results = [];

while ($template = $templates->fetch_assoc()) {
    $docxPath = $uploadDir . $template['file_path'];
    $previewName = pathinfo($template['file_path'], PATHINFO_FILENAME) . '.jpg';
    $previewPath = $previewDir . $previewName;
    $pdfPath = $uploadDir . pathinfo($template['file_path'], PATHINFO_FILENAME) . '.pdf';
    
    if (!file_exists($docxPath)) {
        $results[] = ['name' => $template['name'], 'preview' => '❌ DOCX not found'];
        continue;
    }
    
    // Step 1: DOCX → PDF (LibreOffice)
    $cmd = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . $uploadDir . '" "' . $docxPath . '" 2>&1';
    exec($cmd, $out, $ret);
    sleep(1);
    
    if (!file_exists($pdfPath)) {
        $results[] = ['name' => $template['name'], 'preview' => '❌ PDF failed'];
        continue;
    }
    
    // Step 2: PDF → JPG (Ghostscript)
    $gsCmd = '"' . $gsPath . '" -dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -r150 -dUseCropBox -sOutputFile="' . $previewPath . '" "' . $pdfPath . '" 2>&1';
    exec($gsCmd, $gsOut, $gsRet);
    
    // Clean up PDF
    if (file_exists($pdfPath)) unlink($pdfPath);
    
    if (file_exists($previewPath) && filesize($previewPath) > 500) {
        $results[] = ['name' => $template['name'], 'preview' => '✅ Generated'];
    } else {
        $results[] = ['name' => $template['name'], 'preview' => '❌ JPG failed'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Regenerate Previews</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #030045; color: white; padding: 40px; max-width: 800px; margin: 0 auto; }
        h1 { color: #00b4d8; }
        .result { padding: 10px 14px; margin: 5px 0; border-radius: 8px; font-family: monospace; }
        .success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); }
        .fail { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); }
        .btn { display: inline-block; padding: 10px 20px; background: #00b4d8; color: #030045; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px; }
        img { max-width: 400px; border-radius: 8px; border: 2px solid rgba(0,180,216,0.3); margin: 10px 0; }
    </style>
</head>
<body>
    <h1>📄 Preview Regeneration</h1>
    <p>Using LibreOffice + Ghostscript 10.07.0</p>
    
    <?php foreach ($results as $r): ?>
        <div class="result <?php echo strpos($r['preview'], '✅') !== false ? 'success' : 'fail'; ?>">
            <?php echo htmlspecialchars($r['name']); ?> — <?php echo $r['preview']; ?>
        </div>
    <?php endforeach; ?>
    
    <?php
    $previewFiles = glob($previewDir . '*.jpg');
    if (!empty($previewFiles)):
    ?>
        <h2 style="color: #90e0ef; margin-top: 25px;">Preview:</h2>
        <img src="../templates/previews/<?php echo basename($previewFiles[0]); ?>?v=<?php echo time(); ?>" alt="Preview">
    <?php endif; ?>
    
    <p><a href="../templates.php" class="btn">← Back to Templates</a></p>
</body>
</html>