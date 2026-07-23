<?php
// translate_api.php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Check request parameters
$documentId = isset($_REQUEST['document_id']) ? (int)$_REQUEST['document_id'] : 0;
$paragraphIndex = isset($_REQUEST['paragraph_index']) ? (int)$_REQUEST['paragraph_index'] : -1;
$engine = isset($_REQUEST['engine']) ? $_REQUEST['engine'] : 'google';
$geminiKey = isset($_REQUEST['gemini_key']) ? trim($_REQUEST['gemini_key']) : '';

if ($documentId <= 0 || $paragraphIndex < 0) {
    if (isset($_REQUEST['q'])) {
        $qText = trim($_REQUEST['q']);
        $engine = isset($_REQUEST['engine']) ? $_REQUEST['engine'] : 'google';
        $geminiKey = isset($_REQUEST['gemini_key']) ? trim($_REQUEST['gemini_key']) : '';
        try {
            if ($engine === 'gemini' && !empty($geminiKey)) {
                $translated = translateGemini($qText, $geminiKey);
            } else {
                $translated = translateGoogle($qText);
            }
            echo json_encode([
                'success' => true,
                'text_en' => $qText,
                'text_ar' => $translated
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Invalid parameters. document_id and paragraph_index are required.']);
    exit;
}

try {
    // 1. Fetch paragraph from database
    $stmt = $pdo->prepare("SELECT * FROM paragraphs WHERE document_id = ? AND paragraph_index = ?");
    $stmt->execute([$documentId, $paragraphIndex]);
    $paragraph = $stmt->fetch();

    if (!$paragraph) {
        echo json_encode(['success' => false, 'error' => 'Paragraph not found.']);
        exit;
    }

    // 2. Return cached translation if already translated
    if (!empty($paragraph['text_ar']) && $paragraph['status'] === 'translated') {
        echo json_encode([
            'success' => true,
            'text_en' => $paragraph['text_en'],
            'text_ar' => $paragraph['text_ar'],
            'cached' => true
        ]);
        exit;
    }

    $textToTranslate = $paragraph['text_en'];
    $translatedText = null;

    // 3. Mark paragraph as translating
    $stmtUpdateStatus = $pdo->prepare("UPDATE paragraphs SET status = 'translating' WHERE id = ?");
    $stmtUpdateStatus->execute([$paragraph['id']]);

    // 4. Perform translation
    if ($engine === 'gemini') {
        if (empty($geminiKey)) {
            throw new Exception("Gemini API key is required for Ultra translation mode.");
        }
        $translatedText = translateGemini($textToTranslate, $geminiKey);
    } else {
        $translatedText = translateGoogle($textToTranslate);
    }

    if (empty($translatedText)) {
        throw new Exception("Translation engine returned an empty result.");
    }

    // 5. Update paragraph with successful translation
    $stmtUpdateSuccess = $pdo->prepare("UPDATE paragraphs SET text_ar = ?, status = 'translated', error_message = NULL WHERE id = ?");
    $stmtUpdateSuccess->execute([$translatedText, $paragraph['id']]);

    echo json_encode([
        'success' => true,
        'text_en' => $textToTranslate,
        'text_ar' => $translatedText,
        'cached' => false
    ]);

} catch (Exception $e) {
    // Log failure in database
    if (isset($paragraph['id'])) {
        $stmtUpdateFailure = $pdo->prepare("UPDATE paragraphs SET status = 'failed', error_message = ? WHERE id = ?");
        $stmtUpdateFailure->execute([$e->getMessage(), $paragraph['id']]);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Call Google Translate (unofficial gtx endpoint)
 */
function translateGoogle($text) {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // compatibility for local environments
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $translated = "";
            foreach ($data[0] as $segment) {
                if (is_array($segment) && isset($segment[0])) {
                    $translated .= $segment[0];
                }
            }
            return trim($translated);
        }
    }
    return null;
}

/**
 * Call Google Gemini API
 */
function translateGemini($text, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    
    // Explicit prompt to get high-quality human-like Arabic translation
    $prompt = "You are a professional, bilingual translator specializing in translating English literature and documents into beautiful, natural, and fluent Arabic. " .
              "Translate the English text below into Arabic. Ensure the translation matches the tone of the original, respects cultural context, and flows like it was written by a human translator. " .
              "Do NOT include any notes, explanations, introductory words, or markdown format blocks. Just return the pure Arabic text.\n\n" .
              "Text to translate:\n" . $text;
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($httpCode === 200) {
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
        } else {
            if (isset($data['error']['message'])) {
                throw new Exception("Gemini API Error: " . $data['error']['message']);
            }
        }
    }
    
    throw new Exception("Connection to Gemini API failed (HTTP $httpCode). Please verify your network and API Key.");
}
