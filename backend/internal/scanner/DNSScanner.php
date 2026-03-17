<?php

class DNSScanner
{
    public function scan($domain)
    {
        $records = @dns_get_record($domain);

        if ($records === false || !is_array($records)) {
            // DNS lookup failed or no records;
            // normalize as empty results, no exception thrown here
            return [];
        }

        $results = [];

        foreach ($records as $r) {
            $name = $r["host"] ?? $r["name"] ?? null;
            $value = null;

            if (isset($r["ip"])) {
                $value = $r["ip"];
            } elseif (isset($r["target"])) {
                $value = $r["target"];
            } elseif (isset($r["txt"])) {
                $value = $r["txt"];
            }

            $results[] = [
                "type" => $r["type"] ?? null,
                "name" => $name,
                "value" => $value,
                "ttl" => $r["ttl"] ?? null
            ];
        }

        return $results;
    }
}