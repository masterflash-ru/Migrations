<?php
namespace Mf\Migrations;

use ADO\Service\Connection;
use Zend\Db\Adapter\Adapter;


interface MigrationInterface
{
    /**
     * Get migrations queries
     *
     * @return array
     */
    public function getUpSql();

    /**
     * Get migration rollback queries
     *
     * @return array
     */
    public function getDownSql();

    /**
     * Apply migration
     *
     * @param MetadataInterface $schema
     */
    public function up(Connection $schema,Adapter $adapter);

    /**
     * Rollback migration
     *
     * @param MetadataInterface $schema
     */
    public function down(Connection $schema,Adapter $adapter);
}
