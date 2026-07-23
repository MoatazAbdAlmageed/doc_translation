<?php
// index.php
require_once __DIR__ . '/db.php';

// Fetch all documents from the database
try {
    $stmt = $pdo->query("SELECT * FROM documents ORDER BY created_at DESC");
    $documents = $stmt->fetchAll();
} catch (Exception $e) {
    $documents = [];
    $dbError = $e->getMessage();
}

// Function to format file size
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransRead AI - Dual Language English & Arabic Reader</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fa-solid fa-language logo-icon"></i>
                <div class="logo-text">TransRead <span style="font-weight: 400; font-size: 1.1rem; color: var(--text-secondary);">AI</span></div>
            </div>
            <div class="header-actions">
                <span id="apiKeyStatus" class="api-badge api-badge-inactive">
                    <i class="fa-solid fa-key"></i> Gemini AI Inactive
                </span>
            </div>
        </header>

        <!-- Main Grid Layout -->
        <div class="dashboard-grid">
            
            <!-- Left Side: Upload Zone and Document History -->
            <div class="dashboard-main">
                
                <!-- Upload Zone -->
                <div class="glass-panel" style="margin-bottom: 2rem;">
                    <h2 class="section-title"><i class="fa-solid fa-file-arrow-up"></i> Upload Document</h2>
                    
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" id="fileInput" name="document" accept=".txt,.doc,.docx,.epub,.pdf" style="display: none;">
                            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            <div class="upload-text">Drag & drop your document here, or <span style="color: var(--primary); text-decoration: underline;">browse</span></div>
                            <div class="upload-formats">Supports PDF, EPUB, DOCX, DOC, and TXT files (Max 20MB)</div>
                        </div>
                    </form>
                    
                    <!-- Progress / Status Indicator -->
                    <div id="uploadStatus" style="display: none; margin-top: 1.5rem;">
                        <div class="alert-banner alert-info" id="statusMessage">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                            <span>Uploading and parsing document...</span>
                        </div>
                    </div>
                </div>

                <!-- Document Library -->
                <div class="glass-panel">
                    <h2 class="section-title"><i class="fa-solid fa-book-open"></i> My Library</h2>
                    
                    <?php if (isset($dbError)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-circle-exclamation empty-state-icon" style="color: #f87171;"></i>
                            <p>Database Error: <?php echo htmlspecialchars($dbError); ?></p>
                        </div>
                    <?php elseif (empty($documents)): ?>
                        <div class="empty-state" id="emptyState">
                            <i class="fa-solid fa-folder-open empty-state-icon"></i>
                            <p style="font-weight: 500;">No documents uploaded yet</p>
                            <p style="font-size: 0.85rem; color: var(--text-muted);">Upload a document above to begin your dual-language reading journey.</p>
                        </div>
                    <?php endif; ?>

                    <div class="history-list" id="documentLibraryList">
                        <?php foreach ($documents as $doc): ?>
                            <div class="history-item" data-id="<?php echo $doc['id']; ?>">
                                <div class="history-details">
                                    <div class="doc-type-badge doc-type-<?php echo $doc['file_type']; ?>">
                                        <?php echo $doc['file_type']; ?>
                                    </div>
                                    <div class="doc-info">
                                        <div class="doc-title" title="<?php echo htmlspecialchars($doc['title']); ?>">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </div>
                                        <div class="doc-meta">
                                            <span><i class="fa-solid fa-paragraph"></i> <?php echo $doc['paragraph_count']; ?> paras</span>
                                            <span><i class="fa-solid fa-hard-drive"></i> <?php echo formatBytes($doc['file_size']); ?></span>
                                            <span><i class="fa-solid fa-calendar-day"></i> <?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="history-actions">
                                    <a href="reader.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                        <i class="fa-solid fa-book-open-reader"></i> Read
                                    </a>
                                    <button class="btn btn-danger btn-sm delete-doc-btn" data-id="<?php echo $doc['id']; ?>" style="padding: 0.5rem; width: 2rem; height: 2rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Right Side: Settings & Configuration -->
            <div class="dashboard-sidebar">
                
                <!-- Settings Panel -->
                <div class="glass-panel">
                    <h2 class="section-title"><i class="fa-solid fa-sliders"></i> Translation Settings</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Default Translation Quality</label>
                        <div class="radio-group">
                            <div class="radio-card active" id="engineGoogleCard">
                                <input type="radio" id="engineGoogle" name="translation_engine" value="google" checked>
                                <span class="radio-title">Standard</span>
                                <span class="radio-desc">Google Translate (Fast, Free)</span>
                            </div>
                            <div class="radio-card" id="engineGeminiCard">
                                <input type="radio" id="engineGemini" name="translation_engine" value="gemini">
                                <span class="radio-title">Ultra AI</span>
                                <span class="radio-desc">Gemini AI (Human-like, Natural)</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="geminiKeyGroup" style="display: none; animation: fadeIn 0.3s ease;">
                        <label class="form-label" for="geminiApiKey">Gemini API Key</label>
                        <div style="position: relative;">
                            <input type="password" id="geminiApiKey" class="form-input" placeholder="AIzaSy...">
                            <button type="button" id="togglePasswordVisibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; line-height: 1.4;">
                            Get a free Gemini API Key from <a href="https://aistudio.google.com/" target="_blank" style="color: var(--primary); text-decoration: underline;">Google AI Studio</a> to unlock high-quality context-aware translation.
                        </p>
                        
                        <!-- Test Translation Area -->
                        <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.05);">
                            <label class="form-label" style="font-size: 0.85rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-flask"></i> Test API Key</label>
                            <textarea id="testParagraph" class="form-input" style="min-height: 60px; font-size: 0.85rem; margin-bottom: 0.75rem;" placeholder="Enter a short English paragraph here to test the API key..."></textarea>
                            <button id="testApiBtn" type="button" class="btn btn-secondary btn-sm" style="width: 100%;">
                                <i class="fa-solid fa-vial"></i> Test Translation
                            </button>
                            <div id="testResult" style="margin-top: 0.75rem; font-size: 0.85rem; display: none;"></div>
                        </div>
                    </div>

                    <button id="saveSettingsBtn" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Settings
                    </button>
                </div>

                <!-- Learning English Tips Card -->
                <div class="glass-panel" style="margin-top: 2rem; background: linear-gradient(135deg, rgba(22, 31, 52, 0.6) 0%, rgba(99, 102, 241, 0.05) 100%);">
                    <h2 class="section-title" style="border-left-color: var(--secondary);"><i class="fa-solid fa-graduation-cap"></i> Reader Tips</h2>
                    <ul style="padding-left: 1.25rem; font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.75rem;">
                        <li><strong>Split Screen:</strong> Read English on the left, and check the translation on the right whenever you get stuck.</li>
                        <li><strong>Click to Define:</strong> Double-click or click on any English word in the reading page to pull up definitions, parts of speech, and instant Arabic translations.</li>
                        <li><strong>Text-to-Speech:</strong> Click the audio button next to a paragraph to hear the correct native pronunciation of the English text.</li>
                        <li><strong>Cache:</strong> Translations are saved locally. Re-reading a document will load instantly without any extra API consumption!</li>
                    </ul>
                </div>

            </div>

        </div>
    </div>

    <!-- App JS -->
    <script src="js/app.js"></script>
</body>
</html>
