<?php
namespace App\Processors;

use App\Order;

class TypeAOrderProcessor
{
    public function process(Order $order): void
    {
        $this->exportToCSV($order);
    }

    private function exportToCSV(Order $order): void
    {
        $csvFile = 'orders_type_A_' . $order->id . '_' . time() . '.csv';
        $fileHandle = fopen($csvFile, 'w');
        
        if ($fileHandle !== false) {
            $this->writeCSVHeader($fileHandle);
            $this->writeOrderData($fileHandle, $order);
            
            if ($order->amount > 150) {
                $this->writeHighValueNote($fileHandle);
            }

            fclose($fileHandle);
            $order->status = 'exported';
        } else {
            $order->status = 'export_failed';
        }
    }

    private function writeCSVHeader($fileHandle): void
    {
        fputcsv($fileHandle, ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority']);
    }

    private function writeOrderData($fileHandle, Order $order): void
    {
        fputcsv($fileHandle, [
            $order->id,
            $order->type,
            $order->amount,
            $order->flag ? 'true' : 'false',
            $order->status,
            $order->priority
        ]);
    }

    private function writeHighValueNote($fileHandle): void
    {
        fputcsv($fileHandle, ['', '', '', '', 'Note', 'High value order']);
    }
}

