<?php

declare(strict_types=1);

return [
    'user' => [
        'router' => [
            'prefix' => null,
        ],
    ],

    'yiisoft/aliases' => [
        'aliases' => [
            '@avatars' => '@assets/images/avatar',
            '@user' => dirname(__DIR__),
        ],
    ],

    'yiisoft/yii-db-migration' => [
        'updateNamespaces' => [
            'Yii\\Extension\\User\\Migration',
        ],
    ],
];
