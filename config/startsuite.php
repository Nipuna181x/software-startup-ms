<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Startsuite Brand
    |--------------------------------------------------------------------------
    |
    | The accent colour used across the public marketing website and the auth
    | pages. Organizations pick their own colour for their dashboard; this one
    | belongs to Startsuite itself. Change it here and it changes everywhere.
    |
    */

    'accent' => env('STARTSUITE_ACCENT', '#1a365d'),

    /*
    |--------------------------------------------------------------------------
    | Default Organization Theme
    |--------------------------------------------------------------------------
    |
    | Applied when an organization has not chosen a colour, and used as the
    | starting value of the colour picker during registration.
    |
    */

    'default_organization_color' => '#1d4ed8',

    /*
    |--------------------------------------------------------------------------
    | Theme Presets
    |--------------------------------------------------------------------------
    |
    | Swatches offered during registration and in organization settings. Each
    | entry needs a label and a hex value that is readable on a white surface.
    |
    */

    'theme_presets' => [
        ['label' => 'Indigo', 'value' => '#1d4ed8'],
        ['label' => 'Teal', 'value' => '#0f766e'],
        ['label' => 'Forest', 'value' => '#15803d'],
        ['label' => 'Crimson', 'value' => '#b91c1c'],
        ['label' => 'Amber', 'value' => '#b45309'],
        ['label' => 'Violet', 'value' => '#6d28d9'],
        ['label' => 'Slate', 'value' => '#334155'],
        ['label' => 'Rose', 'value' => '#be123c'],
    ],

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
        'color' => env('DEMO_ORGANIZATION_COLOR', '#0f766e'),
        'name' => env('DEMO_ADMIN_NAME', 'Demo Admin'),
        'email' => env('DEMO_ADMIN_EMAIL', 'admin@northwind.test'),
        'password' => env('DEMO_ADMIN_PASSWORD'),
    ],

];
