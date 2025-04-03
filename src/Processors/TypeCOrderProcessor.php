<?php
namespace App\Processors;

use App\Order;

class TypeCOrderProcessor
{
    public function process(Order $order): void
    {
        if ($order->flag) {
            $order->status = 'completed';
        } else {
            $order->status = 'in_progress';
        }
    }
} 