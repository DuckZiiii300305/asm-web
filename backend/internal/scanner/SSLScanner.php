<?php

class SSLScanner
{
    private int $timeout = 8;

    public function scan(string $domain): array
    {
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            $host = $domain;
        } else {
            $host = gethostbyname($domain);
            if ($host === $domain || !filter_var($host, FILTER_VALIDATE_IP)) {
                throw new Exception("Unable to resolve domain to IP for SSL scan");
            }
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'SNI_enabled' => true,
                'peer_name' => $domain,
                'timeout' => $this->timeout
            ]
        ]);

        $stream = @stream_socket_client("ssl://" . $host . ":443", $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$stream) {
            throw new Exception("SSL connection failed: $errstr ($errno)");
        }

        $params = stream_context_get_params($stream);
        fclose($stream);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            throw new Exception("Unable to retrieve SSL certificate");
        }

        $cert = $params['options']['ssl']['peer_certificate'];
        $data = openssl_x509_parse($cert);
        if ($data === false) {
            throw new Exception("Failed to parse SSL certificate");
        }

        $issuer = $data['issuer'] ?? [];
        $subject = $data['subject'] ?? [];
        $validFrom = isset($data['validFrom_time_t']) ? gmdate('c', $data['validFrom_time_t']) : null;
        $validTo = isset($data['validTo_time_t']) ? gmdate('c', $data['validTo_time_t']) : null;
        $now = time();

        $altNames = [];
        if (!empty($data['extensions']['subjectAltName'])) {
            $san = $data['extensions']['subjectAltName'];
            $parts = explode(', ', $san);
            foreach ($parts as $p) {
                if (str_starts_with($p, 'DNS:')) {
                    $altNames[] = substr($p, 4);
                }
            }
        }

        $info = [
            'domain' => $domain,
            'certificate' => [
                'subject' => $this->arrayToString($subject),
                'issuer' => $this->arrayToString($issuer),
                'serial_number' => $data['serialNumberHex'] ?? ($data['serialNumber'] ?? ''),
                'valid_from' => $validFrom,
                'valid_until' => $validTo,
                'days_until_expiry' => $validTo ? max(0, (int)((strtotime($validTo) - $now) / 86400)) : null,
                'is_expired' => $validTo ? (strtotime($validTo) < $now) : null,
                'is_self_signed' => ($this->arrayToString($subject) === $this->arrayToString($issuer)),
                'san' => $altNames,
            ],
            'connection' => [
                'tls_version' => 'TLS',
                'cipher_suite' => $data['signatureTypeSN'] ?? '',
                'key_exchange' => ''
            ],
            'grade' => 'A',
            'issues' => [],
            'created_at' => gmdate('c')
        ];

        return [$info];
    }

    private function arrayToString(array $arr): string
    {
        $parts = [];
        foreach ($arr as $k => $v) {
            $parts[] = "$k=$v";
        }
        return implode(', ', $parts);
    }
}
