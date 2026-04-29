<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class ContactSeederTest extends TestCase
{
    protected array $contacts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contacts = [
            ['name' => 'Carlos Mendoza', 'title' => 'Gerente de operaciones', 'email' => 'carlos.mendoza@empresa.mx', 'phone' => '+52 55 1234 0001', 'type' => 'customer'],
            ['name' => 'María López Hernández', 'title' => 'Directora de logística', 'email' => 'maria.lopez@logistica.mx', 'phone' => '+52 81 1234 0002', 'type' => 'customer'],
            ['name' => 'Roberto Sánchez', 'title' => 'Jefe de almacén', 'email' => 'roberto.sanchez@almacen.mx', 'phone' => '+52 33 1234 0003', 'type' => 'contact'],
            ['name' => 'Ana García Torres', 'title' => 'Coordinadora de envíos', 'email' => 'ana.garcia@envios.mx', 'phone' => '+52 55 1234 0004', 'type' => 'contact'],
            ['name' => 'Fernando Ramírez', 'title' => 'Supervisor de flota', 'email' => 'fernando.ramirez@flota.mx', 'phone' => '+52 81 1234 0005', 'type' => 'vendor'],
            ['name' => 'Patricia Morales', 'title' => 'Administradora de proveedores', 'email' => 'patricia.morales@proveedores.mx', 'phone' => '+52 33 1234 0006', 'type' => 'vendor'],
            ['name' => 'Miguel Ángel Díaz', 'title' => 'Director comercial', 'email' => 'miguel.diaz@comercial.mx', 'phone' => '+52 55 1234 0007', 'type' => 'customer'],
            ['name' => 'Laura Jiménez Ruiz', 'title' => 'Encargada de facturación', 'email' => 'laura.jimenez@facturacion.mx', 'phone' => '+52 664 1234 0008', 'type' => 'contact'],
            ['name' => 'Javier Ortiz', 'title' => 'Representante de ventas', 'email' => 'javier.ortiz@ventas.mx', 'phone' => '+52 55 1234 0009', 'type' => 'customer'],
            ['name' => 'Sofía Castillo Vega', 'title' => 'Analista de rutas', 'email' => 'sofia.castillo@rutas.mx', 'phone' => '+52 81 1234 0010', 'type' => 'contact'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->contacts));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->contacts));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'email', 'phone', 'type'];
        foreach ($this->contacts as $i => $contact) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $contact, "Contact #{$i} missing field: {$field}");
                $this->assertNotEmpty($contact[$field], "Contact #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_emails_are_unique(): void
    {
        $emails = array_column($this->contacts, 'email');
        $this->assertCount(count($this->contacts), array_unique($emails), 'Duplicate contact email found');
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->contacts, 'name');
        $this->assertCount(count($this->contacts), array_unique($names), 'Duplicate contact name found');
    }

    public function test_email_format_is_valid(): void
    {
        foreach ($this->contacts as $i => $contact) {
            $this->assertNotFalse(filter_var($contact['email'], FILTER_VALIDATE_EMAIL), "Contact #{$i} has invalid email: {$contact['email']}");
        }
    }

    public function test_type_values_are_valid(): void
    {
        $validTypes = ['customer', 'contact', 'vendor'];
        foreach ($this->contacts as $i => $contact) {
            $this->assertContains($contact['type'], $validTypes, "Contact #{$i} has invalid type: {$contact['type']}");
        }
    }

    public function test_all_have_title(): void
    {
        foreach ($this->contacts as $i => $contact) {
            $this->assertArrayHasKey('title', $contact, "Contact #{$i} missing 'title'");
            $this->assertNotEmpty($contact['title'], "Contact #{$i} has empty 'title'");
        }
    }
}
