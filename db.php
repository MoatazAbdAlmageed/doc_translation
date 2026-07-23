<?php
// db.php
// SQLite Database connection and initialization

$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        filename TEXT NOT NULL,
        file_size INTEGER NOT NULL,
        file_type TEXT NOT NULL,
        paragraph_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS paragraphs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        paragraph_index INTEGER NOT NULL,
        text_en TEXT NOT NULL,
        text_ar TEXT DEFAULT NULL,
        status TEXT DEFAULT 'pending', -- pending, translating, translated, failed
        error_message TEXT DEFAULT NULL,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    )");

    // Index for fast lookup
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_paragraphs_doc_index ON paragraphs(document_id, paragraph_index)");

} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage());
}
