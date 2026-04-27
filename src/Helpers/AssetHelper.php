<?php

namespace Darejer\Helpers;

class AssetHelper
{
    /**
     * Read the compiled Vite manifest from public/vendor/darejer/manifest.json
     * and return the correct <link> and <script> HTML tags.
     */
    public static function tags(): string
    {
        $manifestPath = public_path('vendor/darejer/manifest.json');

        if (! file_exists($manifestPath)) {
            return '<!-- Darejer assets not published. Run: php artisan vendor:publish --tag=darejer-assets -->';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $entry = $manifest['resources/js/app.ts'] ?? null;

        if (! $entry) {
            return '<!-- Darejer manifest entry not found -->';
        }

        $html = '';

        foreach ($entry['css'] ?? [] as $cssFile) {
            $url = asset("vendor/darejer/{$cssFile}");
            $html .= "<link rel=\"stylesheet\" href=\"{$url}\">\n    ";
        }

        foreach ($entry['imports'] ?? [] as $chunkKey) {
            $chunk = $manifest[$chunkKey] ?? null;
            if ($chunk) {
                $url = asset("vendor/darejer/{$chunk['file']}");
                $html .= "<script type=\"module\" src=\"{$url}\"></script>\n    ";
            }
        }

        $url = asset("vendor/darejer/{$entry['file']}");
        $html .= "<script type=\"module\" src=\"{$url}\"></script>";

        return $html;
    }
}
