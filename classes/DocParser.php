<?php
// classes/DocParser.php
require_once __DIR__ . '/DocxParser.php';

class DocParser {
    public static function parse($filePath) {
        // Check if the file is actually a zip (docx renamed to doc)
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            $zip->close();
            return DocxParser::parse($filePath);
        }
        
        // Binary .doc parser fallback
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }
        
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            return [];
        }
        
        $fileHandle = fopen($filePath, 'rb');
        if (!$fileHandle) {
            return [];
        }
        
        $rawContent = fread($fileHandle, $fileSize);
        fclose($fileHandle);
        
        // In binary doc formats, text is often stored in UTF-16LE or ASCII/ANSI.
        // We will try both and extract sequences of readable text.
        
        // 1. Try to extract UTF-16LE characters (common in newer binary doc formats)
        $utf8Content = @iconv('UTF-16LE', 'UTF-8//IGNORE', $rawContent);
        $paragraphs = [];
        
        if ($utf8Content) {
            // Find sequences of readable English, Arabic, and punctuation characters
            // Unicode ranges: 
            // - Basic Latin: \x{0020}-\x{007E}
            // - Arabic: \x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}
            preg_match_all('/[\x20-\x7E\r\n\t\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]{4,}/u', $utf8Content, $matches);
            $extractedText = implode("\n", $matches[0]);
        } else {
            $extractedText = '';
        }
        
        // If UTF-16LE didn't yield much, fallback to plain ASCII/ANSI extraction
        if (strlen($extractedText) < 50) {
            preg_match_all('/[\x20-\x7E\r\n\t]{4,}/', $rawContent, $matches);
            $extractedText = implode("\n", $matches[0]);
        }
        
        // Split text by lines and clean
        $lines = explode("\n", $extractedText);
        $currentParagraph = "";
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Filter out obvious binary garbage strings (like MSWordDoc, Word.Document, formatting commands)
            if (empty($line) || 
                stripos($line, 'Microsoft Word') !== false || 
                stripos($line, 'MSWordDoc') !== false || 
                stripos($line, 'Word.Document') !== false ||
                preg_match('/^[a-zA-Z0-9_\-\.\s]{1,3}$/', $line)) {
                continue;
            }
            
            // Accumulate sentences into paragraph blocks
            $currentParagraph .= " " . $line;
            
            if (strlen($currentParagraph) > 350 || preg_match('/[\.\?\!]$/', $line)) {
                $cleanedPara = trim(preg_replace('/\s+/', ' ', $currentParagraph));
                if (strlen($cleanedPara) > 10) {
                    $paragraphs[] = $cleanedPara;
                }
                $currentParagraph = "";
            }
        }
        
        if (!empty($currentParagraph)) {
            $cleanedPara = trim(preg_replace('/\s+/', ' ', $currentParagraph));
            if (strlen($cleanedPara) > 10) {
                $paragraphs[] = $cleanedPara;
            }
        }
        
        return $paragraphs;
    }
}
