<?php
// upload.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/TextParser.php';
require_once __DIR__ . '/classes/DocParser.php';
require_once __DIR__ . '/classes/DocxParser.php';
require_once __DIR__ . '/classes/EpubParser.php';
require_once __DIR__ . '/classes/PdfParser.php';
require_once __DIR__ . '/classes/MdParser.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $errCode = isset($_FILES['document']['error']) ? $_FILES['document']['error'] : 'Unknown';
    echo json_encode(['success' => false, 'error' => "File upload error. Code: $errCode"]);
    exit;
}

$file = $_FILES['document'];
$originalName = $file['name'];
$tmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Supported extensions
$allowedExtensions = ['txt', 'doc', 'docx', 'epub', 'pdf', 'md'];
if (!in_array($ext, $allowedExtensions)) {
    echo json_encode(['success' => false, 'error' => 'Unsupported file format. Please upload .txt, .doc, .docx, .epub, .pdf, or .md.']);
    exit;
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate secure file name
$newFilename = uniqid('doc_', true) . '.' . $ext;
$destination = $uploadDir . '/' . $newFilename;

if (!move_uploaded_file($tmpPath, $destination)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
    exit;
}

// Extract paragraphs based on extension
$paragraphs = [];
try {
    switch ($ext) {
        case 'txt':
            $paragraphs = TextParser::parse($destination);
            break;
        case 'doc':
            $paragraphs = DocParser::parse($destination);
            break;
        case 'docx':
            $paragraphs = DocxParser::parse($destination);
            break;
        case 'epub':
            $paragraphs = EpubParser::parse($destination);
            break;
        case 'pdf':
            $paragraphs = PdfParser::parse($destination);
            break;
        case 'md':
            $paragraphs = MdParser::parse($destination);
            break;
    }
} catch (Exception $e) {
    // Delete file if parsing failed
    @unlink($destination);
    echo json_encode(['success' => false, 'error' => 'Error parsing document: ' . $e->getMessage()]);
    exit;
}

$paragraphCount = count($paragraphs);
if ($paragraphCount === 0) {
    @unlink($destination);
    echo json_encode(['success' => false, 'error' => 'Could not extract any paragraphs from this document. Please ensure it contains readable text.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert document record
    $stmt = $pdo->prepare("INSERT INTO documents (title, filename, file_size, file_type, paragraph_count) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $originalName,
        $newFilename,
        $fileSize,
        $ext,
        $paragraphCount
    ]);
    
    $documentId = $pdo->lastInsertId();

    // Insert paragraphs in batch
    $stmtPara = $pdo->prepare("INSERT INTO paragraphs (document_id, paragraph_index, text_en) VALUES (?, ?, ?)");
    foreach ($paragraphs as $index => $text) {
        $stmtPara->execute([
            $documentId,
            $index,
            $text
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'document_id' => $documentId,
        'title' => $originalName,
        'paragraphs_count' => $paragraphCount
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    @unlink($destination);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
