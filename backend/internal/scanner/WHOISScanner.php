<?php

class WHOISScanner
{
    private int $timeout = 10;

    public function scan(string $domain): array
    {
        $server = $this->getWhoisServer($domain);

        $fp = fsockopen($server, 43, $errno, $errstr, $this->timeout);

        if (!$fp) {
            throw new Exception("WHOIS connection failed");
        }

        fwrite($fp, $domain . "\r\n");

        $response = "";

        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }

        fclose($fp);

        return $this->parseWhois($response);
    }

    private function getWhoisServer($domain): string
    {
        $parts = explode(".", $domain);
        $tld = end($parts);

        $servers = [
            "com" => "whois.verisign-grs.com",
            "net" => "whois.verisign-grs.com",
            "org" => "whois.pir.org",
            "io" => "whois.nic.io",
            "dev" => "whois.nic.google",
            "app" => "whois.nic.google",
        ];

        return $servers[$tld] ?? "whois.nic.$tld";
    }

    private function parseWhois(string $raw): array
    {
        $lines = explode("\n", $raw);

        $data = [
            "registrar" => null,
            "created_date" => null,
            "expiry_date" => null,
            "name_servers" => [],
            "status" => [],
            "emails" => [],
            "raw_data" => $raw
        ];

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === "") continue;

            if (!str_contains($line, ":")) continue;

            [$key, $value] = explode(":", $line, 2);

            $key = strtolower(trim($key));
            $value = trim($value);

            if (str_contains($key, "registrar")) {
                $data["registrar"] = $value;
            }

            if (str_contains($key, "creation")) {
                $data["created_date"] = $value;
            }

            if (str_contains($key, "expir")) {
                $data["expiry_date"] = $value;
            }

            if (str_contains($key, "name server")) {
                $data["name_servers"][] = $value;
            }

            if (str_contains($key, "status")) {
                $data["status"][] = $value;
            }

            if (str_contains($key, "email")) {
                $data["emails"][] = $value;
            }
        }

        return $data;
    }
}