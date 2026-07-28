<?php

namespace App\Services\Licenses;

class SimplePdfDocument
{
    /** @param array<int, string> $lines */
    public function render(array $lines, string $title): string
    {
        $pages = array_chunk($this->wrapLines($lines, 92), 46);
        $objects = [];
        $pageIds = [];
        $fontId = 3;
        $nextId = 4;

        foreach ($pages as $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;

            $stream = "BT\n/F1 11 Tf\n54 756 Td\n14 TL\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $stream .= "T*\n";
                }
                $stream .= '('.$this->escape($line).") Tj\n";
            }
            $stream .= "ET";

            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $kids = implode(' ', array_map(fn (int $id): string => "{$id} 0 R", $pageIds));
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($pageIds).' >>';
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 ".($maxId + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R /Info << /Title (".$this->escape($title).") >> >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /** @param array<int, string> $lines @return array<int, string> */
    private function wrapLines(array $lines, int $width): array
    {
        $result = [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
            if ($line === '') {
                $result[] = '';
                continue;
            }
            foreach (explode("\n", wordwrap($line, $width, "\n", true)) as $wrapped) {
                $result[] = $wrapped;
            }
        }
        return $result ?: [''];
    }

    private function escape(string $value): string
    {
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $value);
    }
}
