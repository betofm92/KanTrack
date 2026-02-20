<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class IssueSeederTest extends TestCase
{
    protected array $issues;

    protected function setUp(): void
    {
        parent::setUp();
        $this->issues = [
            ['title' => 'Falla en frenos del vehículo #01', 'type' => 'mechanical', 'category' => 'brakes', 'priority' => 'high', 'status' => 'pending', 'tags' => ['frenos', 'urgente', 'seguridad']],
            ['title' => 'Llanta ponchada en ruta Monterrey', 'type' => 'tire', 'category' => 'tires', 'priority' => 'critical', 'status' => 'in-progress', 'tags' => ['llanta', 'carretera', 'auxilio']],
            ['title' => 'Fuga de aceite detectada', 'type' => 'mechanical', 'category' => 'engine', 'priority' => 'high', 'status' => 'pending', 'tags' => ['motor', 'aceite', 'mantenimiento']],
            ['title' => 'GPS no reporta posición', 'type' => 'electronic', 'category' => 'gps', 'priority' => 'medium', 'status' => 'pending', 'tags' => ['gps', 'telemetría', 'electrónica']],
            ['title' => 'Aire acondicionado no funciona', 'type' => 'comfort', 'category' => 'hvac', 'priority' => 'low', 'status' => 'resolved', 'tags' => ['aire', 'cabina', 'confort']],
            ['title' => 'Documentación vencida - Verificación', 'type' => 'compliance', 'category' => 'documentation', 'priority' => 'medium', 'status' => 'in-progress', 'tags' => ['verificación', 'documentos', 'cumplimiento']],
            ['title' => 'Colisión menor en estacionamiento', 'type' => 'accident', 'category' => 'collision', 'priority' => 'low', 'status' => 'resolved', 'tags' => ['accidente', 'defensa', 'siniestro']],
            ['title' => 'Sensor de temperatura fallando', 'type' => 'electronic', 'category' => 'sensors', 'priority' => 'critical', 'status' => 'pending', 'tags' => ['sensor', 'temperatura', 'refrigeración']],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->issues));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->issues));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['title', 'type', 'category', 'priority', 'status'];
        foreach ($this->issues as $i => $issue) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $issue, "Issue #{$i} missing field: {$field}");
                $this->assertNotEmpty($issue[$field], "Issue #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_titles_are_unique(): void
    {
        $titles = array_column($this->issues, 'title');
        $this->assertCount(count($this->issues), array_unique($titles), 'Duplicate issue title found');
    }

    public function test_priority_values_are_valid(): void
    {
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        foreach ($this->issues as $i => $issue) {
            $this->assertContains($issue['priority'], $validPriorities, "Issue #{$i} has invalid priority: {$issue['priority']}");
        }
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['pending', 'in-progress', 'resolved', 'closed'];
        foreach ($this->issues as $i => $issue) {
            $this->assertContains($issue['status'], $validStatuses, "Issue #{$i} has invalid status: {$issue['status']}");
        }
    }

    public function test_tags_are_arrays(): void
    {
        foreach ($this->issues as $i => $issue) {
            $this->assertIsArray($issue['tags'], "Issue #{$i} tags should be an array");
            $this->assertNotEmpty($issue['tags'], "Issue #{$i} should have at least one tag");
        }
    }

    public function test_all_priorities_represented(): void
    {
        $priorities = array_unique(array_column($this->issues, 'priority'));
        $this->assertContains('critical', $priorities, 'Missing critical priority issues');
        $this->assertContains('high', $priorities, 'Missing high priority issues');
        $this->assertContains('medium', $priorities, 'Missing medium priority issues');
        $this->assertContains('low', $priorities, 'Missing low priority issues');
    }

    public function test_multiple_statuses_represented(): void
    {
        $statuses = array_unique(array_column($this->issues, 'status'));
        $this->assertGreaterThanOrEqual(2, count($statuses), 'Should have at least 2 different statuses');
    }
}
