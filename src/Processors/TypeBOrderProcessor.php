<?php
namespace App\Processors;

use App\Order;
use App\APIClient;
use App\APIException;

class TypeBOrderProcessor
{
    private $apiClient;

    public function __construct(APIClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function process(Order $order): void
    {
        try {
            $apiResponse = $this->apiClient->callAPI($order->id);
            $this->updateOrderStatus($order, $apiResponse);
        } catch (APIException $e) {
            $order->status = 'api_failure';
        }
    }

    private function updateOrderStatus(Order $order, $apiResponse): void
    {
        if ($apiResponse->status === 'success') {
            if ($apiResponse->data >= 50 && $order->amount < 100) {
                $order->status = 'processed';
            } elseif ($apiResponse->data < 50 || $order->flag) {
                $order->status = 'pending';
            } else {
                $order->status = 'error';
            }
        } else {
            $order->status = 'api_error';
        }
    }
} 