<?php

declare(strict_types=1);

use App\Models\Actividad;
use App\Models\Documento;
use App\Models\Oferta;
use App\Services\OfertaExporter;
use App\Validation\OfertaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\QueryException;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\TestCase;

final class OfertaDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        Documento::query()->delete();
        Oferta::query()->delete();
    }

    private function createOffer(string $object): Oferta
    {
        $input = OfertaValidatorTest::validInput();
        $input['objeto'] = $object;
        return Oferta::create(OfertaValidator::validate($input));
    }

    public function testRealCatalogIsLoaded(): void
    {
        self::assertSame(49022, Actividad::count());
        self::assertSame(49022, Actividad::distinct()->count('codigo_producto'));
    }

    public function testFiltersTreatSqlAndWildcardsAsLiteralText(): void
    {
        $this->createOffer('Compra 10%_real');
        $this->createOffer('Compra 1000real');
        self::assertSame(1, Oferta::filtrar(['objeto' => '%_'])->count());
        self::assertSame(0, Oferta::filtrar(['objeto' => "' OR 1=1 --"])->count());
        self::assertSame(2, Oferta::count());
    }

    public function testForeignKeyAndBudgetAreEnforcedInMySql(): void
    {
        $offer = $this->createOffer('Válida');
        try {
            DB::table('ofertas')->where('id', $offer->id)->update(['presupuesto' => '-1']);
            self::fail('MySQL aceptó un presupuesto negativo');
        } catch (QueryException) {
            self::assertSame('1000000.25', $offer->fresh()->presupuesto);
        }
        $this->expectException(QueryException::class);
        DB::table('ofertas')->where('id', $offer->id)->update(['actividad_id' => 4294967295]);
    }

    public function testExportIncludesAllMatchingRowsAndNoFormulas(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $offer = $this->createOffer('=SUM(1,2) GrupoExportar ' . $i);
            $offer->forceFill(['consecutivo' => sprintf('PO-%04d-26', $offer->id)])->save();
        }
        $this->createOffer('No incluido');
        $path = (new OfertaExporter())->generate(['objeto' => 'GrupoExportar']);
        $reader = new Reader();
        try {
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
            self::assertCount(14, $rows);
            self::assertStringStartsWith('PO-', $rows[1][0]);
            self::assertStringStartsWith('=SUM(1,2)', $rows[1][1]);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($path));
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            self::assertStringNotContainsString('<f>', $xml);
            $zip->close();
        } finally {
            $reader->close();
            unlink($path);
        }
    }

    public function testExportWithNoMatchesStillHasHeaders(): void
    {
        $path = (new OfertaExporter())->generate(['objeto' => 'Sin resultados']);
        $zip = new ZipArchive();
        try {
            self::assertTrue($zip->open($path));
            self::assertStringContainsString('Consecutivo', $zip->getFromName('xl/worksheets/sheet1.xml'));
        } finally {
            $zip->close();
            unlink($path);
        }
    }
}
