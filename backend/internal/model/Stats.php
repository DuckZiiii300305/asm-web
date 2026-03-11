<?php

class AssetStats
{
    public int $total;
    public array $by_type;
    public array $by_status;

    public function __construct($total, $by_type, $by_status)
    {
        $this->total = $total;
        $this->by_type = $by_type;
        $this->by_status = $by_status;
    }

    public function toArray()
    {
        return [
            "total" => $this->total,
            "by_type" => $this->by_type,
            "by_status" => $this->by_status
        ];
    }
}