<?php
// export_epub.php
require_once __DIR__ . '/db.php';

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($documentId <= 0) {
    die("Invalid document ID.");
}

// Fetch document
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$doc = $stmt->fetch();

if (!$doc) {
    die("Document not found.");
}

// Fetch paragraphs
$stmt = $pdo->prepare("SELECT text_en, text_ar, status FROM paragraphs WHERE document_id = ? ORDER BY paragraph_index ASC");
$stmt->execute([$documentId]);
$paragraphs = $stmt->fetchAll();

if (empty($paragraphs)) {
    die("No content to export.");
}

// Generate EPUB (ZIP)
$title = htmlspecialchars($doc['title']);
// Sanitize filename
$filename = "Bilingual_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $doc['title']) . ".epub";

// Use PHP ZipArchive
$tempFile = tempnam(sys_get_temp_dir(), 'epub');
$zip = new ZipArchive();
if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot create EPUB file.");
}

// Add mimetype (should ideally be uncompressed, but standard ZipArchive works for most readers including Kindle Gen)
$zip->addFromString('mimetype', 'application/epub+zip');

// META-INF/container.xml
$containerXml = '<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>';
$zip->addFromString('META-INF/container.xml', $containerXml);

// OEBPS/content.opf
$contentOpf = '<?xml version="1.0" encoding="utf-8"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="uuid_id" version="2.0">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:title>' . $title . ' (Dual Language)</dc:title>
    <dc:language>en</dc:language>
    <dc:language>ar</dc:language>
    <dc:identifier id="uuid_id">transread-' . $documentId . '</dc:identifier>
  </metadata>
  <manifest>
    <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
    <item id="content" href="content.xhtml" media-type="application/xhtml+xml"/>
  </manifest>
  <spine toc="ncx">
    <itemref idref="content"/>
  </spine>
</package>';
$zip->addFromString('OEBPS/content.opf', $contentOpf);

// OEBPS/toc.ncx
$tocNcx = '<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
  <head>
    <meta name="dtb:uid" content="transread-' . $documentId . '"/>
    <meta name="dtb:depth" content="1"/>
    <meta name="dtb:totalPageCount" content="0"/>
    <meta name="dtb:maxPageNumber" content="0"/>
  </head>
  <docTitle>
    <text>' . $title . ' (Dual Language)</text>
  </docTitle>
  <navMap>
    <navPoint id="navPoint-1" playOrder="1">
      <navLabel><text>Start</text></navLabel>
      <content src="content.xhtml"/>
    </navPoint>
  </navMap>
</ncx>';
$zip->addFromString('OEBPS/toc.ncx', $tocNcx);

// OEBPS/content.xhtml
$xhtmlContent = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>' . $title . '</title>
<style type="text/css">
  body { font-family: serif; line-height: 1.6; }
  .para-container { margin-bottom: 2em; border-bottom: 1px solid #ccc; padding-bottom: 1em; }
  .en { font-size: 1.1em; font-weight: normal; margin-bottom: 0.5em; text-align: left; direction: ltr; }
  .ar { font-size: 1.3em; font-family: "Amiri", sans-serif; text-align: right; direction: rtl; color: #333; margin-top: 0.5em; }
  .untranslated { color: #888; font-style: italic; }
</style>
</head>
<body>
<h1 style="text-align:center;">' . $title . '</h1>
<hr/>';

foreach ($paragraphs as $idx => $p) {
    $textEn = htmlspecialchars($p['text_en']);
    $xhtmlContent .= '<div class="para-container">';
    $xhtmlContent .= '<div class="en" dir="ltr">' . $textEn . '</div>';
    
    if ($p['status'] === 'translated' && !empty($p['text_ar'])) {
        $textAr = htmlspecialchars($p['text_ar']);
        $xhtmlContent .= '<div class="ar" dir="rtl">' . $textAr . '</div>';
    } else {
        $xhtmlContent .= '<div class="ar untranslated" dir="rtl">[Translation pending...]</div>';
    }
    $xhtmlContent .= '</div>';
}

$xhtmlContent .= '</body></html>';
$zip->addFromString('OEBPS/content.xhtml', $xhtmlContent);

// Add uncompressed mimetype strictly (workaround for PHP ZipArchive)
// Some newer PHP versions support setCompressionName
if (method_exists($zip, 'setCompressionName')) {
    $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
}

$zip->close();

// Send the file
header('Content-Type: application/epub+zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($tempFile);
unlink($tempFile);
exit;
