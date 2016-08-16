<?php

namespace Lthrt\CarveBundle\Model;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

class TableMaker
{
    use \Lthrt\CarveBundle\Traits\Model\GetSetTrait;

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
        var_dump($source);
        $items = $this->getItemsFromSource($source);
        var_dump($items);
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

        $schema = new Schema();
        foreach ($items as $class => $properties) {
            $class         = strtolower($class);
            $table[$class] = $schema->createTable($class);
            $table[$class]->addColumn('id', 'integer', ['unsigned' => true, 'strategy' => 'AUTO']);
            $table[$class]->setPrimaryKey(['id']);

            $schema->createSequence($class . "_id_seq");
            if ($postgres) {
                $extraSqls[] = 'ALTER TABLE ' . $class . ' ALTER id SET DEFAULT nextval(\'' . $class . '_id_seq\');';
            }

            foreach ($properties as $property => $type) {
                if ($type == 'string') {
                    $table[$class]->addColumn($property, 'string', ['length' => 255]);
                } else {
                    $table[$class]->addColumn($property, 'string', []);
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

    public function makeRecords(
        $source,
        $filename
    ) {
        $max    = intval(count(array_keys($source)) / 3);
        $header = false;

        $structures = [];

        foreach (range(0, $max - 1) as $item) {
            // the second [$item] is very important
            // it preserves the index from the $source array so it can
            // be referenced and pull the proper data for insert
            $structures[$source['class' . $item]][$item] = $source['field' . $item];
        }

        if (($file = fopen($filename, "r")) !== false) {
            if ($header) {
                $data = fgetcsv($file, 1000, ",");
            }

            $conn = $this->doctrine->getManager()->getConnection();
            $conn->beginTransaction();

            try {
                foreach ($structures as $table => $structure) {
                    $sql = 'INSERT INTO ' . $table . ' (';
                    $sql .= implode(',', array_values($structure));
                    $sql .= ') VALUES (';
                    $sql .= implode(',',
                        array_map(
                            function ($i) {return ':' . $i;},
                            array_values($structure))
                    );
                    $sql .= ');';

                    $stmt[$table] = $conn->prepare($sql);
                    foreach ($structure as $key => $field) {
                        $stmt[$table]->bindParam($field, $$field);
                    }

                }
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }

        while (($data = fgetcsv($file, 1000, ",")) !== false) {
            foreach ($structures as $table => $structure) {
                foreach ($structure as $key => $value) {
                    $$value = $data[$key];
                }
                $stmt[$table]->execute();
            }
        }

        $conn->commit();
    }

    private function cleanSource($source)
    {
        if (isset($source['_token'])) {unset($source['_token']);}
        if (isset($source['submit'])) {unset($source['submit']);}
        return $source;
    }

    public function getItemsFromSource($source)
    {
        $max = intval(count(array_keys($source)) / 3);

        foreach (range(0, $max - 1) as $item) {
            $items[$source['class' . $item]] = [];
        }

        foreach (range(0, $max - 1) as $item) {
            $items[$source['class' . $item]][$source['field' . $item]] = $source['type' . $item];
        }

        return $items;
    }
}
