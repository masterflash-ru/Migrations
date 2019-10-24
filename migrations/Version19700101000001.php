<?php

namespace Mf\Migrations;

use Mf\Migrations\AbstractMigration;
use Mf\Migrations\MigrationInterface;


class Version19700101000001 extends AbstractMigration implements MigrationInterface
{
    public static $description = "Create migrations table";
    
    
    public function up($schema)
    {
        switch ($this->db_type){
            case "mysql":{
                $this->addSql("CREATE TABLE `migration_versions` (
                          `version` char(14) NOT NULL DEFAULT 0 COMMENT 'версия файла (вырезано из имени)',
                          `namespace` char(255) DEFAULT NULL COMMENT 'пространство имен',
                          `executed_at` datetime DEFAULT NULL COMMENT 'дата загрузки миграции',
                          `description` char(255) DEFAULT NULL COMMENT 'описание миграции',
                          PRIMARY KEY (`version`,`namespace`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                $this->addSql('insert into `migration_versions` (`version`, `namespace`, `executed_at`,`description`)
                                value ("19700101000001","Mf\\\\Migrations",now(),"Сама система миграции") ');
                break;
            }
            default:{
                throw new \Exception("the database {$this->db_type} is not supported !");
            }
        }
        $this->start_migration_system=true;
    }

    public function down($schema)
    {
        $this->addSql("drop table migration_versions");
    }
}
