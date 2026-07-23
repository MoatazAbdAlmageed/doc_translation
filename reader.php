<?php
// reader.php
require_once __DIR__ . '/db.php';

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($documentId <= 0) {
    header("Location: index.php");
    exit;
}

try {
    // Fetch document details
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        die("Document not found. <a href='index.php'>Return to Library</a>");
    }

    // Fetch paragraphs
    $stmtPara = $pdo->prepare("SELECT * FROM paragraphs WHERE document_id = ? ORDER BY paragraph_index ASC");
    $stmtPara->execute([$documentId]);
    $paragraphs = $stmtPara->fetchAll();

    // Calculate initial translated counts
    $translatedCount = 0;
    foreach ($paragraphs as $para) {
        if ($para['status'] === 'translated' && !empty($para['text_ar'])) {
            $translatedCount++;
        }
    }
    
    $progressPercent = ($document['paragraph_count'] > 0) ? round(($translatedCount / $document['paragraph_count']) * 100) : 0;

} catch (Exception $e) {
    die("Database error: " . $e->getMessage() . " <a href='index.php'>Return to Library</a>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($document['title']); ?> - TransRead AI Reader</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="max-width: 1300px; padding: 1.5rem 1rem;">
        
        <!-- Reader Navigation & Header -->
        <div class="back-btn-container">
            <a href="index.php" class="btn btn-secondary btn-sm" style="padding: 0.5rem 1rem;">
                <i class="fa-solid fa-arrow-left-long"></i> Back to Library
            </a>
        </div>

        <div class="reader-header">
            <div>
                <h1 class="logo-text" style="font-size: 1.6rem; margin-bottom: 0.25rem;">
                    <?php echo htmlspecialchars($document['title']); ?>
                </h1>
                <div class="reader-stats">
                    <span class="stat-item"><i class="fa-solid fa-folder"></i> Format: <span class="stat-val"><?php echo strtoupper($document['file_type']); ?></span></span>
                    <span class="stat-item"><i class="fa-solid fa-paragraph"></i> Paragraphs: <span class="stat-val"><?php echo $document['paragraph_count']; ?></span></span>
                    <span class="stat-item"><i class="fa-solid fa-language"></i> Translated: <span class="stat-val" id="translatedCountSpan"><?php echo $translatedCount; ?></span>/<span class="stat-val"><?php echo $document['paragraph_count']; ?></span></span>
                </div>
            </div>

            <!-- Toggles and Actions -->
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <!-- Layout Toggle -->
                <div class="view-toggles">
                    <button class="toggle-btn active" id="btnLayoutSide" title="Side-by-Side View"><i class="fa-solid fa-columns"></i> Side-by-Side</button>
                    <button class="toggle-btn" id="btnLayoutStacked" title="Stacked View"><i class="fa-solid fa-table-rows"></i> Stacked</button>
                </div>
                
                <!-- Quick actions -->
                <button class="btn btn-secondary btn-sm" id="btnTranslateAll" title="Translate remaining paragraphs automatically">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--accent);"></i> Translate All
                </button>
                
                <span id="readerApiKeyStatus" class="api-badge api-badge-inactive">
                    <i class="fa-solid fa-key"></i> Google Translate
                </span>
            </div>
        </div>

        <!-- Translation Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar" id="readerProgressBar" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>

        <!-- Main Workspace -->
        <div class="reader-workspace" id="readerWorkspace">
            <?php foreach ($paragraphs as $para): ?>
                <div class="paragraph-block" id="block-<?php echo $para['paragraph_index']; ?>" data-index="<?php echo $para['paragraph_index']; ?>">
                    
                    <!-- Paragraph Floating Toolbar -->
                    <div class="block-actions">
                        <button class="action-icon-btn btn-tts-play" title="Read Aloud" data-index="<?php echo $para['paragraph_index']; ?>">
                            <i class="fa-solid fa-volume-high"></i>
                        </button>
                        <button class="action-icon-btn btn-retranslate" title="Re-translate Paragraph" data-index="<?php echo $para['paragraph_index']; ?>">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>

                    <!-- English Column -->
                    <div class="pane-en" id="en-<?php echo $para['paragraph_index']; ?>"><?php echo htmlspecialchars($para['text_en']); ?></div>
                    
                    <!-- Arabic Column -->
                    <div class="pane-ar" id="ar-<?php echo $para['paragraph_index']; ?>" data-status="<?php echo $para['status']; ?>">
                        <?php if ($para['status'] === 'translated' && !empty($para['text_ar'])): ?>
                            <?php echo htmlspecialchars($para['text_ar']); ?>
                        <?php elseif ($para['status'] === 'translating'): ?>
                            <div class="translation-loading">
                                <span class="spinner"></span>
                                <span>Translating...</span>
                            </div>
                        <?php elseif ($para['status'] === 'failed'): ?>
                            <div class="translation-error">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Translation failed. Click refresh to retry.</span>
                            </div>
                        <?php else: ?>
                            <div class="translation-loading" style="cursor: pointer;" onclick="translateParagraph(<?php echo $documentId; ?>, <?php echo $para['paragraph_index']; ?>)">
                                <i class="fa-solid fa-language" style="font-size: 1.5rem; color: var(--primary);"></i>
                                <span style="text-decoration: underline; color: var(--text-secondary);">Click to translate</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Dictionary Lookup Floating Popup -->
        <div class="dict-popup" id="dictPopup">
            <div class="dict-header">
                <span class="dict-word" id="dictWord">Word</span>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button class="dict-pronounce-btn" id="dictPronounceBtn" title="Listen Pronunciation">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <span style="cursor: pointer; color: var(--text-muted);" onclick="closeDictPopup()"><i class="fa-solid fa-xmark"></i></span>
                </div>
            </div>
            <div class="dict-phonetic" id="dictPhonetic">/phonetic/</div>
            
            <div class="dict-meanings" id="dictMeanings">
                <!-- Meanings load dynamically -->
                <div style="text-align: center; padding: 1rem;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
            </div>
            
            <!-- Quick Arabic translation -->
            <div class="dict-translation" id="dictTranslation">الترجمة العربية...</div>
        </div>

    </div>

    <!-- Inject document ID into JS context -->
    <script>
        const CURRENT_DOCUMENT_ID = <?php echo $documentId; ?>;
        const TOTAL_PARAGRAPHS = <?php echo $document['paragraph_count']; ?>;
    </script>
    <!-- App JS -->
    <script src="js/app.js"></script>
</body>
</html>
