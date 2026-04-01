<?php

class CertTransScanner
{
    public function scan(string $domain): array
    {
        if (empty($domain)) {
            throw new Exception('Domain required for cert_trans scan');
        }

        // Mocked certificate transparency results (demo).
        $record = [
            'domain' => $domain,
            'certificate' => [
                'subject' => 'CN=' . $domain,
                'issuer' => 'CN=Mock CA, O=Mock',
                'serial_number' => 'AA:BB:CC:DD',
                'valid_from' => gmdate('c', strtotime('-30 days')),
                'valid_until' => gmdate('c', strtotime('+330 days')),
                'days_until_expiry' => 330,
                'is_expired' => false,
                'is_self_signed' => false,
                'san' => [$domain, 'www.' . $domain]
            ],
            'created_at' => gmdate('c')
        ];

        return [$record];
    }
}
