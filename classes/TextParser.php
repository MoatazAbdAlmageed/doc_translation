<?php
// classes/TextParser.php

class TextParser {
    public static function parse($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }
        
        // Detect encoding and convert to UTF-8 if needed
        $encoding = mb_detect_encoding($content, 'UTF-8, ISO-8859-1, Windows-1256, ASCII', true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Split by empty lines (one or more newlines with optional spaces)
        $lines = preg_split('/\r\n\r\n|\n\n/', $content);
        $paragraphs = [];
        foreach ($lines as $line) {
            $cleaned = trim(preg_replace('/\s+/', ' ', $line));
            if (!empty($cleaned)) {
                $paragraphs[] = $cleaned;
            }
        }
        return $paragraphs;
    }
}
