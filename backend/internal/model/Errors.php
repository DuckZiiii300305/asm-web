<?php

class DomainError extends Exception {}

class Errors
{
    public static function NotFound()
    {
        return new DomainError("asset not found", 404);
    }

    public static function InvalidInput()
    {
        return new DomainError("invalid input", 400);
    }

    public static function Duplicate()
    {
        return new DomainError("asset already exists", 409);
    }

    public static function EmptyName()
    {
        return new DomainError("name is required", 400);
    }

    public static function InvalidType()
    {
        return new DomainError(
            "invalid asset type: must be domain, ip, or service",
            400
        );
    }

    public static function InvalidStatus()
    {
        return new DomainError(
            "invalid status: must be active or inactive",
            400
        );
    }
}