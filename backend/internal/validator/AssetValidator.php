<?php

class AssetValidator
{
    public static function validateNameByType($name, $type)
    {
        if ($type === "domain") {
            return self::validateDomain($name);
        }

        if ($type === "ip") {
            return self::validateIP($name);
        }

        if ($type === "service") {
            return self::validateService($name);
        }

        return false;
    }

    public static function validateDomain($domain)
    {
        return preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain);
    }

    public static function validateIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function validateService($service)
    {
        return preg_match('/^[a-zA-Z0-9:\/.-]+$/', $service);
    }
}