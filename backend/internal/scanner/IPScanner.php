<?php

class IPScanner
{
    private int $timeout = 5;

    public function scan(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new Exception("Invalid IP address");
        }

        $data = [
            "ip_address" => $ip,
            "geolocation" => [
                "country" => null,
                "country_code" => null,
                "city" => null,
                "region" => null,
                "latitude" => null,
                "longitude" => null,
                "isp" => null,
                "org" => null
            ],
            "asn" => [
                "number" => null,
                "name" => null,
                "description" => null
            ],
            "reverse_dns" => null,
            "created_at" => gmdate("c")
        ];

        $reverseDns = @gethostbyaddr($ip);
        if ($reverseDns !== false && $reverseDns !== $ip) {
            $data["reverse_dns"] = $reverseDns;
        } elseif ($reverseDns !== false) {
            $data["reverse_dns"] = null;
        }

        $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,country,countryCode,regionName,city,lat,lon,isp,org,as";

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'header' => "User-Agent: ASM-API-Scanner/1.0\r\n"
            ]
        ]);

        $json = @file_get_contents($url, false, $context);

        if ($json !== false) {
            $parsed = json_decode($json, true);

            if (is_array($parsed) && isset($parsed['status']) && $parsed['status'] === 'success') {
                $data['geolocation'] = [
                    'country' => $parsed['country'] ?? null,
                    'country_code' => $parsed['countryCode'] ?? null,
                    'city' => $parsed['city'] ?? null,
                    'region' => $parsed['regionName'] ?? null,
                    'latitude' => isset($parsed['lat']) ? (float)$parsed['lat'] : null,
                    'longitude' => isset($parsed['lon']) ? (float)$parsed['lon'] : null,
                    'isp' => $parsed['isp'] ?? null,
                    'org' => $parsed['org'] ?? null,
                ];

                if (!empty($parsed['as'])) {
                    // format AS string: "AS13335 Cloudflare, Inc." -> split
                    $as = trim($parsed['as']);
                    if (preg_match('/^AS(\d+)\s+(.*)$/', $as, $m)) {
                        $data['asn'] = [
                            'number' => (int)$m[1],
                            'name' => $m[2],
                            'description' => $m[2]
                        ];
                    }
                }
            }
        }

        return [$data];
    }
}
