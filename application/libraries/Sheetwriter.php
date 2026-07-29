<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sheetwriter — builds a real .xlsx workbook.
 *
 * The counterpart to Sheetreader. This project has no Composer vendor/, so
 * PhpSpreadsheet is not available; an .xlsx is a ZIP of XML parts, and PHP
 * ships `zip`, so the parts are emitted directly.
 *
 * Deliberately minimal but STRICTLY valid — Excel refuses to open a workbook
 * with a missing or undeclared part, so every part is listed in
 * [Content_Types].xml and wired through the two .rels files:
 *
 *   [Content_Types].xml          every part's MIME type
 *   _rels/.rels                  package  -> workbook
 *   xl/workbook.xml              the sheet list
 *   xl/_rels/workbook.xml.rels   workbook -> worksheet + styles
 *   xl/styles.xml                two fonts, so the header row can be bold
 *   xl/worksheets/sheet1.xml     the cells
 *
 * Strings are written as INLINE strings rather than through a sharedStrings
 * table. It costs a few bytes on repeated text but removes a whole part and
 * its index bookkeeping, and a bulk-import template or export has little
 * repetition anyway.
 *
 * TYPING: a PHP int/float is written as a NUMERIC cell; anything else is
 * written as text. That is the caller's lever and it matters — auto-detecting
 * "looks numeric" would turn a referral code like 001234 into 1234 and a
 * username like 12345 into a number. Pass numbers as numbers, everything else
 * as strings.
 */
class Sheetwriter
{
    /** Highest column index Excel supports (XFD = 16384 columns). */
    const MAX_COLS = 16384;

    /**
     * Build a workbook and return the raw .xlsx bytes.
     *
     * @param  array  $rows  List of rows; each row a list of scalar cells.
     *                       Row 0 is styled as a bold header.
     * @param  string $sheetName Tab name (sanitised; Excel forbids : \ / ? * [ ]).
     * @return string Binary .xlsx content.
     * @throws RuntimeException when ext-zip is unavailable or the file cannot be built.
     */
    public function build(array $rows, $sheetName = 'Sheet1')
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP zip extension is not enabled, so an .xlsx cannot be generated.');
        }

        // ZipArchive writes to a path, not a stream, so build in a temp file.
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) throw new RuntimeException('Could not create a temporary file for the workbook.');

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Could not open the workbook archive for writing.');
        }

        $zip->addFromString('[Content_Types].xml',        $this->contentTypes());
        $zip->addFromString('_rels/.rels',                $this->packageRels());
        $zip->addFromString('xl/workbook.xml',            $this->workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml',              $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml',   $this->sheet($rows));
        $zip->close();

        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        if ($bytes === false) throw new RuntimeException('Could not read back the generated workbook.');
        return $bytes;
    }

    /** Build and send as a download. Ends the response. */
    public function download(array $rows, $filename, $sheetName = 'Sheet1')
    {
        $bytes = $this->build($rows, $sheetName);

        // Sent with header() rather than CI's output class so the binary body
        // cannot pick up any trailing whitespace from a loaded view.
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.str_replace('"', '', $filename).'"');
        header('Content-Length: '.strlen($bytes));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $bytes;
        exit;
    }

    /* ============================== parts ============================== */

    private function contentTypes()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function packageRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook($sheetName)
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->esc($this->sheetName($sheetName)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /**
     * Two cell formats: s="0" normal, s="1" bold (the header row).
     * Excel treats several of these collections as required even when empty,
     * so fills/borders/cellStyleXfs are present with their default entries.
     */
    private function styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
              .'<font><sz val="11"/><name val="Calibri"/></font>'
              .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
              .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
              .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function sheet(array $rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $rowNum = 0;
        foreach ($rows as $row) {
            $rowNum++;
            if (!is_array($row)) $row = [$row];
            $style = $rowNum === 1 ? ' s="1"' : '';   // bold header

            $xml .= '<row r="'.$rowNum.'">';
            $colNum = 0;
            foreach ($row as $value) {
                $colNum++;
                if ($colNum > self::MAX_COLS) break;
                // Skip empty cells entirely — that is what Excel itself does,
                // and Sheetreader rebuilds the gaps from the r="" refs.
                if ($value === null || $value === '') continue;

                $ref = $this->colLetter($colNum).$rowNum;

                if (is_int($value) || is_float($value)) {
                    // is_finite guards against INF/NAN, which are not valid XML numbers.
                    if (is_float($value) && !is_finite($value)) {
                        $xml .= '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t>'.$this->esc((string)$value).'</t></is></c>';
                    } else {
                        $xml .= '<c r="'.$ref.'"'.$style.'><v>'.$this->num($value).'</v></c>';
                    }
                } else {
                    $xml .= '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'
                          . $this->esc((string)$value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    /* ============================== helpers ============================= */

    /** 1 -> A, 27 -> AA, 16384 -> XFD. */
    private function colLetter($n)
    {
        $s = '';
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $s = chr(65 + $rem).$s;
            $n = (int)(($n - $rem - 1) / 26);
        }
        return $s;
    }

    /** Locale-proof number formatting — a comma decimal separator is not valid XML. */
    private function num($v)
    {
        if (is_int($v)) return (string)$v;
        $s = rtrim(rtrim(number_format((float)$v, 8, '.', ''), '0'), '.');
        return $s === '' || $s === '-' ? '0' : $s;
    }

    /** Excel forbids : \ / ? * [ ] in a tab name and caps it at 31 chars. */
    private function sheetName($name)
    {
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '', (string)$name);
        $name = trim($name) !== '' ? $name : 'Sheet1';
        return mb_substr($name, 0, 31);
    }

    /**
     * XML-escape, and strip control characters that are illegal in XML 1.0 —
     * a stray 0x00-0x08 from pasted data makes the whole workbook unopenable.
     */
    private function esc($s)
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$s);
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
