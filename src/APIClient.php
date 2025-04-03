<?php
namespace App;

interface APIClient
{
    public function callAPI($orderId): APIResponse;
} 