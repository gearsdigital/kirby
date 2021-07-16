<?php


return function ($kirby) {
    return [
        'breadcrumbLabel' => function () use ($kirby) {
            return $kirby->site()->title()->or(t('view.site'))->toString();
        },
        'icon'    => 'home',
        'label'   => t('view.site'),
        'menu'    => true,
        'dialogs' => require __DIR__ . '/site/dialogs.php',
        'views'   => require __DIR__ . '/site/views.php'
    ];
};