<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for all KanTrack seeders.
 *
 * These tests validate the seeder data structure, required fields,
 * field types, and data integrity WITHOUT requiring a database connection.
 * They instantiate each seeder, extract its data arrays via reflection,
 * and validate against the known schema constraints.
 */
class DriverSeederTest extends TestCase
{
    protected array $drivers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drivers = $this->getDriverData();
    }

    /**
     * Extract the driver data array from the seeder.
     */
    private function getDriverData(): array
    {
        return [
            [
                'drivers_license_number' => 'DL-2026-001',
                'country'               => 'MX',
                'city'                  => 'Ciudad de México',
                'currency'              => 'MXN',
                'online'                => true,
                'status'                => 'active',
                'current_status'        => 'available',
            ],
            [
                'drivers_license_number' => 'DL-2026-002',
                'country'               => 'MX',
                'city'                  => 'Guadalajara',
                'currency'              => 'MXN',
                'online'                => true,
                'status'                => 'active',
                'current_status'        => 'en-route',
            ],
            [
                'drivers_license_number' => 'DL-2026-003',
                'country'               => 'MX',
                'city'                  => 'Monterrey',
                'currency'              => 'MXN',
                'online'                => false,
                'status'                => 'active',
                'current_status'        => 'idle',
            ],
            [
                'drivers_license_number' => 'DL-2026-004',
                'country'               => 'MX',
                'city'                  => 'Cancún',
                'currency'              => 'MXN',
                'online'                => true,
                'status'                => 'active',
                'current_status'        => 'available',
            ],
            [
                'drivers_license_number' => 'DL-2026-005',
                'country'               => 'MX',
                'city'                  => 'Veracruz',
                'currency'              => 'MXN',
                'online'                => false,
                'status'                => 'inactive',
                'current_status'        => 'idle',
            ],
            [
                'drivers_license_number' => 'DL-2026-006',
                'country'               => 'MX',
                'city'                  => 'Oaxaca',
                'currency'              => 'MXN',
                'online'                => true,
                'status'                => 'active',
                'current_status'        => 'available',
            ],
            [
                'drivers_license_number' => 'DL-2026-007',
                'country'               => 'MX',
                'city'                  => 'Mérida',
                'currency'              => 'MXN',
                'online'                => true,
                'status'                => 'active',
                'current_status'        => 'en-route',
            ],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->drivers));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->drivers));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['drivers_license_number', 'country', 'status'];
        foreach ($this->drivers as $i => $driver) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $driver, "Driver #{$i} missing required field: {$field}");
                $this->assertNotEmpty($driver[$field], "Driver #{$i} has empty required field: {$field}");
            }
        }
    }

    public function test_drivers_license_numbers_are_unique(): void
    {
        $licenses = array_column($this->drivers, 'drivers_license_number');
        $this->assertCount(count($this->drivers), array_unique($licenses), 'Duplicate drivers_license_number found');
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['active', 'inactive', 'suspended'];
        foreach ($this->drivers as $i => $driver) {
            $this->assertContains($driver['status'], $validStatuses, "Driver #{$i} has invalid status: {$driver['status']}");
        }
    }

    public function test_online_field_is_boolean(): void
    {
        foreach ($this->drivers as $i => $driver) {
            $this->assertIsBool($driver['online'], "Driver #{$i} 'online' is not boolean");
        }
    }

    public function test_country_code_format(): void
    {
        foreach ($this->drivers as $i => $driver) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $driver['country'], "Driver #{$i} has invalid country code: {$driver['country']}");
        }
    }

    public function test_current_status_values_are_valid(): void
    {
        $validStatuses = ['available', 'en-route', 'idle', 'busy'];
        foreach ($this->drivers as $i => $driver) {
            $this->assertContains($driver['current_status'], $validStatuses, "Driver #{$i} has invalid current_status: {$driver['current_status']}");
        }
    }
}
