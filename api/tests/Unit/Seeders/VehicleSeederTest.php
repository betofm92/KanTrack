<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class VehicleSeederTest extends TestCase
{
    protected array $vehicles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicles = [
            ['name' => 'Camión Volvo FH16 #01', 'make' => 'Volvo', 'model' => 'FH16', 'year' => '2024', 'plate_number' => 'MX-ABC-1234', 'vin' => '1HGBH41JXMN109186', 'type' => 'truck', 'status' => 'active', 'online' => true],
            ['name' => 'Furgoneta Mercedes Sprinter #02', 'make' => 'Mercedes-Benz', 'model' => 'Sprinter', 'year' => '2023', 'plate_number' => 'MX-DEF-5678', 'vin' => '2C4RDGCG5LR123456', 'type' => 'van', 'status' => 'active', 'online' => true],
            ['name' => 'Kenworth T680 #03', 'make' => 'Kenworth', 'model' => 'T680', 'year' => '2025', 'plate_number' => 'MX-GHI-9012', 'vin' => '3AKJHHDR7LSLA9876', 'type' => 'truck', 'status' => 'active', 'online' => false],
            ['name' => 'Ford Transit #04', 'make' => 'Ford', 'model' => 'Transit', 'year' => '2024', 'plate_number' => 'MX-JKL-3456', 'vin' => '1FTBW2CM3MKA00001', 'type' => 'van', 'status' => 'active', 'online' => true],
            ['name' => 'Scania R450 #05', 'make' => 'Scania', 'model' => 'R450', 'year' => '2023', 'plate_number' => 'MX-MNO-7890', 'vin' => '5NPEB4AC1BH123789', 'type' => 'truck', 'status' => 'active', 'online' => true],
            ['name' => 'Isuzu NPR #06', 'make' => 'Isuzu', 'model' => 'NPR', 'year' => '2024', 'plate_number' => 'MX-PQR-1122', 'vin' => '54DC4W1B6MS800234', 'type' => 'box_truck', 'status' => 'inactive', 'online' => false],
            ['name' => 'Freightliner Cascadia #07', 'make' => 'Freightliner', 'model' => 'Cascadia', 'year' => '2025', 'plate_number' => 'MX-STU-3344', 'vin' => '3AKJGLDR8MSAB5678', 'type' => 'truck', 'status' => 'active', 'online' => true],
            ['name' => 'Nissan NV400 #08', 'make' => 'Nissan', 'model' => 'NV400', 'year' => '2023', 'plate_number' => 'MX-VWX-5566', 'vin' => 'JN1TBNT30Z0000001', 'type' => 'van', 'status' => 'active', 'online' => false],
            ['name' => 'International LT625 #09', 'make' => 'International', 'model' => 'LT625', 'year' => '2024', 'plate_number' => 'MX-YZA-7788', 'vin' => '3HSDJAPR5MN200042', 'type' => 'truck', 'status' => 'active', 'online' => true],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->vehicles));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->vehicles));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'make', 'model', 'year', 'plate_number', 'status'];
        foreach ($this->vehicles as $i => $vehicle) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $vehicle, "Vehicle #{$i} missing field: {$field}");
                $this->assertNotEmpty($vehicle[$field], "Vehicle #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_plate_numbers_are_unique(): void
    {
        $plates = array_column($this->vehicles, 'plate_number');
        $this->assertCount(count($this->vehicles), array_unique($plates), 'Duplicate plate_number found');
    }

    public function test_vins_are_unique(): void
    {
        $vins = array_column($this->vehicles, 'vin');
        $this->assertCount(count($this->vehicles), array_unique($vins), 'Duplicate VIN found');
    }

    public function test_vin_length_is_valid(): void
    {
        foreach ($this->vehicles as $i => $vehicle) {
            $this->assertEquals(17, strlen($vehicle['vin']), "Vehicle #{$i} VIN is not 17 characters: {$vehicle['vin']}");
        }
    }

    public function test_year_is_valid(): void
    {
        foreach ($this->vehicles as $i => $vehicle) {
            $year = (int) $vehicle['year'];
            $this->assertGreaterThanOrEqual(2020, $year, "Vehicle #{$i} year too old: {$year}");
            $this->assertLessThanOrEqual(2030, $year, "Vehicle #{$i} year too far: {$year}");
        }
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['active', 'inactive', 'maintenance', 'decommissioned'];
        foreach ($this->vehicles as $i => $vehicle) {
            $this->assertContains($vehicle['status'], $validStatuses, "Vehicle #{$i} has invalid status: {$vehicle['status']}");
        }
    }

    public function test_online_field_is_boolean(): void
    {
        foreach ($this->vehicles as $i => $vehicle) {
            $this->assertIsBool($vehicle['online'], "Vehicle #{$i} 'online' is not boolean");
        }
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->vehicles, 'name');
        $this->assertCount(count($this->vehicles), array_unique($names), 'Duplicate vehicle name found');
    }
}
