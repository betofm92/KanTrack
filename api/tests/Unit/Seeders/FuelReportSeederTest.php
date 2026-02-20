<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class FuelReportSeederTest extends TestCase
{
    protected array $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reports = [
            ['report' => 'Carga completa de diésel en estación CDMX Norte. Sin novedades.', 'odometer' => '45230', 'amount' => '2850', 'currency' => 'MXN', 'volume' => '150', 'metric_unit' => 'liters', 'status' => 'submitted'],
            ['report' => 'Recarga parcial de combustible en ruta Guadalajara-Monterrey.', 'odometer' => '67890', 'amount' => '1500', 'currency' => 'MXN', 'volume' => '80', 'metric_unit' => 'liters', 'status' => 'submitted'],
            ['report' => 'Carga de gasolina premium en Monterrey. Tanque lleno.', 'odometer' => '12345', 'amount' => '3200', 'currency' => 'MXN', 'volume' => '180', 'metric_unit' => 'liters', 'status' => 'approved'],
            ['report' => 'Reabastecimiento de emergencia en carretera Puebla-Veracruz.', 'odometer' => '89012', 'amount' => '950', 'currency' => 'MXN', 'volume' => '50', 'metric_unit' => 'liters', 'status' => 'submitted'],
            ['report' => 'Carga programada semanal. Estación Pemex Insurgentes.', 'odometer' => '34567', 'amount' => '4100', 'currency' => 'MXN', 'volume' => '220', 'metric_unit' => 'liters', 'status' => 'approved'],
            ['report' => 'Carga parcial antes de viaje largo CDMX-Cancún.', 'odometer' => '56789', 'amount' => '1800', 'currency' => 'MXN', 'volume' => '95', 'metric_unit' => 'liters', 'status' => 'submitted'],
            ['report' => 'Recarga de diésel en estación de servicio Mérida Centro.', 'odometer' => '78901', 'amount' => '2400', 'currency' => 'MXN', 'volume' => '130', 'metric_unit' => 'liters', 'status' => 'rejected'],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->reports));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->reports));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['report', 'amount', 'currency', 'volume', 'metric_unit', 'status'];
        foreach ($this->reports as $i => $report) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $report, "FuelReport #{$i} missing field: {$field}");
                $this->assertNotEmpty($report[$field], "FuelReport #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_reports_are_unique(): void
    {
        $reports = array_column($this->reports, 'report');
        $this->assertCount(count($this->reports), array_unique($reports), 'Duplicate fuel report text found');
    }

    public function test_amount_is_numeric(): void
    {
        foreach ($this->reports as $i => $report) {
            $this->assertTrue(is_numeric($report['amount']), "FuelReport #{$i} amount is not numeric: {$report['amount']}");
        }
    }

    public function test_volume_is_numeric(): void
    {
        foreach ($this->reports as $i => $report) {
            $this->assertTrue(is_numeric($report['volume']), "FuelReport #{$i} volume is not numeric: {$report['volume']}");
        }
    }

    public function test_odometer_is_numeric(): void
    {
        foreach ($this->reports as $i => $report) {
            $this->assertTrue(is_numeric($report['odometer']), "FuelReport #{$i} odometer is not numeric: {$report['odometer']}");
        }
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = ['submitted', 'approved', 'rejected', 'pending'];
        foreach ($this->reports as $i => $report) {
            $this->assertContains($report['status'], $validStatuses, "FuelReport #{$i} has invalid status: {$report['status']}");
        }
    }

    public function test_currency_is_mxn(): void
    {
        foreach ($this->reports as $i => $report) {
            $this->assertEquals('MXN', $report['currency'], "FuelReport #{$i} currency should be MXN");
        }
    }

    public function test_metric_unit_is_liters(): void
    {
        foreach ($this->reports as $i => $report) {
            $this->assertEquals('liters', $report['metric_unit'], "FuelReport #{$i} metric_unit should be liters");
        }
    }
}
