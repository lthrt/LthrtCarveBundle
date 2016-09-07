<?php

namespace Lthrt\CarveBundle\Model;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;

class TableMaker
{
    use \Lthrt\CarveBundle\Traits\Model\GetSetTrait;

    const FIELDS    = 4;
    const IMPORTTAG = "_import";
    const IMPORTSC  = "import.";

    /**
     * @var Templating Engine
     */
    private $templating;

    /**
     * @var Doctrine
     */
    private $doctrine;

    public function __construct(
        $templating = [],
        $doctrine
    ) {
        $this->templating = $templating;
        $this->doctrine   = $doctrine;
    }

    public function makeTable($source = [])
    {
        $items     = $this->getItemsFromSource($source);
        $conn      = $this->doctrine->getManager()->getConnection();
        $extraSqls = [];
        if ('pdo_pgsql' == $conn->getParams()['driver']) {
            $platform = new PostgreSqlPlatform();
            $postgres = true;
        } elseif ('pdo_mysql' == $conn->getParams()['driver']) {
            $platform = new MySqlPlatform();
            $driver   = false;
        } else {
            throw new Exception('Only Postgresql and MySql are supported');
        }

        $schemaConfig = new SchemaConfig();
        $schemaConfig->setName('import');
        $schema = new Schema([], [], $schemaConfig, ['public', 'import']);

        $tables[self::IMPORTSC . 'raw_import'] = $schema->createTable(self::IMPORTSC . 'raw_import');
        $schema->createSequence(self::IMPORTSC . "raw_import_id_seq");
        $tables[self::IMPORTSC . 'raw_import']->addColumn('id', 'integer', ['unsigned' => true, 'strategy' => 'AUTO']);
        $tables[self::IMPORTSC . 'raw_import']->setPrimaryKey(['id']);
        if ($postgres) {
            $extraSqls[] = 'ALTER TABLE ' . self::IMPORTSC . 'raw_import ALTER id SET DEFAULT nextval(\'' . self::IMPORTSC . 'raw_import_id_seq\');';
        }

        foreach ($items as $class => $properties) {
            $class = strtolower($class);

            $tables[self::IMPORTSC . $class] = $schema->createTable(self::IMPORTSC . $class . self::IMPORTTAG);

            $tables[self::IMPORTSC . $class]->addColumn('id', 'integer', ['unsigned' => true, 'strategy' => 'AUTO']);
            $schema->createSequence(self::IMPORTSC . $class . self::IMPORTTAG . "_id_seq");
            if ($postgres) {
                $extraSqls[] = 'ALTER TABLE ' . self::IMPORTSC . $class . self::IMPORTTAG . ' ALTER id SET DEFAULT nextval(\'' . self::IMPORTSC . $class . self::IMPORTTAG . '_id_seq\');';
            }

            foreach ($properties as $property => $type) {
                if ('string' == $type) {
                    $tables[self::IMPORTSC . $class]->addColumn($property, 'string', ['length' => 255, 'notnull' => false]);
                    $tables[self::IMPORTSC . 'raw_import']->addColumn($class . '_' . $property, 'string', ['length' => 255, 'notnull' => false]);
                } else {
                    $tables[self::IMPORTSC . $class]->addColumn($property, $type, ['notnull' => false]);
                    $tables[self::IMPORTSC . 'raw_import']->addColumn($class . '_' . $property, $type, ['notnull' => false]);
                }
            }
        }

        $conn->beginTransaction();

        try {
            foreach ($schema->toSql($platform) as $sql) {
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
            if ($postgres) {
                foreach ($extraSqls as $extraSql) {
                    $stmt = $conn->prepare($extraSql);
                    $stmt->execute();
                }

            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function makeImport(
        $source,
        $filename
    ) {
        $max    = intval(count(array_keys($source)) / self::FIELDS);
        $header = false;

        // $structures = $this->getItemsFromSource($source);
        $structures = [];
        $rawFields  = [];

        foreach (range(0, $max - 1) as $item) {
            // the second [$item] is very important
            // it preserves the index from the $source array so it can
            // be referenced and pull the proper data for insert
            $structures[$source['class' . $item]][$item] = $source['field' . $item];
            $rawFields[]                                 = strtolower($source['class' . $item] . '_' . $source['field' . $item]);
        }

        if (($file = fopen($filename, "r")) !== false) {
            if ($header) {
                $data = fgetcsv($file, 1000, ",");
            }

            $conn = $this->doctrine->getManager()->getConnection();
            $conn->beginTransaction();
            try {
                $table  = 'raw';
                $rawSql = 'INSERT INTO ' . self::IMPORTSC . $table . self::IMPORTTAG . ' (';
                $rawSql .= implode(',', array_values($rawFields));
                $rawSql .= ') SELECT ';
                $rawSql .= implode(',',
                    array_map(
                        function (
                            $k,
                            $i
                        ) use ($source, $rawFields) {
                            return ':' . $i;
                        },
                        array_keys($rawFields),
                        array_values($rawFields))
                );

                $rawSql .= ' WHERE NOT EXISTS (';
                $rawSql .= 'SELECT * FROM ' . self::IMPORTSC . $table . self::IMPORTTAG;
                $rawSql .= ' WHERE ';
                $rawSql .= implode(' AND ',
                    array_map(
                        function (
                            $k,
                            $i
                        ) use ($source, $rawFields) {
                            return $i . ' = :' . $i . '0';
                        },
                        array_keys($rawFields),
                        array_values($rawFields))
                );
                $rawSql .= ')';

                $rawSql .= ';';

                $rawStmt = $conn->prepare($rawSql);
                foreach ($rawFields as $key => $rawField) {
                    $rawStmt->bindParam($rawField, $$rawField);
                    $rawStmt->bindParam($rawField . '0', $$rawField);
                }

                foreach ($structures as $table => $structure) {
                    $sql = 'INSERT INTO ' . self::IMPORTSC . $table . self::IMPORTTAG . ' (';
                    $sql .= implode(',', array_values($structure));
                    $sql .= ') SELECT ';
                    $sql .= implode(',',
                        array_map(
                            function (
                                $k,
                                $i
                            ) use ($source, $structure) {
                                return ':' . $i;
                            },
                            array_keys($structure),
                            array_values($structure))
                    );

                    $sql .= ' WHERE NOT EXISTS (';
                    $sql .= 'SELECT * FROM ' . self::IMPORTSC . $table . self::IMPORTTAG;
                    $sql .= ' WHERE ';
                    $sql .= implode(' AND ',
                        array_map(
                            function (
                                $k,
                                $i
                            ) use ($source, $structure) {
                                return $i . ' = :' . $i . '0';
                            },
                            array_keys($structure),
                            array_values($structure))
                    );
                    $sql .= ')';

                    $sql .= ';';
                    print($sql);
                    print "<br/><br/>";
                    $stmt[$table] = $conn->prepare($sql);
                    foreach ($structure as $key => $field) {
                        $stmt[$table]->bindParam($field, $$field);
                        if ('key' == $source["type" . $key]) {
                            // $stmt[$table]->bindParam($field . '_x', $$field);
                        } else {
                            $stmt[$table]->bindParam($field . '0', $$field);
                        }
                    }
                }
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }

        while (($data = fgetcsv($file, 1000, ",")) !== false) {
            foreach ($rawFields as $key => $value) {
                $$value = (($data[$key]) ?: null);
            }

            $rawStmt->execute();

            foreach ($structures as $table => $structure) {
                foreach ($structure as $key => $value) {
                    $$value = (($data[$key]) ?: null);
                }
                $stmt[$table]->execute();
            }
        }

        $conn->commit();
    }

    public function cleanSource($source)
    {
        if (isset($source['_token'])) {unset($source['_token']);}
        if (isset($source['submit'])) {unset($source['submit']);}
        return $source;
    }

    public function getItemsFromSource($source)
    {
        $max = intval(count(array_keys($source)) / self::FIELDS);

        foreach (range(0, $max - 1) as $item) {
            $items[$source['class' . $item]] = [];
        }

        foreach (range(0, $max - 1) as $item) {
            $items[$source['class' . $item]][$source['field' . $item]] = $source['type' . $item];
        }

        return $items;
    }
}
