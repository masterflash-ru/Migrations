<?php

namespace Mf\Migrations;

use Zend\Router\Http\Literal;

return [
    'router' => [
        'routes' => [
            'clear-storage-cron' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/clear-storage-cron',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class =>Controller\IndexControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
