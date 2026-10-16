<?php

namespace App\Support;

/** Penulis PDF tabel minimal tanpa dependensi (A4 landscape, font Helvetica bawaan PDF). */
class SimplePdf
{
    private const W = 842, H = 595, M = 36, ROW = 17;

    /**
     * Satu tabel panjang.
     *
     * @param  list<string>  $headers
     * @param  list<list<scalar|null>>  $rows
     * @param  list<float>  $weights  proporsi lebar kolom
     * @param  list<int>  $rightAlign  indeks kolom rata kanan
     */
    public static function table(string $title, string $subtitle, array $headers, array $rows, array $weights, array $rightAlign = []): string
    {
        return self::report($title, $subtitle, [['heading' => '', 'headers' => $headers, 'rows' => $rows, 'weights' => $weights, 'right' => $rightAlign]]);
    }

    /**
     * Dokumen berisi beberapa tabel berurutan, mengalir ke halaman berikutnya bila penuh.
     *
     * @param  list<array{heading:string,headers:list<string>,rows:list<list<scalar|null>>,weights:list<float>,right?:list<int>}>  $sections
     */
    public static function report(string $title, string $subtitle, array $sections): string
    {
        $usable = self::W - 2 * self::M;
        $floor = self::M + 18;
        $pages = [];
        $c = '';
        $y = 0;

        $newPage = function (bool $first) use (&$pages, &$c, &$y, $title, $subtitle) {
            if ($c !== '') {
                $pages[] = $c;
            }
            $c = '';
            if ($first) {
                $c .= self::text(self::M, self::H - self::M - 4, 'F2', 15, $title);
                $c .= self::text(self::M, self::H - self::M - 20, 'F1', 8.5, $subtitle, '0.45 0.45 0.45');
                $y = self::H - self::M - 46;
            } else {
                $c .= self::text(self::M, self::H - self::M - 4, 'F2', 9, $title, '0.45 0.45 0.45');
                $y = self::H - self::M - 28;
            }
        };

        $header = function (array $s, array $widths) use (&$c, &$y, $usable) {
            $c .= '0.165 0.365 0.69 rg ' . self::M . ' ' . ($y - 5) . " $usable " . self::ROW . " re f\n";
            $x = self::M;
            foreach ($s['headers'] as $i => $h) {
                $c .= self::cell($x, $y, $widths[$i], 'F2', 8, (string) $h, in_array($i, $s['right'] ?? [], true), '1 1 1');
                $x += $widths[$i];
            }
            $y -= self::ROW;
        };

        $newPage(true);

        foreach ($sections as $s) {
            $sum = array_sum($s['weights']);
            $widths = array_map(fn ($w) => $w / $sum * $usable, $s['weights']);
            $rows = $s['rows'] ?: [array_fill(0, count($s['headers']), '')];
            $rows = array_values($rows);

            // Judul + header + minimal 2 baris harus muat; kalau tidak, pindah halaman.
            if ($y - (self::ROW * 3 + 24) < $floor && $y < self::H - self::M - 60) {
                $newPage(false);
            }
            if (($s['heading'] ?? '') !== '') {
                $c .= self::text(self::M, $y, 'F2', 11, $s['heading']);
                $y -= 20;
            }
            $header($s, $widths);

            foreach ($rows as $ri => $row) {
                if ($y < $floor) {
                    $newPage(false);
                    $header($s, $widths);
                }
                if ($ri % 2 === 1) {
                    $c .= '0.95 0.95 0.95 rg ' . self::M . ' ' . ($y - 5) . " $usable " . self::ROW . " re f\n";
                }
                $x = self::M;
                foreach ($s['headers'] as $i => $_) {
                    $c .= self::cell($x, $y, $widths[$i], 'F1', 8, (string) ($row[$i] ?? ''), in_array($i, $s['right'] ?? [], true), '0.1 0.1 0.1');
                    $x += $widths[$i];
                }
                $y -= self::ROW;
            }
            $y -= 16;
        }
        $pages[] = $c;

        $total = count($pages);
        foreach ($pages as $i => &$content) {
            $content .= self::text(self::W - self::M - 70, self::M - 12, 'F1', 8, 'Halaman ' . ($i + 1) . '/' . $total, '0.5 0.5 0.5');
        }
        unset($content);

        // Objek PDF: 1 katalog, 2 pages, 3-4 font, lalu pasangan (page, content) per halaman.
        $objs = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $i => $content) {
            $p = 5 + $i * 2;
            $kids[] = "$p 0 R";
            $objs[$p] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . ($p + 1) . ' 0 R >>';
            $objs[$p + 1] = '<< /Length ' . strlen($content) . " >>\nstream\n$content\nendstream";
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objs);

        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $n => $body) {
            $offsets[$n] = strlen($out);
            $out .= "$n 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $out .= sprintf("%010d 00000 n \n", $o);
        }

        return $out . 'trailer << /Size ' . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private static function cell(float $x, float $y, float $w, string $font, float $size, string $text, bool $right, string $color): string
    {
        $max = max(1, (int) floor(($w - 8) / ($size * 0.5)));
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, max(1, $max - 1)) . '…';
        }
        $tx = $right ? $x + $w - 4 - mb_strlen($text) * $size * 0.5 : $x + 4;

        return self::text($tx, $y, $font, $size, $text, $color);
    }

    private static function text(float $x, float $y, string $font, float $size, string $text, string $color = '0 0 0'): string
    {
        $t = @mb_convert_encoding(str_replace(['…', '–', '—', '’', '“', '”'], ['...', '-', '-', "'", '"', '"'], $text), 'Windows-1252', 'UTF-8');
        $t = strtr($t, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => ' ', "\n" => ' ']);

        return sprintf("BT /%s %s Tf %s rg %.2f %.2f Td (%s) Tj ET\n", $font, $size, $color, $x, $y, $t);
    }
}
