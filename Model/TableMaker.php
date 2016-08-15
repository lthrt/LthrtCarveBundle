<?php

namespace Lthrt\CarveBundle\Model;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
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
        $items = $this->getItemsFromSource($source);

        $platform = new PostgreSQL92Platform();

        $schema = new Schema();
        foreach ($items as $class => $properties) {
            $table[$class] = $schema->createTable($class);
            $table[$class]->addColumn('id', 'integer');
            $table[$class]->setPrimaryKey(['id']);

            $schema->createSequence($class . "_id_seq");
            foreach ($properties as $property => $type) {
                if ($type == 'string') {
                    $table[$class]->addColumn($property, 'string', ['length' => 255]);
                } else {
                    $table[$class]->addColumn($property, 'string', []);
                }
            }
        }

        $conn = $this->doctrine->getManager()->getConnection();
        $conn->beginTransaction();

        try {
            foreach ($schema->toSql($platform) as $sql) {
                $stmt = $conn->prepare($sql);
                $stmt->execute();
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
                    $stmt[$table]->bindParam($field, $data[$key]);
                }

                // $last = end($structure);
                // foreach ($structure as $key => $field) {
                //     if (in_array($source['type' . $key], ['text', 'string', 'date', 'datetime'])) {
                //         $sql .= '\'' . $data[$key] . '\'';
                //     } elseif (in_array($source['type' . $key], ['integer', 'string'])) {
                //         $sql .= $data[$key];
                //     }

                //     if ($field === $last) {
                //     } else {
                //         $sql .= ",";
                //     }
                // }
                var_dump($sql);
            }

            while (($data = fgetcsv($file, 1000, ",")) !== false) {
                var_dump($data);
                foreach (array_keys($structures) as $table) {
                    $stmt[$table]->execute();
                }
            }
        }
        die;
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
