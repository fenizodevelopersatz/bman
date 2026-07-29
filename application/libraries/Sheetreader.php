<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sheetreader — reads a spreadsheet into a plain array of rows.
 *
 * This project has no Composer vendor/ directory, so PhpSpreadsheet is not an
 * option. An .xlsx is just a ZIP of XML, and PHP ships both `zip` and
 * `SimpleXML`, so the ~150 lines below cover everything a bulk-import sheet
 * actually contains: shared strings, inline strings, numbers, and dates.
 * Formulas are read as their CACHED result (the <v> node Excel wrote on save),
 * which is what a data sheet wants anyway.
 *
 * Supported: .xlsx, .csv, .txt (tab/comma/semicolon delimited).
 * NOT supported: the legacy binary .xls — that format needs a real parser, so
 * read() rejects it with a message telling the admin to re-save as .xlsx/.csv.
 *
 * Everything comes back as a trimmed string; the caller decides what is a
 * number, a date, or a code. That keeps "007" and "1.0" from being silently
 * mangled into 7 and 1 on the way in.
 */
class Sheetreader
{
    /** Hard cap so a malicious/huge file cannot exhaust memory. */
    const MAX_ROWS = 20000;

    /**
     * Read a spreadsheet file into rows.
     *
     * @param  string $path Absolute path to the file.
     * @param  string $ext  Extension WITHOUT the dot (defaults to the path's).
     * @return array  List of rows; each row is a list of cell strings.
     * @throws RuntimeException on an unreadable / unsupported file.
     */
    public function read($path, $ext = null)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Uploaded file could not be read.');
        }
        $ext = strtolower($ext !== null ? $ext : pathinfo($path, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'xlsx':
            case 'xlsm':
                return $this->readXlsx($path);
            case 'csv':
            case 'txt':
                return $this->readCsv($path);
            case 'xls':
                throw new RuntimeException('The old .xls format is not supported. Open the file in Excel and use "Save As" → .xlsx or .csv.');
            default:
                throw new RuntimeException('Unsupported file type ".'.$ext.'". Upload an .xlsx or .csv file.');
        }
    }

    /* ================================ CSV ================================ */

    private function readCsv($path)
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new RuntimeException('Uploaded file could not be opened.');

        // Sniff the delimiter from the first non-empty line — sheets exported
        // from Excel in a European locale use ';', not ','.
        $first = '';
        while (($first = fgets($handle)) !== false && trim($first) === '') { /* skip blanks */ }
        $delims = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($delims);
        $delim = key($delims);
        if ($delims[$delim] === 0) $delim = ',';
        rewind($handle);

        $rows = [];
        while (($cells = fgetcsv($handle, 0, $delim)) !== false) {
            if ($cells === [null]) continue;                      // blank line
            $rows[] = array_map([$this, 'clean'], $cells);
            if (count($rows) >= self::MAX_ROWS) break;
        }
        fclose($handle);

        // Strip a UTF-8 BOM off the very first cell so the header matches.
        if (isset($rows[0][0])) $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);

        return $rows;
    }

    /* =============================== XLSX ================================ */

    private function readXlsx($path)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP zip extension is not enabled, so .xlsx cannot be read. Upload a .csv instead, or enable ext-zip.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('That file is not a valid .xlsx workbook.');
        }

        try {
            $shared = $this->sharedStrings($zip);
            $sheetXml = $this->firstSheetXml($zip);
        } finally {
            $zip->close();
        }

        $xml = $this->parseXml($sheetXml);
        if ($xml === false) throw new RuntimeException('The workbook’s sheet XML could not be parsed.');

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $maxIndex = -1;
            foreach ($row->c as $c) {
                $attr = $c->attributes();
                $index = isset($attr['r']) ? $this->columnIndex((string)$attr['r']) : $maxIndex + 1;
                $cells[$index] = $this->cellValue($c, $shared);
                if ($index > $maxIndex) $maxIndex = $index;
            }
            if ($maxIndex < 0) { $rows[] = []; continue; }

            // Excel omits empty cells entirely, so rebuild the gaps — otherwise
            // a blank column would shift every later column left by one.
            $flat = [];
            for ($i = 0; $i <= $maxIndex; $i++) $flat[] = $cells[$i] ?? '';
            $rows[] = $flat;

            if (count($rows) >= self::MAX_ROWS) break;
        }
        return $rows;
    }

    /** The <sst> table .xlsx uses to de-duplicate every text cell in the book. */
    private function sharedStrings(ZipArchive $zip)
    {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) return [];                            // numbers-only sheet
        $xml = $this->parseXml($raw);
        if ($xml === false) return [];

        $out = [];
        foreach ($xml->si as $si) {
            // A cell with mixed formatting is split across several <t> runs.
            $text = '';
            foreach ($si->xpath('.//*[local-name()="t"]') as $t) $text .= (string)$t;
            $out[] = $text;
        }
        return $out;
    }

    /**
     * The XML of the FIRST worksheet. Resolved through workbook.xml.rels rather
     * than assuming sheet1.xml — a workbook whose first tab was renamed or
     * reordered keeps its original file name, so the naive guess reads the
     * wrong tab.
     */
    private function firstSheetXml(ZipArchive $zip)
    {
        $target = 'xl/worksheets/sheet1.xml';

        $book = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($book !== false && $rels !== false) {
            $bookXml = $this->parseXml($book);
            $relsXml = $this->parseXml($rels);
            if ($bookXml !== false && $relsXml !== false && isset($bookXml->sheets->sheet[0])) {
                $rid = (string)$bookXml->sheets->sheet[0]->attributes('r', true)['id'];
                foreach ($relsXml->Relationship as $rel) {
                    if ((string)$rel['Id'] !== $rid) continue;
                    $t = ltrim((string)$rel['Target'], '/');
                    $target = strpos($t, 'xl/') === 0 ? $t : 'xl/'.$t;
                    break;
                }
            }
        }

        $xml = $zip->getFromName($target);
        if ($xml === false) $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($xml === false) throw new RuntimeException('The workbook contains no readable worksheet.');
        return $xml;
    }

    /** One cell's display value, resolving the shared-string and boolean types. */
    private function cellValue(SimpleXMLElement $c, array $shared)
    {
        $type = (string)($c->attributes()['t'] ?? '');

        if ($type === 'inlineStr') {
            $text = '';
            foreach ($c->xpath('.//*[local-name()="t"]') as $t) $text .= (string)$t;
            return $this->clean($text);
        }

        $v = isset($c->v) ? (string)$c->v : '';
        if ($v === '') return '';

        if ($type === 's') {
            $i = (int)$v;
            return $this->clean($shared[$i] ?? '');
        }
        if ($type === 'b')   return $v === '1' ? 'TRUE' : 'FALSE';
        if ($type === 'str') return $this->clean($v);             // cached formula result

        return $this->clean($v);
    }

    /** "BC12" → 54 (0-based column index); the row part is ignored. */
    private function columnIndex($ref)
    {
        if (!preg_match('/^([A-Za-z]+)/', $ref, $m)) return 0;
        $letters = strtoupper($m[1]);
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    /** Parse without letting a malformed/hostile document raise warnings. */
    private function parseXml($raw)
    {
        $prev = libxml_use_internal_errors(true);
        // LIBXML_NONET + no ENT substitution: an .xlsx is untrusted input, and
        // this keeps a crafted DOCTYPE from reaching out or expanding entities.
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $xml;
    }

    private function clean($value)
    {
        if ($value === null) return '';
        // NBSP is what you get when a cell was pasted from a web page.
        $value = str_replace(["\xC2\xA0", "\r"], [' ', ''], (string)$value);
        return trim($value);
    }
}
