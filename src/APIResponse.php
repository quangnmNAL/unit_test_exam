<?php
namespace App;

class APIResponse
{
    public $status;
    public $data;

    public function __construct($status, Order $data)
    {
        $this->status = $status;
        $this->data = $data;
    }
} 