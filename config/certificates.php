<?php

return [
    'types' => [
        'achievement' => [
            'label' => 'Achievement Certificate',
            'template' => 'certificates/achievement-template.pdf',
            'page' => [
                'orientation' => 'L',
                'format' => 'Letter',
            ],
            'name' => ['x' => 0, 'y' => 90],
            'graduation_date' => ['x' => 0, 'y' => 160],
        ],
    ],
];
