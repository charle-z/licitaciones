<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Oferta;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

final class OfertaExporter
{
    public function generate(array $filters): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ofertas-');
        if ($path === false) {
            throw new RuntimeException('No se pudo preparar el reporte.');
        }
        $writer = new Writer();
        try {
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues(['Consecutivo', 'Objeto', 'Descripción', 'Fecha de inicio', 'Fecha de cierre', 'Estado']));
            foreach (Oferta::filtrar($filters)->lazyByIdDesc(500) as $offer) {
                // Las celdas de texto se escriben como texto, incluso si empiezan por =.
                $values = [$offer->consecutivo, $offer->objeto, $offer->descripcion, $offer->fecha_inicio, $offer->fecha_cierre, $offer->estado];
                $writer->addRow(new Row(array_map(static fn ($value) => new StringCell((string) $value), $values)));
            }
            $writer->close();
            return $path;
        } catch (\Throwable $error) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $error;
        }
    }
}
