<?php

class SubdomainScanner
{
    private array $wordlist = [];
    private int $timeout = 5;
    private int $rateLimit = 100;

    public function __construct()
    {
        $this->loadWordlist();
    }

    private function loadWordlist(): void
    {
        $file = __DIR__ . "/wordlists/subdomains.txt";

        if (!file_exists($file)) {
            $this->wordlist = $this->defaultWordlist();
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === "" || str_starts_with($line, "#")) {
                continue;
            }

            $this->wordlist[] = $line;
        }

        if (count($this->wordlist) === 0) {
            $this->wordlist = $this->defaultWordlist();
        }
    }

    public function scan(string $domain): array
    {
        set_time_limit(0);

        $results = [];

        foreach ($this->wordlist as $word) {

            $subdomain = $word . "." . $domain;

            $records = @dns_get_record($subdomain, DNS_A);

            if ($records !== false && count($records) > 0) {

                $results[] = [
                    "name" => $subdomain,
                    "source" => "dns_bruteforce",
                    "is_active" => true
                ];
            }

            usleep(intval(1000000 / $this->rateLimit));
        }

        return $results;
    }

    private function defaultWordlist(): array
    {
        return [
            "www",
            "api",
            "mail",
            "dev",
            "test",
            "staging",
            "admin",
            "portal",
            "dashboard",
            "cdn",
            "static",
            "assets",
            "img",
            "files",
            "ftp",
            "vpn",
            "db",
            "mysql",
            "redis",
            "monitor",
            "status",
            "grafana"
        ];
    }
}