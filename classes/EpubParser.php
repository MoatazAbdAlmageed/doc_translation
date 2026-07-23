<?php
// classes/EpubParser.php

class EpubParser {
    public static function parse($filePath) {
        $paragraphs = [];
        $zip = new ZipArchive();
        
        if ($zip->open($filePath) === TRUE) {
            // 1. Read container.xml to locate OPF file
            $containerXml = $zip->getFromName('META-INF/container.xml');
            if (!$containerXml) {
                $zip->close();
                return [];
            }
            
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadXML($containerXml);
            libxml_clear_errors();
            
            $rootfiles = $dom->getElementsByTagName('rootfile');
            if ($rootfiles->length === 0) {
                $zip->close();
                return [];
            }
            
            $opfPath = $rootfiles->item(0)->getAttribute('full-path');
            $opfDir = dirname($opfPath);
            if ($opfDir === '.' || $opfDir === '/') {
                $opfDir = '';
            } else {
                $opfDir = rtrim($opfDir, '/') . '/';
            }
            
            // 2. Read OPF file
            $opfXml = $zip->getFromName($opfPath);
            if (!$opfXml) {
                $zip->close();
                return [];
            }
            
            $domOpf = new DOMDocument();
            libxml_use_internal_errors(true);
            $domOpf->loadXML($opfXml);
            libxml_clear_errors();
            
            // Get manifest items
            $manifestItems = [];
            $items = $domOpf->getElementsByTagName('item');
            foreach ($items as $item) {
                $id = $item->getAttribute('id');
                $href = $item->getAttribute('href');
                $mediaType = $item->getAttribute('media-type');
                $manifestItems[$id] = [
                    'href' => $href,
                    'media-type' => $mediaType
                ];
            }
            
            // Get spine items in reading order
            $spineRefs = [];
            $itemrefs = $domOpf->getElementsByTagName('itemref');
            foreach ($itemrefs as $itemref) {
                $idref = $itemref->getAttribute('idref');
                if (isset($manifestItems[$idref])) {
                    $spineRefs[] = $manifestItems[$idref]['href'];
                }
            }
            
            // 3. Process HTML documents in spine order
            foreach ($spineRefs as $href) {
                $hrefDecoded = urldecode($href);
                $fullHref = $opfDir . $hrefDecoded;
                $fullHref = self::cleanZipPath($fullHref);
                
                $htmlContent = $zip->getFromName($fullHref);
                if ($htmlContent === false) {
                    continue;
                }
                
                $domHtml = new DOMDocument();
                libxml_use_internal_errors(true);
                // Load HTML, forcing UTF-8
                $domHtml->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                
                $body = $domHtml->getElementsByTagName('body')->item(0);
                if (!$body) {
                    $body = $domHtml;
                }
                
                $nodes = self::getReadableNodes($body);
                foreach ($nodes as $nodeText) {
                    $nodeText = trim($nodeText);
                    if (!empty($nodeText)) {
                        $paragraphs[] = $nodeText;
                    }
                }
            }
            $zip->close();
        }
        return $paragraphs;
    }
    
    private static function cleanZipPath($path) {
        $parts = explode('/', $path);
        $safe = [];
        foreach ($parts as $part) {
            if ($part === '.' || $part === '') {
                continue;
            }
            if ($part === '..') {
                array_pop($safe);
            } else {
                $safe[] = $part;
            }
        }
        return implode('/', $safe);
    }
    
    private static function getReadableNodes($element) {
        $texts = [];
        $textTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote', 'div'];
        self::traverseNodes($element, $textTags, $texts);
        return $texts;
    }
    
    private static function traverseNodes($node, $textTags, &$texts) {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = strtolower($node->tagName);
            
            // Avoid scripting, styling, and navigation contents
            if (in_array($tagName, ['script', 'style', 'nav', 'noscript'])) {
                return;
            }
            
            // If it's a paragraph or header, extract text content directly without diving deeper
            if (in_array($tagName, $textTags)) {
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                // Only extract non-trivial content
                if (!empty($text) && strlen($text) > 3) {
                    $texts[] = $text;
                    return; // Prevent traversing children to avoid duplicating text
                }
            }
        }
        
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                self::traverseNodes($child, $textTags, $texts);
            }
        }
    }
}
