<?php

declare(strict_types=1);

use App\Http\HttpException;
use App\Validation\OfertaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OfertaValidatorTest extends TestCase
{
    public static function validInput(): array
    {
        return ['objeto' => 'Compra de equipos', 'descripcion' => 'Equipos para la oficina', 'moneda' => 'COP', 'presupuesto' => '1000000.25', 'actividad_id' => '1', 'fecha_inicio' => '2026-10-23', 'hora_inicio' => '08:00', 'fecha_cierre' => '2026-10-24', 'hora_cierre' => '17:00'];
    }

    public function testTrimsTextAndPreservesDecimalPrecision(): void
    {
        $input = self::validInput();
        $input['objeto'] = '  Compra de equipos  ';
        $input['presupuesto'] = '9999999999999.99';
        $result = OfertaValidator::validate($input);
        self::assertSame('Compra de equipos', $result['objeto']);
        self::assertSame('9999999999999.99', $result['presupuesto']);
    }

    #[DataProvider('invalidFields')]
    public function testRejectsInvalidFields(string $field, mixed $value): void
    {
        $input = self::validInput();
        $input[$field] = $value;
        $this->expectException(HttpException::class);
        OfertaValidator::validate($input);
    }

    public static function invalidFields(): iterable
    {
        yield ['objeto', '   '];
        yield ['objeto', str_repeat('á', 151)];
        yield ['descripcion', str_repeat('x', 401)];
        yield ['descripcion', []];
        yield ['moneda', 'GBP'];
        yield ['moneda', ['COP']];
        yield ['presupuesto', '0.00'];
        yield ['presupuesto', '-1'];
        yield ['presupuesto', '1e6'];
        yield ['presupuesto', '1,20'];
        yield ['presupuesto', '1.234'];
        yield ['presupuesto', '10000000000000'];
        yield ['presupuesto', 1.2];
        yield ['actividad_id', 0];
        yield ['actividad_id', '1 OR 1=1'];
        yield ['fecha_inicio', '2026-02-30'];
        yield ['fecha_inicio', '2026-10-25'];
        yield ['hora_inicio', '24:00'];
        yield ['hora_cierre', '12:60'];
        yield ['fecha_cierre', '2026-10-22'];
    }

    public function testRejectsEqualStartAndEnd(): void
    {
        $input = self::validInput();
        $input['fecha_cierre'] = $input['fecha_inicio'];
        $input['hora_cierre'] = $input['hora_inicio'];
        $this->expectException(HttpException::class);
        OfertaValidator::validate($input);
    }
}
