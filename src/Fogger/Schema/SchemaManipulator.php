<?php

namespace App\Fogger\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema as DBAL;

class SchemaManipulator
{
    private $sourceSchema;

    private $targetSchema;

    public function __construct(Connection $source, Connection $target)
    {
        $this->sourceSchema = $source->getSchemaManager();
        $this->targetSchema = $target->getSchemaManager();
    }

    /**
     * @throws DBAL\SchemaException
     */
    public function copySchemaDroppingIndexesAndForeignKeys()
    {
        $sourceTables = $this->sourceSchema->listTables();
        /** @var DBAL\Table $table */
        foreach ($sourceTables as $table) {
            foreach ($table->getColumns() as $column) {
                $column->setAutoincrement(false);
            }
            foreach ($table->getForeignKeys() as $fk) {
                $table->removeForeignKey($fk->getName());
            }
            foreach ($table->getIndexes() as $index) {
                $table->dropIndex($index->getName());
            }
            $this->targetSchema->createTable($table);
        }
    }

    private function recreateIndexesOnTable(DBAL\Table $table)
    {
        foreach ($table->getIndexes() as $index) {
            $this->targetSchema->createIndex($index, $table->getName());
        }
        /** @var DBAL\Column $column */
        foreach ($table->getColumns() as $column) {
            if ($column->getAutoincrement()) {
                $this->targetSchema->alterTable(
                    new DBAL\TableDiff($table->getName(), [], [new DBAL\ColumnDiff($column->getName(), $column)])
                );
            }
        }
    }

    private function recreateForeignKeysOnTable(DBAL\Table $table)
    {
        foreach ($table->getForeignKeys() as $fk) {
            $this->targetSchema->createForeignKey($fk, $table->getName());
        }
    }

    public function recreateIndexes()
    {
        $sourceTables = $this->sourceSchema->listTables();
        foreach ($sourceTables as $table) {
            $this->recreateIndexesOnTable($table);
        }
    }

    public function recreateForeignKeys()
    {
        $sourceTables = $this->sourceSchema->listTables();
        foreach ($sourceTables as $table) {
            $this->recreateForeignKeysOnTable($table);
        }
    }
}
