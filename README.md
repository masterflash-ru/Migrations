# Migrations
 Migrations - система миграций базы данных

Предназначена для управления версионированием базы данных приложений на ZF3 и является консольным приложением.

Установка composer require masterflash-ru/migrations

Все команды нужно выполнять из корневого каталога вашего приложения, например, ./vendor/bin/migrations help - выводит справку по командам, далее 
'./vendor/bin/' мы будем опускать для простоты.

## Опции
По умолчанию:
```php

    'migrations' => [
        'dir' => './data/migrations',           //папка куда записываются файлы новых миграций
        "connection" =>"DefaultSystemDb",       // имя соединения с базой из конфига вашего приложения
    ],
```
Переопределите эти параметры в конфиге вашего приложения при необходимости.

## Команды
- migrations status [namespase]- выводит всю информацию по загруженным и не загруженным миграциям, namespase - ведется поиск только для указанного пространства имен
- migrations generate [namespase]  создает пустую миграцию в папке ./data/migrations
- migrations execute version --up  загружает указанную версию миграции (version - версия в формате ГГГГММДДЧЧММСС)
- migrations execute version --down  выгружает указанную версию миграции (version - версия в формате ГГГГММДДЧЧММСС)
- migrations version --add Записывает в таблицу версий информацию о миграции без реальной загрузки (version - версия в формате ГГГГММДДЧЧММСС)
- migrations version --delete Удаляет из таблицы версий информацию о миграции без реальной загрузки (version - версия в формате ГГГГММДДЧЧММСС)

Позже будет описание дополнительных параметров