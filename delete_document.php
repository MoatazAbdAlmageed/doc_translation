<?php
// delete_document.php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

if ($documentId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid document ID.']);
    exit;
}

try {
    // Get filename to delete
    $stmt = $pdo->prepare("SELECT filename FROM documents WHERE id = ?");
    $stmt->execute([$documentId]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        $filePath = __DIR__ . '/uploads/' . $doc['filename'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        // Delete from database (foreign key cascade deletes paragraphs if SQLite cascade is set, 
        // but let's delete them manually too just in case foreign keys are not enabled by default)
        $pdo->exec("PRAGMA foreign_keys = ON");
        $stmtDelete = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        $stmtDelete->execute([$documentId]);
        
        // Just in case, clean up paragraphs manually
        $stmtDeleteParas = $pdo->prepare("DELETE FROM paragraphs WHERE document_id = ?");
        $stmtDeleteParas->execute([$documentId]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Document not found.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
