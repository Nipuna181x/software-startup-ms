<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="description" content="Startsuite brings your software team together in a branded workspace. Discover our vision for connected planning, projects, code reviews, and documentation.">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">


@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
