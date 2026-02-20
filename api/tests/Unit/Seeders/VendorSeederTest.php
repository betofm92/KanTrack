<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class VendorSeederTest extends TestCase
{
    protected array $vendors;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vendors = [
            ['name' => 'Transportes García S.A. de C.V.', 'email' => 'contacto@transportesgarcia.mx', 'phone' => '+52 55 1234 5678', 'country' => 'MX', 'type' => 'freight', 'status' => 'active'],
            ['name' => 'Logística Reyes', 'email' => 'info@logisticareyes.com', 'phone' => '+52 81 9876 5432', 'country' => 'MX', 'type' => 'logistics', 'status' => 'active'],
            ['name' => 'Distribuidora Azteca', 'email' => 'ventas@distazteca.mx', 'phone' => '+52 33 5555 1234', 'country' => 'MX', 'type' => 'supplier', 'status' => 'active'],
            ['name' => 'MexFreight International', 'email' => 'ops@mexfreight.com', 'phone' => '+52 55 4444 8888', 'country' => 'MX', 'type' => 'freight', 'status' => 'active'],
            ['name' => 'AutoPartes del Norte', 'email' => 'soporte@autopartesnorte.mx', 'phone' => '+52 81 3333 7777', 'country' => 'MX', 'type' => 'parts', 'status' => 'active'],
            ['name' => 'Servicios Petroleros Baja', 'email' => 'admin@spbaja.com.mx', 'phone' => '+52 664 2222 6666', 'country' => 'MX', 'type' => 'fuel', 'status' => 'active'],
            ['name' => 'Almacenes del Pacífico', 'email' => 'almacenes@pacifico.mx', 'phone' => '+52 33 1111 9999', 'country' => 'MX', 'type' => 'warehouse', 'status' => 'active'],
            ['name' => 'Taller Mecánico Hernández', 'email' => 'taller@hernandez.mx', 'phone' => '+52 55 7777 3333', 'country' => 'MX', 'type' => 'maintenance', 'status' => 'inactive'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->vendors));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->vendors));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'email', 'phone', 'country', 'status'];
        foreach ($this->vendors as $i => $vendor) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $vendor, "Vendor #{$i} missing field: {$field}");
                $this->assertNotEmpty($vendor[$field], "Vendor #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_emails_are_unique(): void
    {
        $emails = array_column($this->vendors, 'email');
        $this->assertCount(count($this->vendors), array_unique($emails), 'Duplicate vendor email found');
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->vendors, 'name');
        $this->assertCount(count($this->vendors), array_unique($names), 'Duplicate vendor name found');
    }

    public function test_email_format_is_valid(): void
    {
        foreach ($this->vendors as $i => $vendor) {
            $this->assertNotFalse(filter_var($vendor['email'], FILTER_VALIDATE_EMAIL), "Vendor #{$i} has invalid email: {$vendor['email']}");
        }
    }

    public function test_country_code_format(): void
    {
        foreach ($this->vendors as $i => $vendor) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $vendor['country'], "Vendor #{$i} has invalid country code: {$vendor['country']}");
        }
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['active', 'inactive', 'suspended'];
        foreach ($this->vendors as $i => $vendor) {
            $this->assertContains($vendor['status'], $validStatuses, "Vendor #{$i} has invalid status: {$vendor['status']}");
        }
    }

    public function test_phone_format_starts_with_country_code(): void
    {
        foreach ($this->vendors as $i => $vendor) {
            $this->assertStringStartsWith('+52', $vendor['phone'], "Vendor #{$i} phone should start with +52: {$vendor['phone']}");
        }
    }
}
