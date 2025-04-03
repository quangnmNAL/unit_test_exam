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
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class OrderProcessingServiceTest extends TestCase
{
    /** @var DatabaseService|MockObject */
    private $dbServiceSpy;

    /** @var APIClient|Stub */
    private $apiClientStub;

    private OrderProcessingService $orderProcessingService;
    private MockBuilder $mockBuilder;

    protected function setUp(): void
    {
        // Create a spy for DatabaseService to track method calls
        $this->dbServiceSpy = $this->createMock(DatabaseService::class);
        
        // Create a stub for APIClient to simulate responses
        $this->apiClientStub = $this->createStub(APIClient::class);
        
        $this->orderProcessingService = new OrderProcessingService(
            $this->dbServiceSpy,
            $this->apiClientStub
        );
        
        $this->mockBuilder = new MockBuilder();
    }

    protected function tearDown(): void
    {
        \phpmock\Mock::disableAll();
    }

    public function testProcessTypeAOrder()
    {
        // Arrange
        $order = new Order(1, 'A', 100, false);
        
        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        // Mock fopen to return a valid handle
        $mock = $this->mockBuilder
            ->setNamespace('App\Processors')
            ->setName('fopen')
            ->setFunction(function() {
                return fopen('php://temp', 'r+');
            })
            ->build();
        $mock->enable();

        $this->dbServiceSpy->expects($this->once())
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
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        // Mock fopen to return a valid handle
        $mock = $this->mockBuilder
            ->setNamespace('App\Processors')
            ->setName('fopen')
            ->setFunction(function() {
                return fopen('php://temp', 'r+');
            })
            ->build();
        $mock->enable();

        $this->dbServiceSpy->expects($this->once())
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

    public function testProcessTypeAOrderWithExportFailure()
    {
        // Arrange
        $order = new Order(1, 'A', 100, false);
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        // Mock fopen to return false
        $mock = $this->mockBuilder
            ->setNamespace('App\Processors')
            ->setName('fopen')
            ->setFunction(function() {
                return false;
            })
            ->build();
        $mock->enable();

        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'export_failed', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('export_failed', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeBOrderSuccess()
    {
        // Arrange
        $order = new Order(1, 'B', 80, false);
        $apiResponse = new APIResponse('success', $order);
        $apiResponse->data = 60;

        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        // Set up stub behavior
        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceSpy->expects($this->once())
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

        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceSpy->expects($this->once())
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

        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceSpy->expects($this->once())
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

        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        // Set up stub behavior
        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceSpy->expects($this->once())
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

    public function testProcessTypeBOrderWithAPIException()
    {
        // Arrange
        $order = new Order(1, 'B', 80, false);

        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        // Set up stub to throw exception
        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willThrowException(new APIException('API Error'));

        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'api_failure', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('api_failure', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeCOrderWithFlag()
    {
        // Arrange
        $order = new Order(1, 'C', 100, true);

        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'completed', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('completed', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessTypeCOrderWithoutFlag()
    {
        // Arrange
        $order = new Order(1, 'C', 150, false);
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceSpy->expects($this->once())
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

    public function testProcessTypeBOrderWithHighAmountAndHighAPIResponse()
    {
        // Arrange
        $order = new Order(1, 'B', 150, false);
        $apiResponse = new APIResponse('success', $order);
        $apiResponse->data = 60;

        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->apiClientStub->method('callAPI')
            ->with(1)
            ->willReturn($apiResponse);

        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'error', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('error', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessUnknownTypeOrder()
    {
        // Arrange
        $order = new Order(1, 'D', 100, false);

        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([$order]);

        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'unknown_type', 'low')
            ->willReturn(true);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('unknown_type', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }

    public function testProcessEmptyOrders()
    {
        // Arrange
        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willReturn([]);

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDatabaseException()
    {
        // Arrange
        // Set up spy to throw exception
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->willThrowException(new DatabaseException('Database Error'));

        // Act & Assert
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database Error');
        
        $this->orderProcessingService->processOrders(1);
    }

    public function testDatabaseExceptionOnUpdateOrderStatus()
    {
        // Arrange
        $order = new Order(1, 'A', 100, false);
        
        // Set up spy expectations
        $this->dbServiceSpy->expects($this->once())
            ->method('getOrdersByUser')
            ->with(1)
            ->willReturn([$order]);

        // Mock fopen to return a valid handle
        $mock = $this->mockBuilder
            ->setNamespace('App\Processors')
            ->setName('fopen')
            ->setFunction(function() {
                return fopen('php://temp', 'r+');
            })
            ->build();
        $mock->enable();

        // Set up spy to throw exception on updateOrderStatus
        $this->dbServiceSpy->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1, 'exported', 'low')
            ->willThrowException(new DatabaseException('Update failed'));

        // Act
        $result = $this->orderProcessingService->processOrders(1);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('db_error', $result[0]->status);
        $this->assertEquals('low', $result[0]->priority);
    }
}
