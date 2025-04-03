<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Order;
use App\OrderProcessingService;
use App\DatabaseService;
use App\APIClient;
use App\APIResponse;
use App\DatabaseException;
use App\APIException;

class OrderProcessingServiceTest extends TestCase
{
    private $dbServiceMock;
    private $apiClientMock;
    private $orderProcessingService;

    protected function setUp(): void
    {
        $this->dbServiceMock = $this->createMock(DatabaseService::class);
        $this->apiClientMock = $this->createMock(APIClient::class);
        $this->orderProcessingService = new OrderProcessingService(
            $this->dbServiceMock,
            $this->apiClientMock
        );
    }

    public function testProcessTypeAOrder()
    {
        // Arrange
        $order = new Order(1, 'A', 100, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'exported', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('exported', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeAOrderWithHighValue()
    {
        // Arrange
        $order = new Order(1, 'A', 201, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'exported', 'high')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('exported', $result[0]->status);
        $this->assertEquals('high', $result[0]->priority);
    }

    public function testProcessTypeBOrderSuccess()
    {
        // Arrange
        $order = new Order(1, 'B', 80, false);
        $apiResponse = new APIResponse('success', $order);
        $apiResponse->data = 60;

        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientMock->expects($this->once())
            ->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'processed', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('processed', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeBOrderWithLowAPIResponse()
    {
        // Arrange
        $order = new Order(1, 'B', 80, false);
        $apiResponse = new APIResponse('success', $order);
        $apiResponse->data = 40;

        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientMock->expects($this->once())
            ->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'pending', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('pending', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeBOrderWithFlag()
    {
        // Arrange
        $order = new Order(1, 'B', 80, true);
        $apiResponse = new APIResponse('success', $order);
        $apiResponse->data = 60;

        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientMock->expects($this->once())
            ->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'processed', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('processed', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeBOrderWithAPIError()
    {
        // Arrange
        $order = new Order(1, 'B', 80, false);
        $apiResponse = new APIResponse('error', $order);
        $apiResponse->data = 60;

        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientMock->expects($this->once())
            ->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'api_error', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('api_error', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeCOrderWithFlag()
    {
        // Arrange
        $order = new Order(1, 'C', 250, true);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'completed', 'high')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('completed', $result[0]->status);
        $this->assertEquals('high', $result[0]->priority);
    }

    public function testProcessTypeCOrderWithoutFlag()
    {
        // Arrange
        $order = new Order(1, 'C', 150, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'in_progress', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('in_progress', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessUnknownTypeOrder()
    {
        // Arrange
        $order = new Order(1, 'D', 100, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'unknown_type', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        if ($result === false) {
            $this->fail('processOrders returned false instead of an array');
        }
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('unknown_type', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessEmptyOrders()
    {
        // Arrange
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([]);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        if ($result === false) {
            $this->fail('processOrders returned false instead of an array');
        }
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDatabaseException()
    {
        // Arrange
        $order = new Order(1, 'A', 100, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->willThrowException(new DatabaseException());

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        if ($result === false) {
            $this->fail('processOrders returned false instead of an array');
        }
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('db_error', $result[0]->status);
    }

    public function testAPIException()
    {
        // Arrange
        $order = new Order(1, 'B', 100, false);
        $this->dbServiceMock->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientMock->expects($this->once())
            ->method('callAPI')
            ->willThrowException(new APIException());

        $this->dbServiceMock->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'api_failure', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        if ($result === false) {
            $this->fail('processOrders returned false instead of an array');
        }
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('api_failure', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }
}
