<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'SnB Dashboard',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '',
    'logo_img' => 'img/logo-with-text-small.png',
    'logo_img_class' => 'brand-image',
    'logo_img_xl' => null,
    // 'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => '',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'img/logo-with-text.png',
            'alt' => '',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => false,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'img/logo-with-text.png',
            'alt' => '',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => 'light-mode',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-light-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => '/',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => '',
    'password_reset_url' => '',
    'password_email_url' => '',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [


        [
            'text' => 'Client Management',
            'icon' => 'fas fa-users-cog',
            'can' => ['dropbox.view', 'client.view'],
            'submenu' => [
                [
                    'text' => 'Dropbox',
                    'url' => 'dropboxes?status=pending',
                    'icon' => 'fas fa-inbox',
                    'can' => 'dropbox.view',
                ],
                [
                    'text' => 'Clients',
                    'url' => 'clients',
                    'icon' => 'fas fa-user-friends',
                    'can' => 'client.view',
                ]

            ]

        ],

        [
            'text' => 'Task Manager',
            'icon' => 'fas fa-tasks',
            'can' => ['task.view', 'task_category.view', 'task_template.view', 'position.view', 'sub_task_template.view'],
            'submenu' => [
                [
                    'text' => 'Positions',
                    'url' => 'positions',
                    'icon' => 'fas fa-id-badge',
                    'can' => 'position.view',
                ],
                [
                    'text' => 'Task Categories',
                    'url' => 'task-categories',
                    'icon' => 'fas fa-tags',
                    'can' => 'task_category.view',
                ],
                [
                    'text' => 'Task Templates',
                    'url' => 'task-templates',
                    'icon' => 'fas fa-clipboard-list',
                    'can' => 'task_template.view',
                ],
                [
                    'text' => 'Task Pipeline',
                    'url' => 'tasks',
                    'icon' => 'fas fa-stream',
                    'can' => 'task.view',
                ],
                [
                    'text' => 'My Tasks',
                    'url' => 'tasks/assigned',
                    'icon' => 'fas fa-user-check',
                    'can' => 'task.view',
                ],
                [
                    'text' => 'Tasks',
                    'url' => 'tasks/list',
                    'icon' => 'fas fa-list-check',
                    'can' => 'task.view',
                ],
                [
                    'text' => 'Sub-Task Templates',
                    'url' => 'sub-task-templates',
                    'icon' => 'fas fa-list',
                    'can' => 'sub_task_template.view',
                ],
            ],
        ],

        [
            'text' => 'Housing',
            'icon' => 'fas fa-house-user',
            'can' => ['house.view', 'apartment.view'],
            'submenu' => [
                [
                    'text' => 'Houses',
                    'url' => 'houses',
                    'icon' => 'fas fa-home',
                    'can' => 'house.view',
                ],
                [
                    'text' => 'Apartments',
                    'url' => 'apartments',
                    'icon' => 'fas fa-building',
                    'can' => 'apartment.view',
                ],
            ],
        ],

        [
            'text' => 'Client Documents',
            'icon' => 'fas fa-envelope',
            'can'  => ['verification_letter.view', 'certificate.view'],
            'submenu' => [
                [
                    'text' => 'Verification Letters',
                    'url' => 'verification-letters',
                    'icon' => 'fas fa-envelope-open-text',
                    'can' => 'verification_letter.view',
                ],
                [
                    'text' => 'Certificates',
                    'url' => 'certificates',
                    'icon' => 'fas fa-award',
                    'can' => 'certificate.view',
                ],
            ],
        ],

        [
            'text' => 'Randomizer',
            'icon' => 'fas fa-random',
            'submenu' => [
                [
                    'text' => 'UA Randomizer',
                    'url' => 'ua-randomizers',
                    'icon' => 'fas fa-vial',
                    'can' => 'ua_randomizer.view',
                ],
                [
                    'text' => 'Chart Audit Randomizer',
                    'url' => 'chart-audit-randomizers',
                    'icon' => 'fas fa-file-medical',
                    'can' => 'chart_audit_randomizer.view',
                ],
            ],
        ],
        [
            'text' => 'Productivity',
            'icon' => 'fas fa-notes-medical',
            'submenu' => [
                [
                    'text' => 'Clinician',
                    'url' => 'clinical-notes',
                    'icon' => 'fas fa-clipboard',
                    'can' => 'clinical_notes.view',
                ],
                [
                    'text' => 'CPRS',
                    'icon' => 'fas fa-calendar-check',
                    'url' => 'cprs',
                    'can' => 'cprs.view',
                ],
            ],
        ],

        [
            'text' => 'Reports',
            'icon' => 'fas fa-chart-bar',
            'can' => [
                'report.attendance',
                'report.attendance_export',
                'report.attendance_tsv',
                'report.client_list',
                'report.clients_by_house',
                'report.clients_by_counselor',
                'report.clients_by_peer',
                'report.clients_by_group',
                'report.clients_by_peer_group',
                'report.intakes',
                'report.reactivations',
                'report.discharges',
                'report.hospitalizations',
                'report.transitions',
                'report.medicaid_by_date',
                'report.group_attendance_summary',
                'report.peer_group_attendance_summary',
                'report.total_client_list',
                'report.house',
                'report.audit_log',
            ],
            'submenu' => [
                ['text' => 'Attendance', 'url' => 'reports/attendance', 'can' => 'report.attendance'],
                ['text' => 'Attendance Bulk Export', 'url' => 'reports/attendance/export', 'can' => 'report.attendance_export'],
                ['text' => 'Attendance TSV', 'url' => 'reports/attendance-tsv', 'can' => 'report.attendance_tsv'],
                ['text' => 'Clients List', 'url' => 'reports/clients-list', 'can' => 'report.client_list'],
                ['text' => 'Clients by House', 'url' => 'reports/clients-by-house', 'can' => 'report.clients_by_house'],
                ['text' => 'Clients by Counselor', 'url' => 'reports/clients-by-counselor', 'can' => 'report.clients_by_counselor'],
                ['text' => 'Clients by Peer', 'url' => 'reports/clients-by-peer', 'can' => 'report.clients_by_peer'],
                ['text' => 'Clients by Group', 'url' => 'reports/clients-by-group', 'can' => 'report.clients_by_group'],
                ['text' => 'Clients by Peer Group', 'url' => 'reports/clients-by-peer-group', 'can' => 'report.clients_by_peer_group'],
                ['text' => 'Intakes', 'url' => 'reports/intakes', 'can' => 'report.intakes'],
                ['text' => 'Reactivations', 'url' => 'reports/reactivations', 'can' => 'report.reactivations'],
                ['text' => 'Discharges', 'url' => 'reports/discharges', 'can' => 'report.discharges'],
                ['text' => 'Hospitalizations', 'url' => 'reports/hospitalizations', 'can' => 'report.hospitalizations'],
                ['text' => 'Transitions', 'url' => 'reports/transitions', 'can' => 'report.transitions'],
                ['text' => 'Medicaid List by Date', 'url' => 'reports/medicaid-by-date', 'can' => 'report.medicaid_by_date'],
                ['text' => 'Group Attendance Summary', 'url' => 'reports/group-attendance-summary', 'can' => 'report.group_attendance_summary'],
                ['text' => 'Peer Group Attendance Summary', 'url' => 'reports/peer-group-attendance-summary', 'can' => 'report.peer_group_attendance_summary'],
                ['text' => 'Total Client List', 'url' => 'reports/total-client-list', 'can' => 'report.total_client_list'],
                ['text' => 'Houses', 'url' => 'reports/house', 'can' => 'report.house'],
                ['text' => 'Audit Log', 'url' => 'reports/audit-log', 'can' => 'report.audit_log'],
            ],
        ], 




        [
            'text' => 'Consents & Auths',
            'icon' => 'fas fa-file-signature',
            'can' => ['auth_release.view', 'authorization.view', 'consent.view'],
            'submenu' => [
                [
                    'text' => 'Auth to Release Info',
                    'url' => 'auth-to-release-info',
                    'icon' => 'fas fa-file-signature',
                    'can' => 'auth_release.view',
                ],
                [
                    'text' => 'Authorizations',
                    'url' => 'authorizations',
                    'icon' => 'fas fa-clipboard-check',
                    'can' => 'authorization.view',
                ],
                [
                    'text' => 'Consents',
                    'url' => 'consents',
                    'icon' => 'fas fa-file-contract',
                    'can' => 'consent.view',
                ],
            ]
        ],



        [
            'text' => 'Trackers',
            'icon' => 'fas fa-clipboard-list',
            'can' => ['authorization.view_tracker', 'eligibility_tracker.view', 'hospitalization_tracker.view', 'authorization_line.view'],
            'submenu' => [
                [
                    'text' => 'Auth Tracker',
                    'url' => 'authorizations/tracker',
                    'icon' => 'fas fa-route',
                    'can' => 'authorization.view_tracker',
                ],
                [
                    'text' => 'Eligibility Tracker',
                    'url' => 'authorizations/eligibility-tracker',
                    'icon' => 'fas fa-clipboard-list',
                    'can' => 'eligibility_tracker.view',
                ],
                [
                    'text' => 'Hospitalization Tracker',
                    'url' => 'hospitalizations/tracker',
                    'icon' => 'fas fa-procedures',
                    'can' => 'hospitalization_tracker.view',
                ],
                [
                    'text' => 'Denied Auths Tracker',
                    'url' => 'authorizations/denied-auths-tracker',
                    'icon' => 'fas fa-ban',
                    'can' => 'authorization_line.view',
                ],
                [
                    'text' => 'Pending Auths Tracker',
                    'url' => 'authorizations/pending-auths-tracker',
                    'icon' => 'fas fa-hourglass-half',
                    'can' => 'authorization_line.view',
                ],
                [
                    'text' => 'Chart Compliance',
                    'url' => 'clinical-notes/tracker',
                    'icon' => 'fas fa-notes-medical',
                    'can' => 'clinical_notes.view',
                ],
            ]
        ],

        [
            'text' => 'Bookings',
            'icon' => 'fas fa-calendar-check',
            'submenu' => [
                [
                    'text' => 'Booking Slots',
                    'url' => 'bookings',
                    'icon' => 'far fa-calendar',
                    'can' => 'booking_slot.view',
                ],
                [
                    'text' => 'Booking Windows',
                    'url' => 'booking-windows',
                    'icon' => 'fas fa-sliders-h',
                    'can' => 'booking_window.view',
                ],
            ],
        ],








        [
            'text' => 'Staff Documents',
            'url' => 'documents',
            'icon' => 'fas fa-folder-open',
            'can' => 'document.view',
        ],

        [
            'text' => 'Billing',
            'icon' => 'fas fa-file-invoice',
            'can' => ['claim.view', 'invoice.view', 'payment.view', 'check.view', 'attendance2026.view', 'attendance.batch'],
            'submenu' => [
                [
                    'text' => 'Attendance',
                    'url' => 'attendance/batch',
                    'icon' => 'fas fa-calendar-check',
                    'can' => 'attendance.batch',
                ],
                [
                    'text' => 'Attendance 2026',
                    'url' => 'attendance-2026',
                    'icon' => 'fas fa-calendar-check',
                    'can' => 'attendance2026.view',
                ],
                 [
                    'text' => 'Claims',
                    'url' => 'claims',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'can' => 'claim.view',
                ],
                [
                    'text' => 'Checks',
                    'url' => 'checks',
                    'icon' => 'fas fa-money-check-alt',
                    'can' => 'check.view',
                ],
            ],
        ],

        // ['header' => 'Administration'],
        [
            'text' => 'Admin',
            'icon' => 'fas fa-user-cog',
            'can' => ['user.list', 'role.view'],
            'submenu' => [
                [
                    'text' => 'Users',
                    'url' => 'users',
                    'icon' => 'fas fa-users-cog',
                    'can' => 'user.list',
                ],
                [
                    'text' => 'Roles',
                    'url' => 'roles',
                    'icon' => 'fas fa-user-shield',
                    'can' => 'role.view',
                ],
            ]

        ],
        [
            'text' => 'Account Settings',
            'icon' => 'fas fa-user-lock',
            'can' => ['user.reset_pin'],
            'submenu' => [
                [
                    'text' => 'PIN',
                    'url' => 'pin',
                    'icon' => 'fas fa-key',
                    'can' => 'user.reset_pin',
                ],
            ]
        ],




        [
            'text' => 'Practice Setup',
            'icon' => 'fas fa-briefcase-medical',
            'can' => ['client_group.view', 'peer_group.view', 'medical_contact.view', 'hospitalization_facility.view', 'level_of_care.view', 'service_code.view'],
            'submenu' => [
                [
                    'text' => 'Client Groups',
                    'url' => 'client-groups',
                    'icon' => 'fas fa-layer-group',
                    'can' => 'client_group.view',
                ],
                [
                    'text' => 'Email Templates',
                    'url' => 'email-templates',
                    'icon' => 'fas fa-envelope-open-text',
                    'can' => 'email_template.manage',
                ],
                [
                    'text' => 'Peer Groups',
                    'url' => 'peer-groups',
                    'icon' => 'fas fa-users',
                    'can' => 'peer_group.view',
                ],
                [
                    'text' => 'Medical Contacts',
                    'url' => 'medical-contacts',
                    'icon' => 'fas fa-notes-medical',
                    'can' => 'medical_contact.view',
                ],
                [
                    'text' => 'Hospitalization Facilities',
                    'url' => 'hospitalization-facilities',
                    'icon' => 'fas fa-procedures',
                    'can' => 'hospitalization_facility.view',
                ],
                [
                    'text' => 'Levels of Care',
                    'url' => 'level-of-cares',
                    'icon' => 'fas fa-clinic-medical',
                    'can' => 'level_of_care.view',
                ],
                [
                    'text' => 'Service Codes',
                    'url' => 'service-codes',
                    'icon' => 'fas fa-notes-medical',
                    'can' => 'service_code.view',
                ],
                [
                    'text' => 'Certificate Types',
                    'url' => 'certificate-types',
                    'icon' => 'fas fa-certificate',
                    'can' => 'certificate-type.view',
                ],
            ]
        ],


    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'PinModal' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'js/app.js',
                ],
            ],
        ],
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
