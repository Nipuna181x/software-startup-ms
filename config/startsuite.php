<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Startsuite Brand Colour
    |--------------------------------------------------------------------------
    |
    | New organization records retain a colour value for compatibility with
    | existing data. The interface itself uses the fixed Startsuite palette
    | defined in resources/css/app.css.
    |
    */

    'brand_color' => '#b8f66b',

    /*
    |--------------------------------------------------------------------------
    | Logo Uploads
    |--------------------------------------------------------------------------
    |
    | SVG is deliberately excluded because it can carry scripts.
    |
    */

    'logo' => [
        'disk' => 'public',
        'directory' => 'organization-logos',
        'max_kilobytes' => 2048,
        'mimes' => ['png', 'jpg', 'jpeg', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Calling Module: Business Screenshots
    |--------------------------------------------------------------------------
    |
    | Screenshots live on the private `local` disk (storage/app/private), not
    | the public disk used for logos, because they may contain a business's
    | private contact details. They are served through an authorized route
    | that checks the owning business belongs to the viewer's organization.
    |
    */

    'business_screenshot' => [
        'disk' => 'local',
        'directory' => 'business-screenshots',
        'max_kilobytes' => 4096,
        'mimes' => ['png', 'jpg', 'jpeg', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Seed Data
    |--------------------------------------------------------------------------
    |
    | Used by DemoOrganizationSeeder for local testing. The password has no
    | default on purpose: without DEMO_ADMIN_PASSWORD set the seeder skips,
    | so no known credentials can ever reach a deployed environment.
    |
    */

    'platform_admin' => [
        'email' => env('PLATFORM_ADMIN_EMAIL', 'admin@startsuite.test'),
        'password' => env('PLATFORM_ADMIN_PASSWORD'),
    ],

    'demo' => [
        'organization' => env('DEMO_ORGANIZATION', 'Northwind Logistics'),
        'slug' => env('DEMO_ORGANIZATION_SLUG', 'northwind-logistics'),
        'color' => env('DEMO_ORGANIZATION_COLOR', '#b8f66b'),
        'name' => env('DEMO_ADMIN_NAME', 'Demo Admin'),
        'email' => env('DEMO_ADMIN_EMAIL', 'admin@northwind.test'),
        'password' => env('DEMO_ADMIN_PASSWORD'),
    ],

];
