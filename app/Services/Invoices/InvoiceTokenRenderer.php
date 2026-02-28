<?php

namespace App\Services\Invoices;

class InvoiceTokenRenderer
{
    public function render(string $html, array $data): string
    {
        $flat = $this->flatten($data);

        foreach ($flat as $key => $value) {
            $raw = (string) $value;

            // Unescaped variant (preferred for HTML blocks like items.rows, logo data URIs if you want)
            $html = str_replace('{!!'.$key.'!!}', $raw, $html);

            // Escaped variants (text-safe)
            $escaped = e($raw);
            $html = str_replace('{{'.$key.'}}', $escaped, $html); // mustache style
            $html = str_replace('{'.$key.'}', $escaped, $html);   // single-brace style ✅ your DB template
        }

        return $html;
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $full = $prefix ? "{$prefix}.{$k}" : $k;
            if (is_array($v)) $out += $this->flatten($v, $full);
            else $out[$full] = $v;
        }
        return $out;
    }
}
