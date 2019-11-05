<?php

return [
    "Configuration"                 =>  "Конфигурация",
    "Connection name:"              =>  "Имя соединения:",
    "Database Name:"                =>  "Имя базы данных:",
    "Migrations Directory:"         =>  "Каталог для файлов миграций:",
    "Default Namespace:"            =>  "Пространство имен по умолчанию:",
    "Total Executed Migrations:"    =>  "Всего загружено миграций:",
    "NameSpace:"                    =>  "Пространство имен:",
    "NameSpace"                    =>  "Пространство имен",
    "Namespace migration"           =>  "Пространство имен миграции",
    "Executed Migrations:"          =>  "Загружено миграций:",
    "Current Version:"              =>  "Текущая версия:",
    "Next Version:"                 =>  "Следуюущая версия",
    "Latest Version:"               =>  "Последняя версия:",
    "Available Migrations:"          =>  "Доступно миграций:",
    "Available Migration Versions:" =>  "Доступные миграци версий:",
    "Migration cancelled!"          =>  "Загрузка миграции отменена",
    "Unable to create file"         =>  "Невозможно создать файл",
    "Migration not found!"          =>  "Миграция не найдена",
    "Migrations table not found in database"=>"Таблица с миграциями не обнаружена в базе данных",
    "The migration system is initialized, reload the migrations!"=>"Система миграций инициализирована, выполните загрузку миграций повторно!",
    "Manually add and delete migration versions from the version table."=>"Ручное добавление/удаление миграций в таблицу, без их выполнения",
    "The version to add or delete."=>"Версия миграции для добавления/удаления",
    'Add the specified version.'=>"Добавление указанной миграции",
    'Delete the specified version.'=>"Удаление указанной миграции",
    'Apply to all the versions.'=>"Применить ко всем версиям",
    "Multiple versions of migrations detected, use the --namespace option"=>"Обнаружено несколько версий миграций, используйте параметр --namespace",
    "Generate a blank migration class."=>"Генерация пустого класса миграции",
    'Parameter conflict: migration version and --all key'=>"Конфликт параметров: версия миграции и ключ --all",
    "You must specify whether you want to --add or --delete the specified version."=>"Необходимо использовать --add или --delete для операции.",
    
    'Execute a migration to a specified version or the latest available version.'=>"Выполнить миграцию до указанной версии или последней доступной версии.",
    
    'WARNING! You are about to add, delete or synchronize migration versions from the version table that could result in data lost. Are you sure you wish to continue? (y/n)'=>
        'Внимание! Вы собираетесь добавить, удалить или синхронизировать версии миграции из таблицы версий, что может привести к потере данных. Вы уверены, что хотите продолжить? (y/n)',
    
    "View the status of a set of migrations."=>"Просмотр состояния набора миграций.",
    
"The <info>%command.name%</info> command outputs the status of a set of migrations:\n<info>%command.full_name%</info>\n"
    =>"Команда <info>%command.name%</info> выводит состояние набора миграций:\n<info>%command.full_name%</info>\n",
    
    'Execute a single migration version up or down manually.'=>'Выполнить или откатить одну миграцию вручную',
    'The path to output the migration SQL file instead of executing it. Defaults to current working directory.'=>'Путь и/или имя файла SQL миграции куда будет записаны SQL. По умолчанию используется data/migrations',
    'Execute the migration as a dry run.'=>"Прогон миграций без реальной загрузки",
    'Execute the migration up.'=>'Выполнить миграцию',
    'Execute the migration down.'=>'Откатить миграцию',
    
    "WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)" =>
            "Внимание! Вы собираетесь выполнить миграцию базы данных, которая может привести к изменению схемы и потере данных. Вы уверены, что хотите продолжить? (y/n)",
    
];