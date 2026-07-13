<?php

declare(strict_types=1);

namespace Platform\Billing\Support;

final class SimplePdfBuilder
{
    /**
     * @param  list<string>  $lines
     */
    public function build(string $title, array $lines): string
    {
        $stream = "BT\n/F1 12 Tf\n";
        $y = 750;
        $stream .= '50 '.$y." Td (".$this->escape($title).") Tj\n";
        $y -= 24;

        foreach ($lines as $line) {
            $stream .= '0 -16 Td ('.$this->escape($line).") Tj\n";
            $y -= 16;
        }

        $stream .= "ET\n";
        $length = strlen($stream);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = "<< /Length {$length} >>\nstream\n{$stream}endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
