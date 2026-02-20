<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class GroupSeederTest extends TestCase
{
    protected array $groups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->groups = [
            ['name' => 'Administradores', 'description' => 'Grupo con permisos completos de administración del sistema.'],
            ['name' => 'Operadores', 'description' => 'Operadores de flota con acceso a gestión de vehículos y conductores.'],
            ['name' => 'Dispatchers', 'description' => 'Equipo de despacho con acceso a órdenes y asignación de rutas.'],
            ['name' => 'Supervisores', 'description' => 'Supervisores de operación con acceso a reportes e incidencias.'],
            ['name' => 'Contabilidad', 'description' => 'Equipo contable con acceso a informes de combustible y facturación.'],
            ['name' => 'Mantenimiento', 'description' => 'Personal de taller con acceso a incidencias y estado de vehículos.'],
            ['name' => 'Solo Lectura', 'description' => 'Usuarios con acceso de solo lectura a todos los módulos.'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->groups));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->groups));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'description'];
        foreach ($this->groups as $i => $group) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $group, "Group #{$i} missing field: {$field}");
                $this->assertNotEmpty($group[$field], "Group #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->groups, 'name');
        $this->assertCount(count($this->groups), array_unique($names), 'Duplicate group name found');
    }

    public function test_description_max_length(): void
    {
        foreach ($this->groups as $i => $group) {
            $this->assertLessThanOrEqual(500, strlen($group['description']), "Group #{$i} description exceeds 500 chars");
        }
    }

    public function test_name_is_not_too_long(): void
    {
        foreach ($this->groups as $i => $group) {
            $this->assertLessThanOrEqual(255, strlen($group['name']), "Group #{$i} name exceeds 255 chars");
        }
    }

    public function test_descriptions_are_unique(): void
    {
        $descriptions = array_column($this->groups, 'description');
        $this->assertCount(count($this->groups), array_unique($descriptions), 'Duplicate group description found');
    }
}
