<?php
namespace App;

use App\Processors\TypeAOrderProcessor;
use App\Processors\TypeBOrderProcessor;
use App\Processors\TypeCOrderProcessor;

class OrderProcessingService
{
    private $dbService;
    private $apiClient;
    private $typeAProcessor;
    private $typeBProcessor;
    private $typeCProcessor;

    public function __construct(DatabaseService $dbService, APIClient $apiClient)
    {
        $this->dbService = $dbService;
        $this->apiClient = $apiClient;
        $this->typeAProcessor = new TypeAOrderProcessor();
        $this->typeBProcessor = new TypeBOrderProcessor($apiClient);
        $this->typeCProcessor = new TypeCOrderProcessor();
    }

    public function processOrders(int $userId)
    {
        $orders = $this->dbService->getOrdersByUser($userId);

        foreach ($orders as $order) {
            $this->setOrderPriority($order);
            $this->processOrderByType($order);
            $this->updateOrderInDatabase($order);
        }

        return $orders;
    }

    private function setOrderPriority(Order $order): void
    {
        if ($order->amount > 200) {
            $order->priority = 'high';
        } else {
            $order->priority = 'low';
        }
    }

    private function processOrderByType(Order $order): void
    {
        switch ($order->type) {
            case 'A':
                $this->typeAProcessor->process($order);
                break;
            case 'B':
                $this->typeBProcessor->process($order);
                break;
            case 'C':
                $this->typeCProcessor->process($order);
                break;
            default:
                $order->status = 'unknown_type';
                break;
        }
    }

    private function updateOrderInDatabase(Order $order): void
    {
        try {
            $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
        } catch (DatabaseException $e) {
            $order->status = 'db_error';
        }
    }
}
