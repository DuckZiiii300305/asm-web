<?php

class Asset
{
    public $id;
    public $name;
    public $type;
    public $status;
    public $created_at;
    public $updated_at;
}

class AssetType
{
    const DOMAIN = "domain";
    const IP = "ip";
    const SERVICE = "service";

    public static function isValid($type)
    {
        return in_array($type, [
            self::DOMAIN,
            self::IP,
            self::SERVICE
        ]);
    }
}

class AssetStatus
{
    const ACTIVE = "active";
    const INACTIVE = "inactive";

    public static function isValid($status)
    {
        return in_array($status, [
            self::ACTIVE,
            self::INACTIVE
        ]);
    }
}