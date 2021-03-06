<?php

declare(strict_types=1);

namespace PhpCfdi\SatCatalogos\Tests\Unit\Nomina;

use PhpCfdi\SatCatalogos\Common\EntryIdentifiable;
use PhpCfdi\SatCatalogos\Nomina\OrigenRecurso;
use PHPUnit\Framework\TestCase;

final class OrigenRecursoTest extends TestCase
{
    public function testCreateInstance(): void
    {
        $id = '01';
        $texto = 'Diurna';
        $vigenteDesde = 0;
        $vigenteHasta = 0;

        $origenRecurso = new OrigenRecurso($id, $texto);

        $this->assertInstanceOf(EntryIdentifiable::class, $origenRecurso);
        $this->assertSame($id, $origenRecurso->id());
        $this->assertSame($texto, $origenRecurso->texto());
        $this->assertSame($vigenteDesde, $origenRecurso->vigenteDesde());
        $this->assertSame($vigenteHasta, $origenRecurso->vigenteHasta());
    }
}
