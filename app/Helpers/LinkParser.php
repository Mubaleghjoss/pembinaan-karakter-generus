<?php

namespace App\Helpers;

class LinkParser
{
    /**
     * Common TLDs for domain detection
     */
    private static array $tlds = [
        'com', 'org', 'net', 'edu', 'gov', 'mil', 'int',
        'co', 'io', 'ai', 'app', 'dev', 'me', 'info', 'biz',
        'id', 'co.id', 'or.id', 'ac.id', 'go.id', 'web.id',
        'uk', 'de', 'fr', 'jp', 'cn', 'au', 'ca', 'br', 'in',
        'ru', 'nl', 'es', 'it', 'pl', 'se', 'no', 'fi', 'dk',
        'xyz', 'online', 'site', 'tech', 'store', 'blog',
    ];

    /**
     * Parse text and convert URLs/domains to clickable hyperlinks
     */
    public static function parse(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // First, handle URLs with protocol (http:// or https://)
        $text = preg_replace_callback(
            '/\b(https?:\/\/[^\s<>"\']+)/i',
            function ($matches) {
                $url = rtrim($matches[1], '.,;:!?)');
                return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline break-all">' . htmlspecialchars($url) . '</a>';
            },
            $text
        );

        // Then, handle domains without protocol (e.g., google.com, example.co.id)
        $tldsPattern = implode('|', array_map(function ($tld) {
            return preg_quote($tld, '/');
        }, self::$tlds));

        $text = preg_replace_callback(
            '/\b(?<![:\/])([a-zA-Z0-9][-a-zA-Z0-9]*\.)+(' . $tldsPattern . ')(\b|\/[^\s<>"\']*)?/i',
            function ($matches) {
                $domain = rtrim($matches[0], '.,;:!?)');
                
                // Skip if already inside an href or if it looks like it's part of an existing link
                if (strpos($domain, 'href=') !== false) {
                    return $matches[0];
                }
                
                $url = 'https://' . $domain;
                return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline break-all">' . htmlspecialchars($domain) . '</a>';
            },
            $text
        );

        return $text;
    }

    /**
     * Check if text contains any URL or domain
     */
    public static function containsUrl(string $text): bool
    {
        // Check for URLs with protocol
        if (preg_match('/https?:\/\/[^\s]+/i', $text)) {
            return true;
        }

        // Check for domains without protocol
        $tldsPattern = implode('|', array_map(function ($tld) {
            return preg_quote($tld, '/');
        }, self::$tlds));

        return (bool) preg_match('/\b([a-zA-Z0-9][-a-zA-Z0-9]*\.)+(' . $tldsPattern . ')\b/i', $text);
    }

    /**
     * Extract all URLs from text
     */
    public static function extractUrls(string $text): array
    {
        $urls = [];

        // Extract URLs with protocol
        preg_match_all('/https?:\/\/[^\s<>"\']+/i', $text, $matches);
        $urls = array_merge($urls, $matches[0]);

        // Extract domains without protocol
        $tldsPattern = implode('|', array_map(function ($tld) {
            return preg_quote($tld, '/');
        }, self::$tlds));

        preg_match_all('/\b(?<![:\/])([a-zA-Z0-9][-a-zA-Z0-9]*\.)+(' . $tldsPattern . ')(\b|\/[^\s<>"\']*)?/i', $text, $matches);
        
        foreach ($matches[0] as $domain) {
            if (!in_array($domain, $urls) && strpos($domain, 'href=') === false) {
                $urls[] = $domain;
            }
        }

        return array_unique($urls);
    }
}
