<?php
// classes/PdfParser.php

require_once __DIR__ . '/../vendor/autoload.php';

class PdfParser {
    public static function parse($filePath) {
        $paragraphs = [];
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (empty($text)) {
                return [];
            }
            
            // Normalize line endings
            $text = str_replace("\r\n", "\n", $text);
            $text = str_replace("\r", "\n", $text);
            
            // Standard PDFs will separate paragraphs by double newlines,
            // but sometimes they use single newlines for normal wraps.
            // Split by multiple newlines first.
            $rawParagraphs = preg_split('/\n{2,}/', $text);
            
            foreach ($rawParagraphs as $rawPara) {
                // Remove inside single newlines that act as word wraps in PDFs
                // and compress duplicate spaces
                $cleaned = preg_replace('/\s+/', ' ', $rawPara);
                $cleaned = trim($cleaned);
                
                // Avoid tiny snippets like page numbers or headers
                if (!empty($cleaned) && strlen($cleaned) > 10) {
                    $paragraphs[] = $cleaned;
                }
            }
        } catch (Exception $e) {
            error_log("PDF parsing error: " . $e->getMessage());
        }
        return $paragraphs;
    }
}
