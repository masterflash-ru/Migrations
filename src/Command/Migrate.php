<?php
/**
 */

namespace Mf\Migrations\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Mf\Migrations\MigrationInterface;





class Migrate extends AbstractCommand
{


    protected static $defaultName = 'migrate';


    protected function configure()
    {

        $this
            ->setDescription(
                $this->translator->translate('Execute a migration to a specified version or the latest available version.')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.',
                'latest'
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to output the migration SQL file instead of executing it. Defaults to current working directory.',
                false
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.'
            )
            ->addOption(
                'allow-no-migration',
                null,
                InputOption::VALUE_NONE,
                'Do not throw an exception if no migration is available.'
            )
            ->addOption(
                'all-or-nothing',
                null,
                InputOption::VALUE_OPTIONAL,
                'Wrap the entire migration in a transaction.',
                false
            )->setHelp(<<<EOT
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name%</info>

You can optionally manually specify the version you wish to migrate to:

    <info>%command.full_name% YYYYMMDDHHMMSS</info>

You can specify the version you wish to migrate to using an alias:

    <info>%command.full_name% prev</info>
    <info>These alias are defined : first, latest, prev, current and next</info>

You can specify the version you wish to migrate to using an number against the current version:

    <info>%command.full_name% current+3</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

You can also time all the different queries if you wanna know which one is taking so long:

    <info>%command.full_name% --query-time</info>

Use the --all-or-nothing option to wrap the entire migration in a transaction.
EOT
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $question = $this->translator->translate('WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)');;

        /*if (! $this->canExecute($question, $input, $output)) {
            $output->writeln('<error>'.$this->translator->translate("Migration cancelled!").'</error>');
            return 1;
        }*/
        
        //загрузка миграций и выполнение
        $migrations=$this->searchMigrations(null,0);
        
        //ищем тип базы данных, по умолчанию это Mysql
        $db_type="mysql";
        foreach ($this->connection->Properties  as $p ){
            if($p->Name=="driver_name"){
                $db_type=strtolower($p->Value);
                break;
            }
        }
        

        foreach ($migrations as $m){
            $class_name=$m["namespace"]."\\".$m["class_name"];
            $class=new $class_name($db_type,$this->connection);
            if (!$class instanceof MigrationInterface){
                continue;
            }
            //собственно выполнение SQL
            $sql_array=$class->getUpSql();
            foreach ($sql_array as $sql){
                $this->connection->Execute($sql);
            }
            
            if ($class->isStartMigrationSystem()){
                //если у нас вообще старт системы миграции, то прерываем все и выводим что 
                //нужно запустить процесс миграции вновь
                $outputStyle = new OutputFormatterStyle('green', 'default', ['bold', 'blink']);
                $output->getFormatter()->setStyle('startmigration', $outputStyle);
                $output->writeln('<startmigration> => '.$this->translator->translate('The migration system is initialized, reload the migrations!').' <=</startmigration>');
                exit ;
            }
            //запишем в таблицу загрузку
            $this->rs->AddNew();
            $this->rs->Fields->Item["version"]->Value=$m["version"];
            $this->rs->Fields->Item["namespace"]->Value=$m["namespace"];
            $this->rs->Fields->Item["executed_at"]->Value=date("Y-m-d H:i:s"); 
            $this->rs->Fields->Item["description"]->Value=$m["description"];
            $this->rs->Update();

        }
        

    }
    
}