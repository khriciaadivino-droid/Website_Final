<?php

namespace App\Tests\Entity;

use App\Entity\Shipment;
use PHPUnit\Framework\TestCase;

class ShipmentTest extends TestCase
{
    public function testShipmentCodeGeneration(): void
    {
        $shipment = new Shipment();
        
        // Simulate the PrePersist lifecycle callback
        $reflection = new \ReflectionClass($shipment);
        $method = $reflection->getMethod('onPrePersist');
        $method->setAccessible(true);
        $method->invoke($shipment);
        
        // Verify code was generated
        $this->assertNotNull($shipment->getCode());
        $this->assertStringStartsWith('SHP-', $shipment->getCode());
        
        // Verify code format matches SHP-YYYYMMDD-XXXX
        $this->assertMatchesRegularExpression('/^SHP-\d{8}-\d{4}$/', $shipment->getCode());
        
        // Verify timestamps were set
        $this->assertNotNull($shipment->getCreatedAt());
        $this->assertNotNull($shipment->getShipmentDate());
        $this->assertNotNull($shipment->getUpdatedAt());
        
        // Verify default status
        $this->assertEquals('pending', $shipment->getStatus());
    }

    public function testShipmentGetstersSetters(): void
    {
        $shipment = new Shipment();
        
        $now = new \DateTime();
        $shipment->setCode('SHP-20260326-0001');
        $shipment->setTrackingNumber('TRACK123456789');
        $shipment->setStatus('shipped');
        $shipment->setShipmentDate($now);
        $shipment->setDeliveryDate($now);
        $shipment->setOrigin('New York');
        $shipment->setDestination('Los Angeles');
        $shipment->setWeight(5.5);
        $shipment->setCarrier('FedEx');
        $shipment->setNotes('Fragile items');
        $shipment->setCreatedAt($now);
        $shipment->setUpdatedAt($now);
        
        $this->assertEquals('SHP-20260326-0001', $shipment->getCode());
        $this->assertEquals('TRACK123456789', $shipment->getTrackingNumber());
        $this->assertEquals('shipped', $shipment->getStatus());
        $this->assertEquals('New York', $shipment->getOrigin());
        $this->assertEquals('Los Angeles', $shipment->getDestination());
        $this->assertEquals(5.5, $shipment->getWeight());
        $this->assertEquals('FedEx', $shipment->getCarrier());
        $this->assertEquals('Fragile items', $shipment->getNotes());
    }

    public function testMultipleShipmentsHaveDifferentCodes(): void
    {
        $shipment1 = new Shipment();
        $shipment2 = new Shipment();
        
        // Simulate PrePersist
        $reflection = new \ReflectionClass($shipment1);
        $method = $reflection->getMethod('onPrePersist');
        $method->setAccessible(true);
        $method->invoke($shipment1);
        
        $reflection = new \ReflectionClass($shipment2);
        $method = $reflection->getMethod('onPrePersist');
        $method->setAccessible(true);
        $method->invoke($shipment2);
        
        // Codes should be different (randomized number part)
        $this->assertNotEquals($shipment1->getCode(), $shipment2->getCode());
    }
}
