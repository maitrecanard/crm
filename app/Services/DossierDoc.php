<?php

namespace App\Services;

/**
 * Convertit un dossier de réponse (sections Markdown stockées en base) en un
 * document HTML ouvrable directement dans Word (.doc) : titres, gras, listes et
 * vrais tableaux. Équivalent serveur de dossier_to_doc.py.
 */
class DossierDoc
{
    private const CSS = <<<'CSS'
        body{font-family:Calibri,'Segoe UI',Arial,sans-serif;font-size:11pt;color:#1a1a1a;line-height:1.45;max-width:18cm;margin:1.5cm auto;}
        h1{font-size:20pt;border-bottom:2px solid #444;padding-bottom:4px;}
        h2{font-size:14pt;margin-top:18px;color:#222;}
        h3{font-size:12pt;margin-top:14px;}
        table{border-collapse:collapse;width:100%;margin:12px 0;font-size:10.5pt;}
        th,td{border:1px solid #999;padding:5px 8px;text-align:left;}
        th{background:#f0f0f0;}
        td.num{text-align:right;}
        blockquote{color:#666;border-left:3px solid #ccc;margin:10px 0;padding:4px 12px;background:#fafafa;}
        hr{border:none;border-top:1px solid #ccc;margin:16px 0;}
        .page-break{page-break-before:always;}
        ul{margin:6px 0;}
        code{background:#f3f3f3;padding:1px 4px;border-radius:3px;}
        CSS;

    /** Assemble le document complet à partir du dossier (resume, memoire, dpgf, acte). */
    public static function html(array $dossier, string $idweb): string
    {
        $parts = [
            ['key' => 'resume',  'break' => false],
            ['key' => 'memoire', 'break' => true],
            ['key' => 'dpgf',    'break' => true],
            ['key' => 'acte',    'break' => true],
        ];

        $sections = '';
        foreach ($parts as $p) {
            $md = $dossier[$p['key']] ?? '';
            if (! is_string($md) || trim($md) === '') {
                continue;
            }
            $cls = ($p['break'] && $sections !== '') ? ' class="page-break"' : '';
            $sections .= "<div{$cls}>".self::mdToHtml($md).'</div>';
        }

        $css = self::CSS;
        return "<html><head><meta charset='utf-8'><title>Dossier {$idweb}</title>"
            ."<style>{$css}</style></head><body>{$sections}</body></html>";
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/`(.+?)`/u', '<code>$1</code>', $text);

        return $text;
    }

    private static function isNum(string $cell): bool
    {
        $cell = trim($cell);

        return $cell !== ''
            && preg_match('/^[\d\s.,€%-]+$/u', $cell)
            && preg_match('/\d/', $cell);
    }

    private static function mdToHtml(string $md): string
    {
        $lines = explode("\n", str_replace("\r", '', $md));
        $out = [];
        $inList = false;
        $n = count($lines);
        $i = 0;

        $closeList = function () use (&$inList, &$out) {
            if ($inList) {
                $out[] = '</ul>';
                $inList = false;
            }
        };

        while ($i < $n) {
            $line = rtrim($lines[$i]);

            // Tableau : | ... | suivi d'une ligne |---|
            if (str_starts_with(ltrim($line), '|')
                && $i + 1 < $n && preg_match('/^\s*\|[\s:|-]+\|\s*$/', $lines[$i + 1])) {
                $closeList();
                $header = array_map('trim', explode('|', trim(trim($line), '|')));
                $i += 2;
                $rows = [];
                while ($i < $n && str_starts_with(ltrim($lines[$i]), '|')) {
                    $rows[] = array_map('trim', explode('|', trim(trim($lines[$i]), '|')));
                    $i++;
                }
                $out[] = '<table><tr>'
                    .implode('', array_map(fn ($h) => '<th>'.self::inline($h).'</th>', $header))
                    .'</tr>';
                foreach ($rows as $r) {
                    $cells = '';
                    foreach ($r as $c) {
                        $cls = self::isNum($c) ? ' class="num"' : '';
                        $cells .= "<td{$cls}>".self::inline($c).'</td>';
                    }
                    $out[] = "<tr>{$cells}</tr>";
                }
                $out[] = '</table>';

                continue;
            }

            if (trim($line) === '') {
                $closeList();
                $i++;

                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.*)/', $line, $m)) {
                $closeList();
                $lvl = strlen($m[1]);
                $out[] = "<h{$lvl}>".self::inline($m[2])."</h{$lvl}>";
                $i++;

                continue;
            }

            if (trim($line) === '---') {
                $closeList();
                $out[] = '<hr>';
                $i++;

                continue;
            }

            if (str_starts_with(ltrim($line), '>')) {
                $closeList();
                $out[] = '<blockquote>'.self::inline(trim(ltrim($line, '> '))).'</blockquote>';
                $i++;

                continue;
            }

            if (preg_match('/^\s*-\s+(.*)/', $line, $m)) {
                if (! $inList) {
                    $out[] = '<ul>';
                    $inList = true;
                }
                $item = preg_replace_callback('/^\[( |x|X)\]\s*/', fn ($mm) => $mm[1] === ' ' ? '☐ ' : '☑ ', $m[1]);
                $out[] = '<li>'.self::inline($item).'</li>';
                $i++;

                continue;
            }

            $closeList();
            $out[] = '<p>'.self::inline($line).'</p>';
            $i++;
        }

        $closeList();

        return implode("\n", $out);
    }
}
