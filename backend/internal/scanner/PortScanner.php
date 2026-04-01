<?php

class PortScanner
{
    private float $timeout = 0.03; // seconds per port (lightweight scan)

    private array $serviceMap = [
        22 => 'ssh',
        80 => 'http',
        443 => 'https',
        5432 => 'postgresql',
        3306 => 'mysql',
        6379 => 'redis'
    ];

    public function scan(string $target, int $fromPort = 1, int $toPort = 1000): array
    {
        set_time_limit(0);

        $start = microtime(true);

        $openPorts = [];
        $closed = 0;

        for ($port = $fromPort; $port <= $toPort; $port++) {
            $errno = 0;
            $errstr = '';
            $scheme = 'tcp';
            $addr = $target . ':' . $port;

            $sock = @stream_socket_client($scheme . '://' . $addr, $errno, $errstr, $this->timeout);

            if ($sock) {
                fclose($sock);

                $service = $this->serviceMap[$port] ?? getservbyport($port, 'tcp') ?: '';

                $openPorts[] = [
                    'port' => $port,
                    'protocol' => 'tcp',
                    'state' => 'open',
                    'service' => $service,
                    'version' => ''
                ];
            } else {
                $closed++;
            }
        }

        $durationMs = (int)(1000 * (microtime(true) - $start));

        return [
            'ip_address' => $target,
            'open_ports' => $openPorts,
            'closed_ports' => $closed,
            'total_scanned' => $toPort - $fromPort + 1,
            'scan_duration_ms' => $durationMs,
            'created_at' => gmdate('c')
        ];
    }
}