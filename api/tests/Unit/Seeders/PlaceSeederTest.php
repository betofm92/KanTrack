<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

class PlaceSeederTest extends TestCase
{
    protected array $places;

    protected function setUp(): void
    {
        parent::setUp();
        $this->places = [
            ['name' => 'Centro de Distribución CDMX', 'street1' => 'Av. Insurgentes Sur 1234', 'city' => 'Ciudad de México', 'province' => 'CDMX', 'postal_code' => '03100', 'country' => 'MX', 'type' => 'warehouse', 'lat' => 19.3910, 'lng' => -99.1709],
            ['name' => 'Almacén Guadalajara', 'street1' => 'Calzada Lázaro Cárdenas 567', 'city' => 'Guadalajara', 'province' => 'Jalisco', 'postal_code' => '44100', 'country' => 'MX', 'type' => 'warehouse', 'lat' => 20.6597, 'lng' => -103.3496],
            ['name' => 'Terminal de Carga Monterrey', 'street1' => 'Blvd. Rogelio Cantú Gómez 890', 'city' => 'Monterrey', 'province' => 'Nuevo León', 'postal_code' => '64620', 'country' => 'MX', 'type' => 'terminal', 'lat' => 25.6866, 'lng' => -100.3161],
            ['name' => 'Punto de Entrega Cancún', 'street1' => 'Av. Tulum Km 3.5', 'city' => 'Cancún', 'province' => 'Quintana Roo', 'postal_code' => '77500', 'country' => 'MX', 'type' => 'delivery_point', 'lat' => 21.1619, 'lng' => -86.8515],
            ['name' => 'Puerto de Veracruz - Muelle 3', 'street1' => 'Recinto Portuario S/N', 'city' => 'Veracruz', 'province' => 'Veracruz', 'postal_code' => '91700', 'country' => 'MX', 'type' => 'port', 'lat' => 19.1738, 'lng' => -96.1342],
            ['name' => 'Bodega Oaxaca Centro', 'street1' => 'Calle Macedonio Alcalá 302', 'city' => 'Oaxaca de Juárez', 'province' => 'Oaxaca', 'postal_code' => '68000', 'country' => 'MX', 'type' => 'warehouse', 'lat' => 17.0732, 'lng' => -96.7266],
            ['name' => 'Centro Logístico Mérida', 'street1' => 'Periférico Norte Tablaje 12345', 'city' => 'Mérida', 'province' => 'Yucatán', 'postal_code' => '97300', 'country' => 'MX', 'type' => 'hub', 'lat' => 20.9674, 'lng' => -89.5926],
            ['name' => 'Planta Industrial Querétaro', 'street1' => 'Parque Industrial Benito Juárez Lote 45', 'city' => 'Querétaro', 'province' => 'Querétaro', 'postal_code' => '76120', 'country' => 'MX', 'type' => 'factory', 'lat' => 20.5888, 'lng' => -100.3899],
            ['name' => 'Oficina Regional Puebla', 'street1' => 'Blvd. Hermanos Serdán 1500', 'city' => 'Puebla', 'province' => 'Puebla', 'postal_code' => '72090', 'country' => 'MX', 'type' => 'office', 'lat' => 19.0414, 'lng' => -98.2063],
        ];
    }

    public function test_has_minimum_seven_records(): void
    {
        $this->assertGreaterThanOrEqual(7, count($this->places));
    }

    public function test_has_maximum_thirteen_records(): void
    {
        $this->assertLessThanOrEqual(13, count($this->places));
    }

    public function test_all_have_required_fields(): void
    {
        $requiredFields = ['name', 'street1', 'city', 'country'];
        foreach ($this->places as $i => $place) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $place, "Place #{$i} missing field: {$field}");
                $this->assertNotEmpty($place[$field], "Place #{$i} has empty field: {$field}");
            }
        }
    }

    public function test_names_are_unique(): void
    {
        $names = array_column($this->places, 'name');
        $this->assertCount(count($this->places), array_unique($names), 'Duplicate place name found');
    }

    public function test_country_code_format(): void
    {
        foreach ($this->places as $i => $place) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $place['country'], "Place #{$i} has invalid country code: {$place['country']}");
        }
    }

    public function test_coordinates_are_valid_for_mexico(): void
    {
        foreach ($this->places as $i => $place) {
            $this->assertGreaterThanOrEqual(14.0, $place['lat'], "Place #{$i} latitude too low for Mexico: {$place['lat']}");
            $this->assertLessThanOrEqual(33.0, $place['lat'], "Place #{$i} latitude too high for Mexico: {$place['lat']}");
            $this->assertGreaterThanOrEqual(-118.0, $place['lng'], "Place #{$i} longitude too low for Mexico: {$place['lng']}");
            $this->assertLessThanOrEqual(-86.0, $place['lng'], "Place #{$i} longitude too high for Mexico: {$place['lng']}");
        }
    }

    public function test_postal_codes_are_5_digits(): void
    {
        foreach ($this->places as $i => $place) {
            $this->assertMatchesRegularExpression('/^\d{5}$/', $place['postal_code'], "Place #{$i} has invalid postal code: {$place['postal_code']}");
        }
    }

    public function test_all_have_province(): void
    {
        foreach ($this->places as $i => $place) {
            $this->assertArrayHasKey('province', $place, "Place #{$i} missing 'province'");
            $this->assertNotEmpty($place['province'], "Place #{$i} has empty 'province'");
        }
    }
}
