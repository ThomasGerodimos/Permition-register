<?php
/**
 * Generate DOCUMENTATION.docx from DOCUMENTATION.md
 *
 * Prerequisites:
 *   composer require phpoffice/phpword
 *
 * Usage:
 *   Open in browser: http://localhost/permissions/docs/generate-docx.php
 *   Or run from CLI: php docs/generate-docx.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;

// ── Read Markdown ──────────────────────────────────────────────────────
$md = file_get_contents(__DIR__ . '/DOCUMENTATION.md');
if (!$md) {
    die('Cannot read DOCUMENTATION.md');
}

$lines = explode("\n", $md);

// ── Create Document ────────────────────────────────────────────────────
$phpWord = new PhpWord();

// Default font
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(10);

// Styles
$phpWord->addTitleStyle(1, ['size' => 22, 'bold' => true, 'color' => '1a3c5e'], ['spaceAfter' => 200, 'spaceBefore' => 0]);
$phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '1a5276'], ['spaceAfter' => 120, 'spaceBefore' => 300, 'borderBottomSize' => 6, 'borderBottomColor' => '2c5f8a']);
$phpWord->addTitleStyle(3, ['size' => 13, 'bold' => true, 'color' => '2c5f8a'], ['spaceAfter' => 80, 'spaceBefore' => 200]);
$phpWord->addTitleStyle(4, ['size' => 11, 'bold' => true, 'color' => '34495e'], ['spaceAfter' => 60, 'spaceBefore' => 160]);

$phpWord->addParagraphStyle('Normal', ['spaceAfter' => 80, 'spaceBefore' => 0]);
$phpWord->addParagraphStyle('CodeBlock', [
    'spaceAfter' => 80, 'spaceBefore' => 80,
    'indentation' => ['left' => 400],
]);
$phpWord->addFontStyle('CodeFont', ['name' => 'Consolas', 'size' => 8, 'color' => '2d2d2d']);
$phpWord->addFontStyle('BoldText', ['bold' => true]);
$phpWord->addFontStyle('CodeInline', ['name' => 'Consolas', 'size' => 9, 'color' => 'c0392b', 'bgColor' => 'f5f5f5']);

// Header/footer styles
$phpWord->addParagraphStyle('Footer', ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

// ── Section ────────────────────────────────────────────────────────────
$section = $phpWord->addSection([
    'marginTop'    => 1134,  // ~2cm
    'marginBottom' => 1134,
    'marginLeft'   => 1418,  // ~2.5cm
    'marginRight'  => 1134,
    'headerHeight' => 567,
    'footerHeight' => 567,
]);

// Header
$header = $section->addHeader();
$headerTable = $header->addTable();
$headerTable->addRow();
$cell1 = $headerTable->addCell(5000);
$cell1->addText('Μητρώο Δικαιωμάτων', ['size' => 8, 'color' => '888888', 'italic' => true], ['spaceAfter' => 0]);
$cell2 = $headerTable->addCell(5000);
$cell2->addText('Τεχνική Τεκμηρίωση v1.0', ['size' => 8, 'color' => '888888', 'italic' => true], ['alignment' => Jc::END, 'spaceAfter' => 0]);

// Footer
$footer = $section->addFooter();
$footer->addPreserveText('ΑΚΝΕΕΔ — Υποδιεύθυνση Ψηφιακής Διακυβέρνησης                                                                          Σελίδα {PAGE} / {NUMPAGES}', ['size' => 7, 'color' => '999999'], 'Footer');

// ── Cover Page ─────────────────────────────────────────────────────────
$section->addTextBreak(4);
$section->addText('ΑΚΝΕΕΔ', ['size' => 12, 'color' => '666666'], ['alignment' => Jc::CENTER]);
$section->addText('Αρχή Καταπολέμησης της Νομιμοποίησης', ['size' => 11, 'color' => '888888'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
$section->addText('Εσόδων από Εγκληματικές Δραστηριότητες', ['size' => 11, 'color' => '888888'], ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);

// Title
$section->addText('Μητρώο Δικαιωμάτων', ['size' => 28, 'bold' => true, 'color' => '1a3c5e'], ['alignment' => Jc::CENTER, 'spaceAfter' => 100]);
$section->addText('Τεχνική Τεκμηρίωση', ['size' => 18, 'color' => '2c5f8a'], ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);

// Horizontal line
$section->addText('', [], ['borderBottomSize' => 12, 'borderBottomColor' => '2c5f8a', 'spaceAfter' => 200]);

// Info table
$infoTable = $section->addTable(['borderSize' => 0, 'cellMarginTop' => 40, 'cellMarginBottom' => 40]);
$infoData = [
    ['Έκδοση', '1.0'],
    ['Ημερομηνία', 'Μάρτιος 2026'],
    ['Ανάπτυξη', 'Τμήμα Ανάπτυξης και Υποστήριξης Εφαρμογών'],
    ['Υποδιεύθυνση', 'Ψηφιακής Διακυβέρνησης'],
];
foreach ($infoData as $row) {
    $tr = $infoTable->addRow();
    $tr->addCell(2500)->addText($row[0], ['bold' => true, 'size' => 10, 'color' => '555555'], ['alignment' => Jc::END]);
    $tr->addCell(6000)->addText($row[1], ['size' => 10], ['indentation' => ['left' => 200]]);
}

$section->addPageBreak();

// ── Parse Markdown ─────────────────────────────────────────────────────
$inCodeBlock = false;
$codeBuffer  = [];
$inTable     = false;
$tableRows   = [];
$currentTable = null;

function flushCode(&$section, &$codeBuffer, &$inCodeBlock) {
    if (!empty($codeBuffer)) {
        // Add shaded text block
        $textRun = $section->addTextRun('CodeBlock');
        $code = implode("\n", $codeBuffer);
        foreach (explode("\n", $code) as $i => $line) {
            if ($i > 0) $textRun->addTextBreak();
            $textRun->addText(
                htmlspecialchars($line ?: ' ', ENT_XML1, 'UTF-8'),
                'CodeFont'
            );
        }
    }
    $codeBuffer = [];
    $inCodeBlock = false;
}

function flushTable(&$section, &$tableRows, &$inTable) {
    if (empty($tableRows)) { $inTable = false; return; }

    // Parse header + separator + data
    $headers = [];
    $data    = [];
    $started = false;

    foreach ($tableRows as $i => $row) {
        $cells = array_map('trim', explode('|', trim($row, '|')));
        if ($i === 0) {
            $headers = $cells;
        } elseif (preg_match('/^[\s|:-]+$/', $row)) {
            $started = true;
            continue;
        } else {
            $data[] = $cells;
        }
    }

    $colCount = count($headers);
    if ($colCount === 0) { $tableRows = []; $inTable = false; return; }

    // Calculate column widths (total ~9000 twips)
    $totalWidth = 9000;
    $colWidth = (int)($totalWidth / $colCount);

    $style = [
        'borderSize'  => 4,
        'borderColor' => 'CCCCCC',
        'cellMarginTop' => 30,
        'cellMarginBottom' => 30,
        'cellMarginLeft' => 80,
        'cellMarginRight' => 80,
    ];

    $table = $section->addTable($style);

    // Header row
    $table->addRow(null, ['tblHeader' => true]);
    foreach ($headers as $h) {
        $cell = $table->addCell($colWidth, ['bgColor' => '2c5f8a', 'valign' => 'center']);
        $cell->addText(
            htmlspecialchars(trim($h), ENT_XML1, 'UTF-8'),
            ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
            ['spaceAfter' => 0, 'spaceBefore' => 0]
        );
    }

    // Data rows
    foreach ($data as $ri => $cells) {
        $bgColor = ($ri % 2 === 0) ? 'FFFFFF' : 'F0F5FA';
        $table->addRow();
        for ($c = 0; $c < $colCount; $c++) {
            $val = $cells[$c] ?? '';
            $cell = $table->addCell($colWidth, ['bgColor' => $bgColor, 'valign' => 'center']);
            addRichText($cell, $val, 9);
        }
    }

    $section->addTextBreak(0);
    $tableRows = [];
    $inTable = false;
}

function addRichText($container, $text, $size = 10, $paragraphStyle = null) {
    $pStyle = $paragraphStyle ?? ['spaceAfter' => 40, 'spaceBefore' => 0];

    // Simple parse for bold (**text**) and code (`text`)
    $text = trim($text);
    if ($text === '') {
        $container->addText(' ', ['size' => $size], $pStyle);
        return;
    }

    // Check for checkboxes
    $text = str_replace('☐', '[ ]', $text);

    $textRun = $container->addTextRun($pStyle);

    // Split by backticks and bold markers
    $pattern = '/(`[^`]+`|\*\*[^*]+\*\*)/';
    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    foreach ($parts as $part) {
        if (preg_match('/^`(.+)`$/', $part, $m)) {
            $textRun->addText(htmlspecialchars($m[1], ENT_XML1, 'UTF-8'), ['name' => 'Consolas', 'size' => $size - 1, 'color' => 'c0392b']);
        } elseif (preg_match('/^\*\*(.+)\*\*$/', $part, $m)) {
            $textRun->addText(htmlspecialchars($m[1], ENT_XML1, 'UTF-8'), ['bold' => true, 'size' => $size]);
        } elseif ($part !== '') {
            $textRun->addText(htmlspecialchars($part, ENT_XML1, 'UTF-8'), ['size' => $size]);
        }
    }
}

// ── Process lines ──────────────────────────────────────────────────────
$skipCoverInfo = false; // Skip the version info table at the end (we put it on cover)

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];

    // Skip the TOC section (we have our own)
    // Skip the final version table (already on cover)
    if (trim($line) === '## Πληροφορίες Έκδοσης') {
        $skipCoverInfo = true;
        continue;
    }
    if ($skipCoverInfo) continue;

    // Code block toggle
    if (str_starts_with(trim($line), '```')) {
        if ($inCodeBlock) {
            flushCode($section, $codeBuffer, $inCodeBlock);
        } else {
            // Flush any pending table
            if ($inTable) flushTable($section, $tableRows, $inTable);
            $inCodeBlock = true;
            $codeBuffer = [];
        }
        continue;
    }

    if ($inCodeBlock) {
        $codeBuffer[] = $line;
        continue;
    }

    // Table detection
    if (preg_match('/^\|/', trim($line))) {
        if (!$inTable) {
            $inTable = true;
            $tableRows = [];
        }
        $tableRows[] = $line;
        continue;
    } else {
        if ($inTable) {
            flushTable($section, $tableRows, $inTable);
        }
    }

    $trimmed = trim($line);

    // Empty line
    if ($trimmed === '') {
        continue;
    }

    // Horizontal rule
    if ($trimmed === '---' || $trimmed === '***') {
        $section->addText('', [], ['borderBottomSize' => 4, 'borderBottomColor' => 'CCCCCC', 'spaceAfter' => 100, 'spaceBefore' => 100]);
        continue;
    }

    // Headings
    if (preg_match('/^(#{1,4})\s+(.+)$/', $trimmed, $m)) {
        $level = strlen($m[1]);
        $text  = trim($m[2]);
        // Clean markdown from heading text
        $text = preg_replace('/[`*]/', '', $text);

        // Skip TOC heading
        if ($text === 'Περιεχόμενα') {
            // Skip TOC lines until next heading or ---
            while ($i + 1 < count($lines)) {
                $nextLine = trim($lines[$i + 1]);
                if (str_starts_with($nextLine, '#') || $nextLine === '---') break;
                $i++;
            }
            continue;
        }

        $section->addTitle(htmlspecialchars($text, ENT_XML1, 'UTF-8'), $level);
        continue;
    }

    // Bullet list
    if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
        $textRun = $section->addTextRun(['indentation' => ['left' => 400, 'hanging' => 200], 'spaceAfter' => 40]);
        $textRun->addText('  •  ', ['size' => 10, 'color' => '2c5f8a']);

        // Parse inline formatting
        $content = $m[1];
        $pattern = '/(`[^`]+`|\*\*[^*]+\*\*)/';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (preg_match('/^`(.+)`$/', $part, $cm)) {
                $textRun->addText(htmlspecialchars($cm[1], ENT_XML1, 'UTF-8'), ['name' => 'Consolas', 'size' => 9, 'color' => 'c0392b']);
            } elseif (preg_match('/^\*\*(.+)\*\*$/', $part, $cm)) {
                $textRun->addText(htmlspecialchars($cm[1], ENT_XML1, 'UTF-8'), ['bold' => true, 'size' => 10]);
            } elseif ($part !== '') {
                $textRun->addText(htmlspecialchars($part, ENT_XML1, 'UTF-8'), ['size' => 10]);
            }
        }
        continue;
    }

    // Numbered list
    if (preg_match('/^(\d+)\.\s+(.+)$/', $trimmed, $m)) {
        $textRun = $section->addTextRun(['indentation' => ['left' => 400, 'hanging' => 200], 'spaceAfter' => 40]);
        $textRun->addText('  ' . $m[1] . '.  ', ['size' => 10, 'bold' => true, 'color' => '2c5f8a']);

        $content = $m[2];
        // Strip markdown links [text](#anchor)
        $content = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $content);

        $pattern = '/(`[^`]+`|\*\*[^*]+\*\*)/';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (preg_match('/^`(.+)`$/', $part, $cm)) {
                $textRun->addText(htmlspecialchars($cm[1], ENT_XML1, 'UTF-8'), ['name' => 'Consolas', 'size' => 9, 'color' => 'c0392b']);
            } elseif (preg_match('/^\*\*(.+)\*\*$/', $part, $cm)) {
                $textRun->addText(htmlspecialchars($cm[1], ENT_XML1, 'UTF-8'), ['bold' => true, 'size' => 10]);
            } elseif ($part !== '') {
                $textRun->addText(htmlspecialchars($part, ENT_XML1, 'UTF-8'), ['size' => 10]);
            }
        }
        continue;
    }

    // Blockquote
    if (str_starts_with($trimmed, '>')) {
        $content = ltrim($trimmed, '> ');
        $section->addText(
            htmlspecialchars($content, ENT_XML1, 'UTF-8'),
            ['size' => 10, 'italic' => true, 'color' => '555555'],
            ['indentation' => ['left' => 400], 'borderLeftSize' => 12, 'borderLeftColor' => '2c5f8a', 'spaceAfter' => 80]
        );
        continue;
    }

    // Normal paragraph
    addRichText($section, $trimmed, 10, 'Normal');
}

// Flush remaining
if ($inCodeBlock) flushCode($section, $codeBuffer, $inCodeBlock);
if ($inTable) flushTable($section, $tableRows, $inTable);

// ── Save ───────────────────────────────────────────────────────────────
$outputPath = __DIR__ . '/DOCUMENTATION.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($outputPath);

$msg = "DOCUMENTATION.docx generated successfully at: {$outputPath}";
if (php_sapi_name() === 'cli') {
    echo $msg . "\n";
} else {
    echo "<div style='font-family:Arial;padding:40px;text-align:center;'>";
    echo "<h2 style='color:#2c5f8a;'>&#10004; {$msg}</h2>";
    echo "<p><a href='DOCUMENTATION.docx' download>Download DOCUMENTATION.docx</a></p>";
    echo "</div>";
}
