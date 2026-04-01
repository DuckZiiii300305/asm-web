<?php

class TechScanner
{
    private int $timeout = 8;

    public function scan(string $domain): array
    {
        $headers = [];
        $meta = [];
        $technologies = [];

        $schemeCandidates = ['https', 'http'];
        $html = null;
        $usedUrl = null;

        foreach ($schemeCandidates as $scheme) {
            $url = $scheme . '://' . $domain;

            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'header' => "User-Agent: ASM-API-Scanner/1.0\r\n"
                ]
            ];

            $context = stream_context_create($opts);
            $html = @file_get_contents($url, false, $context);

            if ($html !== false) {
                $usedUrl = $url;
                break;
            }
        }

        if ($usedUrl === null) {
            throw new Exception("Unable to connect to site for tech detection");
        }

        $headersFromServer = @get_headers($usedUrl, 1);
        if (is_array($headersFromServer)) {
            $headers = $headersFromServer;
            // detect basic tech
            if (!empty($headers['Server'])) {
                $technologies[] = [
                    'name' => $headers['Server'],
                    'category' => 'Web Server',
                    'version' => null,
                    'confidence' => 90
                ];
            }
            if (!empty($headers['X-Powered-By'])) {
                $technologies[] = [
                    'name' => $headers['X-Powered-By'],
                    'category' => 'Application Framework',
                    'version' => null,
                    'confidence' => 90
                ];
            }
            if (!empty($headers['CF-RAY']) || !empty($headers['Server']) && str_contains(strtolower($headers['Server']), 'cloudflare')) {
                $technologies[] = [
                    'name' => 'Cloudflare',
                    'category' => 'CDN',
                    'version' => null,
                    'confidence' => 95
                ];
            }
        }

        if (is_string($html)) {
            preg_match_all('/<meta\s+[^>]*name=["\']generator["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $gen);
            if (!empty($gen[1][0])) {
                $meta['generator'] = $gen[1][0];
                $parts = explode(' ', $gen[1][0]);
                $name = $parts[0];
                $ver = isset($parts[1]) ? $parts[1] : null;
                $technologies[] = [
                    'name' => $name,
                    'category' => 'JavaScript Framework',
                    'version' => $ver,
                    'confidence' => 85
                ];
            }

            preg_match_all('/<meta\s+[^>]*name=["\']viewport["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $vp);
            if (!empty($vp[1][0])) {
                $meta['viewport'] = $vp[1][0];
            }
        }

        return [[
            'domain' => $domain,
            'technologies' => $technologies,
            'headers' => $headers,
            'meta_tags' => $meta,
            'created_at' => gmdate('c')
        ]];
    }
}
