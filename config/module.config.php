<?php declare(strict_types=1);

namespace EditingExtensions;

return [
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            UsedTermCache::class => Service\UsedTermCacheFactory::class,
        ],
    ],
];
