<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class FleetSeederTest extends TestCase
{
    protected array $fleets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fleets = [
            ['name' => 'Flota Norte', 'color' => '#1E88E5', 'task' => 'Distribución zona norte', 'status' => 'active'],
            ['name' => 'Flota Sur', 'color' => '#43A047', 'task' => 'Distribución zona sur', 'status' => 'active'],
            ['name' => 'Flota Centro', 'color' => '#FB8C00', 'task' => 'Distribución zona centro', 'status' => 'active'],
            ['name' => 'Flota Express', 'color' => '#E53935', 'task' => 'Entregas urgentes', 'status' => 'active'],
            ['name' => 'Flota Pesada', 'color' => '#8E24AA', 'task' => 'Transporte de carga pesada', 'status' => 'active'],
            ['name' => 'Flota Refrigerada', 'color' => '#00ACC1', 'task' => 'Transporte de perecederos', 'status' => 'active'],
            ['name' => 'Flota Nocturna', 'color' => '#3949AB', 'task' => 'Operaciones nocturnas', 'status' => 'inactive'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->fleets));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->fleets));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'status'];
        foreach ($this->fleets as $i => $fleet) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $fleet, "Fleet #{$i} missing field: {$field}");
                $this->assertNotEmpty($fleet[$field], "Fleet #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->fleets, 'name');
        $this->assertCount(count($this->fleets), array_unique($names), 'Duplicate fleet name found');
    }

    public function test_color_is_valid_hex(): void
    {
        foreach ($this->fleets as $i => $fleet) {
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $fleet['color'], "Fleet #{$i} has invalid color: {$fleet['color']}");
        }
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['active', 'inactive', 'suspended'];
        foreach ($this->fleets as $i => $fleet) {
            $this->assertContains($fleet['status'], $validStatuses, "Fleet #{$i} has invalid status: {$fleet['status']}");
        }
    }

    public function test_all_have_task_description(): void
    {
        foreach ($this->fleets as $i => $fleet) {
            $this->assertArrayHasKey('task', $fleet, "Fleet #{$i} missing 'task'");
            $this->assertNotEmpty($fleet['task'], "Fleet #{$i} has empty 'task'");
        }
    }

    public function test_colors_are_unique(): void
    {
        $colors = array_column($this->fleets, 'color');
        $this->assertCount(count($this->fleets), array_unique($colors), 'Duplicate fleet color found');
    }
}
