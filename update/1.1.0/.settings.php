<?php
return [
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