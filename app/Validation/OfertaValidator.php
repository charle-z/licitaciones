<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\HttpException;
use DateTimeImmutable;

final class OfertaValidator
{
    public static function validate(array $input): array
    {
        $errors = [];
        $data = [];
        foreach (['objeto' => 150, 'descripcion' => 400] as $field => $max) {
            $value = $input[$field] ?? '';
            $data[$field] = is_string($value) ? trim($value) : '';
            if ($data[$field] === '' || mb_strlen($data[$field]) > $max) {
                $errors[$field] = "Es obligatorio y debe tener como máximo {$max} caracteres.";
            }
        }
        $data['moneda'] = $input['moneda'] ?? '';
        if (!is_string($data['moneda']) || !in_array($data['moneda'], ['COP', 'USD', 'EUR'], true)) {
            $errors['moneda'] = 'Selecciona COP, USD o EUR.';
        }
        $budget = $input['presupuesto'] ?? '';
        if (!is_string($budget) && !is_int($budget)) {
            $budget = '';
        }
        $budget = (string) $budget;
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $budget) || str_replace(['0', '.'], '', $budget) === '') {
            $errors['presupuesto'] = 'Ingresa un valor mayor a cero, hasta 13 enteros y dos decimales.';
        }
        $data['presupuesto'] = $budget;
        $id = filter_var($input['actividad_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
        if ($id === false) {
            $errors['actividad_id'] = 'Selecciona una actividad del catálogo.';
        }
        $data['actividad_id'] = $id;
        $moments = [];
        foreach (['inicio', 'cierre'] as $suffix) {
            $date = $input['fecha_' . $suffix] ?? '';
            $time = $input['hora_' . $suffix] ?? '';
            if (!is_string($date) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)) {
                $errors['fecha_' . $suffix] = 'Ingresa una fecha válida.';
                $date = '';
            }
            if (!is_string($time) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)) {
                $errors['hora_' . $suffix] = 'Usa una hora válida en formato de 24 horas.';
                $time = '';
            }
            $value = $date . ' ' . $time;
            $moment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value);
            if ($moment === false || $moment->format('Y-m-d H:i') !== $value) {
                $errors['fecha_' . $suffix] ??= 'Revisa la fecha y hora.';
            } else {
                $moments[$suffix] = $moment;
            }
            $data['fecha_' . $suffix] = $date;
            $data['hora_' . $suffix] = $time;
        }
        if (isset($moments['inicio'], $moments['cierre']) && $moments['inicio'] >= $moments['cierre']) {
            $errors['fecha_cierre'] = 'El cierre debe ser posterior al inicio.';
        }
        if ($errors !== []) {
            throw new HttpException('Revisa los campos indicados.', 422, $errors);
        }
        return $data;
    }
}
