<?php
return [
    'controllers' => array(
        'value' => array(
            'namespaces' => array(
                '\\Awz\\Bx24Lead\\Api\\Controller' => 'api'
            )
        ),
        'readonly' => true
    ),
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzbx24lead-user',
                    'provider' => [
                        'moduleId' => 'awz.bx24lead',
                        'className' => '\\Awz\\Bx24lead\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzbx24lead-group',
                    'provider' => [
                        'moduleId' => 'awz.bx24lead',
                        'className' => '\\Awz\\Bx24lead\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];