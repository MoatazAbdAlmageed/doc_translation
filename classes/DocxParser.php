<?php
// classes/DocxParser.php

class DocxParser {
    public static function parse($filePath) {
        $paragraphs = [];
        $zip = new ZipArchive();
        
        if ($zip->open($filePath) === TRUE) {
            $xmlFilename = 'word/document.xml';
            $xmlContent = $zip->getFromName($xmlFilename);
            
            if ($xmlContent !== false) {
                $dom = new DOMDocument();
                // Disable loading external entities and ignore errors for unclean XML
                libxml_use_internal_errors(true);
                $dom->loadXML($xmlContent);
                libxml_clear_errors();
                
                // Paragraph elements are <w:p>
                $pElements = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                if ($pElements->length === 0) {
                    // Try without namespace
                    $pElements = $dom->getElementsByTagName('p');
                }
                
                foreach ($pElements as $pElement) {
                    $text = '';
                    // Within paragraph, runs <w:r> contain text <w:t>
                    $tElements = $pElement->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
                    if ($tElements->length === 0) {
                        $tElements = $pElement->getElementsByTagName('t');
                    }
                    
                    foreach ($tElements as $tElement) {
                        $text .= $tElement->nodeValue;
                    }
                    
                    $text = trim($text);
                    if (!empty($text)) {
                        $paragraphs[] = $text;
                    }
                }
            }
            $zip->close();
        }
        return $paragraphs;
    }
}
