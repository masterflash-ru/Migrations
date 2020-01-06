<?php
namespace Mf\Migrations;

use Laminas\Db\Sql\Ddl\SqlInterface;
use Laminas\Db\Sql\SqlInterface as SqlInterfaceIO;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Ddl;

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
    
    /**Инициализированный адаптер ZF3*/
    protected $adapter;
    
    /**опция для Mysql при создании таблицы, если нужен MyiSam - просто определите его у себя в миграции */
    protected $mysql_add_create_table=" ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct($db_type="mysql",$connection)
    {
        $this->db_type = strtolower($db_type);
        $this->connection=$connection;
        $this->adapter=$connection->getZfAdapter();
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
     * @param string $sql | Laminas\Db\Sql\Ddl\SqlInterface
     */
    protected function addSql($sql)
    {
        if ($sql instanceof SqlInterface || $sql instanceof SqlInterfaceIO){
            $s=new Sql($this->adapter);
            if ($sql instanceof Ddl\CreateTable  && $this->db_type=="mysql"){
                //костыли для Mysql, добавление строки имени движка и кодировки
                $add=$this->mysql_add_create_table;
            } else {
                $add="";
            }
            //исключим двойное экранирование
            $sql=stripslashes($s->buildSqlString($sql).$add);
        }
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
        $this->up($this->connection,$this->adapter);

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
        $this->down($this->connection,$this->adapter);

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
