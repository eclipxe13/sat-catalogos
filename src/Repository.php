<?php

declare(strict_types=1);

namespace PhpCfdi\SatCatalogos;

use LogicException;
use PDO;
use PDOException;
use PDOStatement;
use PhpCfdi\SatCatalogos\Exceptions\SatCatalogosLogicException;
use PhpCfdi\SatCatalogos\Exceptions\SatCatalogosNotFoundException;

class Repository
{
    /** @var PDO */
    private $pdo;

    /** @var PDOStatement<mixed>[] */
    private $statements = [];

    public const CFDI_ADUANAS = 'cfdi_aduanas';

    public const CFDI_CLAVES_UNIDADES = 'cfdi_claves_unidades';

    public const CFDI_PRODUCTOS_SERVICIOS = 'cfdi_productos_servicios';

    public const CFDI_CODIGOS_POSTALES = 'cfdi_codigos_postales';

    public const CFDI_IMPUESTOS = 'cfdi_impuestos';

    public const CFDI_FORMAS_PAGO = 'cfdi_formas_pago';

    public const CFDI_METODOS_PAGO = 'cfdi_metodos_pago';

    public const CFDI_MONEDAS = 'cfdi_monedas';

    public const CFDI_PAISES = 'cfdi_paises';

    public const CFDI_REGIMENES_FISCALES = 'cfdi_regimenes_fiscales';

    public const CFDI_TIPOS_RELACIONES = 'cfdi_tipos_relaciones';

    public const CFDI_USOS_CFDI = 'cfdi_usos_cfdi';

    public const CFDI_TIPOS_FACTORES = 'cfdi_tipos_factores';

    public const CFDI_NUMEROS_PEDIMENTO_ADUANA = 'cfdi_numeros_pedimento_aduana';

    public const CFDI_REGLAS_TASA_CUOTA = 'cfdi_reglas_tasa_cuota';

    public const CFDI_PATENTES_ADUANALES = 'cfdi_patentes_aduanales';

    public const CFDI_TIPOS_COMPROBANTES = 'cfdi_tipos_comprobantes';

    public const NOMINA_TIPOS_NOMINAS = 'nomina_tipos_nominas';

    public const NOMINA_TIPOS_JORNADAS = 'nomina_tipos_jornadas';

    public const NOMINA_ORIGENES_RECURSOS = 'nomina_origenes_recursos';

    public const NOMINA_BANCOS = 'nomina_bancos';

    public const NOMINA_ESTADOS = 'nomina_estados';

    public const NOMINA_PERIODICIDADES_PAGOS = 'nomina_periodicidades_pagos';

    public const NOMINA_RIESGOS_PUESTOS = 'nomina_riesgos_puestos';

    public const NOMINA_TIPOS_CONTRATOS = 'nomina_tipos_contratos';

    public const NOMINA_TIPOS_DEDUCCIONES = 'nomina_tipos_deducciones';

    public const NOMINA_TIPOS_HORAS = 'nomina_tipos_horas';

    public const NOMINA_TIPOS_INCAPACIDADES = 'nomina_tipos_incapacidades';

    public const NOMINA_TIPOS_OTROS_PAGOS = 'nomina_tipos_otros_pagos';

    public const NOMINA_TIPOS_PERCEPCIONES = 'nomina_tipos_percepciones';

    public const NOMINA_TIPOS_REGIMENES = 'nomina_tipos_regimenes';

