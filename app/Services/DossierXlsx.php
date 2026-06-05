<?php

namespace App\Services;

use ZipArchive;

/**
 * Génère l'annexe financière .xlsx (DPGF + BPU) à partir de la DPGF Markdown
 * stockée dans le dossier d'un appel d'offres. Vrais nombres + formules
 * (Montant = Jours × PU, TOTAL = somme). Équivalent serveur de dossier_to_xlsx.py.
 *
 * Styles : 0 normal · 1 gras · 2 format € · 3 gras + €.
 */
class DossierXlsx
{
    /** Construit le binaire .xlsx, ou null si aucune table DPGF exploitable. */
    public static function build(array $dossier, string $idweb): ?string
    {
        $md = is_string($dossier['dpgf'] ?? null) ? $dossier['dpgf'] : '';
        $tables = self::parseTables($md);
        if (! $tables) {
            return null;
        }

        $objet = '';
        if (preg_match('/\*\*Marché\*\*\s*:\s*(.+?)(?:·|\n)/u', $md, $m)) {
            $objet = trim($m[1]);
        }

        // --- Feuille DPGF ---
        $dpgf = [
            [self::s("DPGF — {$objet}", 1)],
            [],
            [self::s('#', 1), self::s('Poste', 1), self::s('Jours', 1), self::s('PU € HT', 1), self::s('Montant € HT', 1)],
        ];
        $firstData = count($dpgf) + 1;
        $r = $firstData;
        foreach ($tables[0]['rows'] as $row) {
            $poste = $row[1] ?? '';
            if (str_contains(mb_strtoupper($poste), 'TOTAL')) {
                continue;
            }
            $jours = self::num($row[2] ?? '');
            $pu = self::num($row[3] ?? '');
            if ($jours === null || $pu === null) {
                continue;
            }
            $dpgf[] = [self::n(self::num($row[0] ?? '')), self::s($poste), self::n($jours), self::n($pu, 2), self::f("C{$r}*D{$r}", 2)];
            $r++;
        }
        $last = $r - 1;
        $dpgf[] = [
            null, self::s('TOTAL HT', 1),
            self::f("SUM(C{$firstData}:C{$last})", 1), null,
            self::f("SUM(E{$firstData}:E{$last})", 3),
        ];

        $sheets = [['DPGF', $dpgf]];

        // --- Feuille BPU (2e table, si présente) ---
        if (isset($tables[1])) {
            $t = $tables[1];
            $bpu = [
                [self::s('Bordereau des Prix Unitaires (BPU)', 1)],
                [],
                array_map(fn ($h) => self::s($h, 1), $t['header']),
            ];
            foreach ($t['rows'] as $row) {
                $cells = [];
                $n = count($row);
                foreach ($row as $j => $c) {
                    $pu = self::num($c);
                    $cells[] = ($j === $n - 1 && $pu !== null) ? self::n($pu, 2) : self::s($c);
                }
                $bpu[] = $cells;
            }
            $sheets[] = ['BPU', $bpu];
        }

        return self::zip($sheets);
    }

    // --- helpers cellules ---
    private static function s($text, int $style = 0): array { return ['s', (string) $text, $style]; }
    private static function n($num, int $style = 0): array { return ['n', $num, $style]; }
    private static function f(string $formula, int $style = 0): array { return ['f', $formula, $style]; }

    private static function num(string $cell): ?int
    {
        $digits = preg_replace('/[^\d]/', '', $cell);

        return $digits === '' ? null : (int) $digits;
    }

    private static function col(int $idx): string
    {
        $s = '';
        $idx++;
        while ($idx) {
            $idx--;
            $s = chr(65 + ($idx % 26)).$s;
            $idx = intdiv($idx, 26);
        }

        return $s;
    }

    /** Extrait les tableaux Markdown : [ ['header'=>[], 'rows'=>[[]]] ]. */
    private static function parseTables(string $md): array
    {
        $tables = [];
        $cur = null;
        foreach (explode("\n", str_replace("\r", '', $md)) as $line) {
            if (str_starts_with(ltrim($line), '|')) {
                $cells = array_map('trim', explode('|', trim(trim($line), '|')));
                if (preg_match('/^[\s:|-]+$/', implode('|', $cells))) {
                    continue;   // ligne de séparation |---|
                }
                if ($cur === null) {
                    $cur = ['header' => $cells, 'rows' => []];
                } else {
                    $cur['rows'][] = $cells;
                }
            } elseif ($cur !== null) {
                $tables[] = $cur;
                $cur = null;
            }
        }
        if ($cur !== null) {
            $tables[] = $cur;
        }

        return $tables;
    }

    private static function sheetXml(array $rows): string
    {
        $body = '';
        foreach ($rows as $r => $row) {
            $rn = $r + 1;
            $cells = '';
            foreach ($row as $c => $cell) {
                if ($cell === null) {
                    continue;
                }
                [$kind, $val, $style] = $cell;
                $ref = self::col($c).$rn;
                $s = $style ? " s=\"{$style}\"" : '';
                if ($kind === 's') {
                    $cells .= "<c r=\"{$ref}\"{$s} t=\"inlineStr\"><is><t xml:space=\"preserve\">"
                        .htmlspecialchars($val, ENT_XML1, 'UTF-8').'</t></is></c>';
                } elseif ($kind === 'n') {
                    $cells .= "<c r=\"{$ref}\"{$s}><v>{$val}</v></c>";
                } else { // f
                    $cells .= "<c r=\"{$ref}\"{$s}><f>".htmlspecialchars($val, ENT_XML1, 'UTF-8').'</f></c>';
                }
            }
            $body .= "<row r=\"{$rn}\">{$cells}</row>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            ."<sheetData>{$body}</sheetData></worksheet>";
    }

    private static function zip(array $sheets): ?string
    {
        $nb = count($sheets);

        $ctOver = '';
        $wbSheets = '';
        $wbRels = '';
        for ($i = 1; $i <= $nb; $i++) {
            $ctOver .= "<Override PartName=\"/xl/worksheets/sheet{$i}.xml\" "
                .'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $name = htmlspecialchars($sheets[$i - 1][0], ENT_XML1, 'UTF-8');
            $wbSheets .= "<sheet name=\"{$name}\" sheetId=\"{$i}\" r:id=\"rId{$i}\"/>";
            $wbRels .= "<Relationship Id=\"rId{$i}\" "
                .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                ."Target=\"worksheets/sheet{$i}.xml\"/>";
        }
        $styleRid = $nb + 1;
        $wbRels .= "<Relationship Id=\"rId{$styleRid}\" "
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$ctOver.'</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<sheets>{$wbSheets}</sheets></workbook>";

        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$wbRels.'</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0\ &quot;€&quot;"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            return null;
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);
        $zip->addFromString('xl/styles.xml', $styles);
        for ($i = 1; $i <= $nb; $i++) {
            $zip->addFromString("xl/worksheets/sheet{$i}.xml", self::sheetXml($sheets[$i - 1][1]));
        }
        $zip->close();

        $bin = file_get_contents($tmp);
        @unlink($tmp);

        return $bin ?: null;
    }
}
