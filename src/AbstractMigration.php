<?php
namespace Mf\Migrations;


abstract class AbstractMigration implements MigrationInterface
{
    /**массив SQL запросов*/
    protected $sql = [];
    
    /**тип базы данных, по умолчанию mysql*/
    protected $db_type;
    
    /**соединение с базой, экземпляр Connection пакета ADO*/
    protected $connection;
    
    /**специальный флаг, равен true, когда загружается сама таблица миграций*/
    protected $start_migration_system=false;

    public function __construct($db_type="mysql",$connection)
    {
        $this->db_type = strtolower($db_type);
        $this->connection=$connection;
    }
    
    /**
    * проверим у нас вообще старт миграций?
    */
    public function isStartMigrationSystem()
    {
        return $this->start_migration_system;
    }
    
    /**
     * Add migration query
     *
     * @param string $sql
     */
    protected function addSql($sql)
    {
        $this->sql[] = $sql;
    }

    /**
     * Get migration queries
     *
     * @return array
     */
    public function getUpSql()
    {
        $this->sql = [];
        $this->up($this->connection);

        return $this->sql;
    }

    /**
     * Get migration rollback queries
     *
     * @return array
     */
    public function getDownSql()
    {
        $this->sql = [];
        $this->down($this->connection);

        return $this->sql;
    }

    /**
     * @return OutputWriter
     */
    protected function getWriter()
    {
        return $this->writer;
    }
}
