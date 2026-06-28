<?php

use Valet\Drivers\ValetDriver;

/**
 * Local Valet driver for The Heart of Jerome.
 *
 * Serves the built React SPA from this web root, routes /api/*.php to the
 * sibling api/ folder (executed by Valet's PHP-FPM), serves uploaded media
 * from the sibling uploads/ folder, and falls back to index.html for all
 * client-side routes. Only used by Valet locally — ignored on Hostinger.
 */
class LocalValetDriver extends ValetDriver
{
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return true;
    }

    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        // Uploaded media lives one level up from the web root (persists across builds).
        if (str_starts_with($uri, '/uploads/')) {
            $candidate = dirname($sitePath) . $uri;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Built assets (JS/CSS/logo). Never serve a .php file as a static asset.
        if ($uri !== '/' && ! str_ends_with($uri, '.php')) {
            $path = $sitePath . $uri;
            if (is_file($path)) {
                return $path;
            }
        }

        return false;
    }

    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        // API → execute the matching PHP file in the sibling api/ folder.
        if (preg_match('#^(/api/[A-Za-z0-9_/-]+\.php)$#', $uri, $m)) {
            $script = dirname($sitePath) . $m[1];
            if (is_file($script)) {
                $_SERVER['SCRIPT_FILENAME'] = $script;
                $_SERVER['SCRIPT_NAME'] = $m[1];
                $_SERVER['DOCUMENT_ROOT'] = dirname($sitePath);

                return $script;
            }
        }

        // Everything else is a client-side route → serve the SPA shell.
        return $sitePath . '/index.html';
    }
}