    public const CATALOGS = [
        self::CFDI_ADUANAS,
        self::CFDI_CLAVES_UNIDADES,
        self::CFDI_PRODUCTOS_SERVICIOS,
        self::CFDI_CODIGOS_POSTALES,
        self::CFDI_IMPUESTOS,
        self::CFDI_FORMAS_PAGO,
        self::CFDI_METODOS_PAGO,
        self::CFDI_MONEDAS,
        self::CFDI_PAISES,
        self::CFDI_REGIMENES_FISCALES,
        self::CFDI_TIPOS_RELACIONES,
        self::CFDI_USOS_CFDI,
        self::CFDI_TIPOS_FACTORES,
        self::CFDI_NUMEROS_PEDIMENTO_ADUANA,
        self::CFDI_REGLAS_TASA_CUOTA,
        self::CFDI_PATENTES_ADUANALES,
        self::CFDI_TIPOS_COMPROBANTES,
        self::NOMINA_TIPOS_NOMINAS,
        self::NOMINA_TIPOS_JORNADAS,
        self::NOMINA_ORIGENES_RECURSOS,
        self::NOMINA_BANCOS,
        self::NOMINA_ESTADOS,
        self::NOMINA_PERIODICIDADES_PAGOS,
        self::NOMINA_RIESGOS_PUESTOS,
        self::NOMINA_TIPOS_CONTRATOS,
        self::NOMINA_TIPOS_DEDUCCIONES,
        self::NOMINA_TIPOS_HORAS,
        self::NOMINA_TIPOS_INCAPACIDADES,
        self::NOMINA_TIPOS_OTROS_PAGOS,
        self::NOMINA_TIPOS_PERCEPCIONES,
        self::NOMINA_TIPOS_REGIMENES,
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $catalog
     * @param string $id
     * @return array<string, mixed>
     */
    public function queryById(string $catalog, string $id): array
    {
        $sql = 'select *'
            . ' from ' . $this->catalogName($catalog)
            . ' where (id = :id);';
        $data = $this->queryRow($sql, ['id' => $id]);
        if (! count($data)) {
            throw $this->createSatCatalogosNotFoundException($catalog, ['id' => $id]);
        }

        return $data;
    }

    /**
     * @param string $catalog
     * @param string[] $ids
     * @return array<array<string, mixed>>
     */
    public function queryByIds(string $catalog, array $ids): array
    {
        return $this->queryRowsInField($catalog, 'id', $ids);
    }

    /**
     * @param string $catalog
     * @param string $fieldName
     * @param mixed[] $values
     * @return array<int, array<string, mixed>>
     */
    public function queryRowsInField(string $catalog, string $fieldName, array $values): array
    {
        $values = array_values($values);
        $questionMarks = implode(',', array_fill(0, count($values), '?'));
        $sql = 'select *'
            . ' from ' . $this->catalogName($catalog)
            . ' where ' . $this->escapeName($fieldName) . ' IN (' . $questionMarks . ')'
            . ';';
        $stmt = $this->query($sql, $values);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return (is_array($data)) ? $data : [];
    }

    /**
     * @param string $catalog
     * @param array<string, mixed> $values
     * @param int $limit
     * @param bool $exactSearch
     * @return array<int, array<string, mixed>>
     */
    public function queryRowsByFields(string $catalog, array $values, int $limit = 0, bool $exactSearch = true): array
    {
        $keys = array_keys($values);
        $operator = ($exactSearch) ? '=' : 'like';
        $sql = 'select *'
            . ' from ' . $this->catalogName($catalog)
            . call_user_func(function (array $keys, string $operator): string {
                if (count($keys)) {
                    return ' where ' . implode(' and ', array_map(function ($field) use ($operator) {
                        return '(' . $this->escapeName($field) . ' ' . $operator . ' :' . $field . ')';
                    }, $keys));
                }
                return '';
            }, $keys, $operator)
            . (($limit > 0) ? ' limit ' . $limit : '')
            . ';';
        $stmt = $this->query($sql, $values);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return (is_array($data)) ? $data : [];
    }

    /**
     * @param string $catalog
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function queryRowByFields(string $catalog, array $values): array
    {
        $data = $this->queryRowsByFields($catalog, $values, 1);
        if (1 !== count($data)) {
            throw $this->createSatCatalogosNotFoundException($catalog, $values);
        }

        return $data[0];
    }

    /**
     * @param string $catalog
     * @param array<string, mixed> $values
     * @return SatCatalogosNotFoundException
     */
    private function createSatCatalogosNotFoundException(string $catalog, array $values): SatCatalogosNotFoundException
    {
        $valuesCount = count($values);
        $keys = array_keys($values);
        if ($valuesCount > 1) {
            $exMessage = sprintf(
                'Cannot found %s using (%s) with values (%s)',
                $catalog,
                implode(', ', $keys),
                implode(', ', $values)
            );
        } elseif (1 === $valuesCount) {
            $exMessage = sprintf('Cannot found %s using %s \'%s\'', $catalog, $keys[0], $values[$keys[0]]);
        } else {
            $exMessage = sprintf('Cannot found any %s without filter', $catalog);
        }

        return new SatCatalogosNotFoundException($exMessage);
    }

    public function existsId(string $catalog, string $id): bool
    {
        $sql = 'select count(*) '
            . ' from ' . $this->catalogName($catalog)
            . ' where (id = :id);';
        $value = $this->queryValue($sql, ['id' => $id], 0);
        return (1 === (int) $value);
    }

    public function escapeName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    public function catalogName(string $catalog): string
    {
        if (! in_array($catalog, self::CATALOGS, true)) {
            throw new SatCatalogosLogicException("The catalog name $catalog is not recognized by the repository");
        }

        return $this->escapeName($catalog);
    }

    /**
     * Execute a sql statement, it will use the preparedStatements cache, set the arguments and throw an exception
     * with the corresponding message (if working on silent mode)
     * @param string $query
     * @param mixed[] $arguments
     * @return PDOStatement<mixed>
     */
    private function query(string $query, array $arguments = []): PDOStatement
    {
        $statement = $this->statement($query);
        $statement->execute($arguments);
        return $statement;
    }

    /**
     * Get one and only one value after executing a query.
     * NOTICE: Do not use this function for boolean values
     *
     * @param string $query
     * @param mixed[] $arguments
     * @param mixed $defaultValue
     * @return mixed
     */
    private function queryValue(string $query, array $arguments = [], $defaultValue = null)
    {
        $stmt = $this->query($query, $arguments);
        $value = $stmt->fetchColumn(0);

        return (false !== $value) ? $value : $defaultValue;
    }

    /**
     * @param string $query
     * @param mixed[] $arguments
     * @return array<string, mixed>
     */
    private function queryRow(string $query, array $arguments = []): array
    {
        $stmt = $this->query($query, $arguments);
        $values = $stmt->fetch(PDO::FETCH_ASSOC);

        return (is_array($values)) ? $values : [];
    }

    /**
     * Cache or create a prepared statement
     *
     * @param string $query
     * @return PDOStatement<mixed>
     */
    private function statement(string $query): PDOStatement
    {
        $statement = $this->statements[$query] ?? null;
        if ($statement instanceof PDOStatement) {
            return $statement;
        }

        try {
            /**
             * @noinspection PhpUsageOfSilenceOperatorInspection
             * @var PDOStatement<mixed>|false $statement phpstan does not know that prepare can return FALSE
             */
            $statement = @$this->pdo->prepare($query);
        } catch (PDOException $exception) {
            throw new LogicException("Cannot prepare the statement: $query", 0, $exception);
        }
        if (false === $statement) {
            throw new LogicException("Cannot prepare the statement: $query");
        }
        $this->statements[$query] = $statement;

        return $statement;
    }
}
