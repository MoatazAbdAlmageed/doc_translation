<?php
// classes/MdParser.php
require_once __DIR__ . '/TextParser.php';

class MdParser {
    public static function parse($filePath) {
        // Markdown is primarily plain text. We can re-use the TextParser 
        // to extract paragraphs.
        return TextParser::parse($filePath);
    }
}
