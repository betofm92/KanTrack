<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class UserSeederTest extends TestCase
{
    protected array $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = [
            ['name' => 'Administrador General', 'email' => 'admin@kantrack.mx', 'phone' => '+52 55 1000 0001', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'admin'],
            ['name' => 'Carlos Operador', 'email' => 'carlos.operador@kantrack.mx', 'phone' => '+52 55 1000 0002', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'María Supervisora', 'email' => 'maria.supervisora@kantrack.mx', 'phone' => '+52 81 1000 0003', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'Roberto Dispatcher', 'email' => 'roberto.dispatch@kantrack.mx', 'phone' => '+52 33 1000 0004', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'Ana Contadora', 'email' => 'ana.contadora@kantrack.mx', 'phone' => '+52 55 1000 0005', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'Fernando Mecánico', 'email' => 'fernando.mecanico@kantrack.mx', 'phone' => '+52 81 1000 0006', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'Patricia Logística', 'email' => 'patricia.logistica@kantrack.mx', 'phone' => '+52 33 1000 0007', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
            ['name' => 'Miguel Almacenista', 'email' => 'miguel.almacen@kantrack.mx', 'phone' => '+52 55 1000 0008', 'timezone' => 'America/Mexico_City', 'country' => 'MX', 'status' => 'inactive', 'type' => 'user'],
            ['name' => 'Laura Facturación', 'email' => 'laura.facturacion@kantrack.mx', 'phone' => '+52 664 1000 0009', 'timezone' => 'America/Tijuana', 'country' => 'MX', 'status' => 'active', 'type' => 'user'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->users));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->users));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'email', 'phone', 'country', 'status'];
        foreach ($this->users as $i => $user) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $user, "User #{$i} missing field: {$field}");
                $this->assertNotEmpty($user[$field], "User #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_emails_are_unique(): void
    {
        $emails = array_column($this->users, 'email');
        $this->assertCount(count($this->users), array_unique($emails), 'Duplicate user email found');
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->users, 'name');
        $this->assertCount(count($this->users), array_unique($names), 'Duplicate user name found');
    }

    public function test_email_format_is_valid(): void
    {
        foreach ($this->users as $i => $user) {
            $this->assertNotFalse(filter_var($user['email'], FILTER_VALIDATE_EMAIL), "User #{$i} has invalid email: {$user['email']}");
        }
    }

    public function test_has_at_least_one_admin(): void
    {
        $admins = array_filter($this->users, fn($u) => $u['type'] === 'admin');
        $this->assertGreaterThanOrEqual(1, count($admins), 'Should have at least one admin user');
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['active', 'inactive', 'suspended'];
        foreach ($this->users as $i => $user) {
            $this->assertContains($user['status'], $validStatuses, "User #{$i} has invalid status: {$user['status']}");
        }
    }

    public function test_timezone_is_valid(): void
    {
        $validTimezones = \DateTimeZone::listIdentifiers();
        foreach ($this->users as $i => $user) {
            $this->assertContains($user['timezone'], $validTimezones, "User #{$i} has invalid timezone: {$user['timezone']}");
        }
    }

    public function test_country_code_format(): void
    {
        foreach ($this->users as $i => $user) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $user['country'], "User #{$i} has invalid country code: {$user['country']}");
        }
    }

    public function test_type_values_are_valid(): void
    {
        $validTypes = ['admin', 'user'];
        foreach ($this->users as $i => $user) {
            $this->assertContains($user['type'], $validTypes, "User #{$i} has invalid type: {$user['type']}");
        }
    }
}
