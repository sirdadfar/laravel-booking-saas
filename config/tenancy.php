<?php

return [
    /*
     * Base domains used to turn "demo.example.com" into tenant "demo".
     * The bare base domain is never a tenant.
     */
    'base_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_BASE_DOMAINS', 'localhost,example.test')),
    ))),

    /*
     * Header used by API clients and tests. Path resolution always wins.
     */
    'header' => (string) env('TENANCY_HEADER', 'X-Tenant'),
];
