<?php

namespace Mf\Migrations;

use Mf\Migrations\AbstractMigration;
use Mf\Migrations\MigrationInterface;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql;

class Version19700101000001 extends AbstractMigration implements MigrationInterface
{
    public static $description = "Create migrations table";
    
    
    public function up($schema,$adapter)
    {
        $table = new Ddl\CreateTable("migration_versions");
        $table->addColumn(new Ddl\Column\Varchar('version',14,false,"00000000000000",["COMMENT"=>"версия файла (вырезано из имени)"]));
        $table->addColumn(new Ddl\Column\Varchar('namespace', 255,true,null,["COMMENT"=>"пространство имен"]));
        $table->addColumn(new Ddl\Column\Datetime('executed_at', true,null,["COMMENT"=>"дата загрузки миграции"]));
        $table->addColumn(new Ddl\Column\Varchar('description', 255,true,null,["COMMENT"=>"описание миграции"]));
        $table->addConstraint(
            new Ddl\Constraint\PrimaryKey(['version', 'namespace'])
        );
        $this->addSql($table);
        $insert = new Sql\Insert("migration_versions");
        $insert->values([
            'version' => '19700101000001',
            'namespace' => 'Mf\Migrations',
            'executed_at'=>date("Y-m-d H:i:s"),
            'description'=>'Сама система миграции'
        ]);
        $this->addSql($insert);
        $this->start_migration_system=true;
    }

    public function down($schema,$adapter)
    {
        $drop = new Ddl\DropTable('migration_versions');
        $this->addSql($drop);
    }
}
