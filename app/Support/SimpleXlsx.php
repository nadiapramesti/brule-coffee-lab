<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis .xlsx minimal tanpa dependensi (hanya ext-zip).
 * Teks ditulis sebagai inline string, jadi isi seperti "=HYPERLINK(...)" tidak pernah dieksekusi sebagai rumus.
 */
class SimpleXlsx
{
    private const STYLES = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2A5DB0"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>';

    /**
     * @param  list<string>  $headers
     * @param  iterable<list<scalar|null>>  $rows  angka (int/float) otomatis berformat ribuan
     * @param  list<int>  $widths  lebar kolom (karakter), opsional
     */
    public static function build(string $sheet, array $headers, iterable $rows, array $widths = []): string
    {
        return self::buildMany([['name' => $sheet, 'headers' => $headers, 'rows' => $rows, 'widths' => $widths]]);
    }

    /** @param list<array{name:string,headers:list<string>,rows:iterable,widths?:list<int>}> $sheets */
    public static function buildMany(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file Excel.');
        }

        $types = '';
        $wbSheets = '';
        $rels = '';
        foreach (array_values($sheets) as $i => $sh) {
            $n = $i + 1;
            $title = htmlspecialchars(mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $sh['name']), 0, 31), ENT_XML1);
            $types .= "<Override PartName=\"/xl/worksheets/sheet$n.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $wbSheets .= "<sheet name=\"$title\" sheetId=\"$n\" r:id=\"rId$n\"/>";
            $rels .= "<Relationship Id=\"rId$n\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet$n.xml\"/>";
            $zip->addFromString("xl/worksheets/sheet$n.xml", self::sheetXml($sh['headers'], $sh['rows'], $sh['widths'] ?? []));
        }
        $sid = count($sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $types . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wbSheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . "<Relationship Id=\"rId$sid\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/></Relationships>");
        $zip->addFromString('xl/styles.xml', self::STYLES);
        $zip->close();

        $bin = file_get_contents($tmp);
        @unlink($tmp);

        return $bin;
    }

    private static function sheetXml(array $headers, iterable $rows, array $widths): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';

        if ($widths) {
            $xml .= '<cols>';
            foreach ($widths as $i => $w) {
                $n = $i + 1;
                $xml .= "<col min=\"$n\" max=\"$n\" width=\"$w\" customWidth=\"1\"/>";
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>' . self::row(1, $headers, 1);
        $r = 2;
        foreach ($rows as $row) {
            $xml .= self::row($r++, $row, 0);
        }
        $last = self::col(max(count($headers), 1) - 1) . max($r - 1, 1);
        $xml .= "</sheetData><autoFilter ref=\"A1:$last\"/></worksheet>";


        return $xml;
    }

    private static function row(int $r, array $cells, int $style): string
    {
        $out = "<row r=\"$r\">";
        foreach (array_values($cells) as $i => $v) {
            $ref = self::col($i) . $r;
            if ($v === null || $v === '') {
                continue;
            }
            if (is_int($v) || is_float($v)) {
                $out .= "<c r=\"$ref\" s=\"" . ($style ?: 2) . "\"><v>$v</v></c>";
            } else {
                $t = htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $v), ENT_XML1 | ENT_SUBSTITUTE);
                $out .= "<c r=\"$ref\" t=\"inlineStr\"" . ($style ? " s=\"$style\"" : '') . "><is><t xml:space=\"preserve\">$t</t></is></c>";
            }
        }

        return $out . '</row>';
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }
}
