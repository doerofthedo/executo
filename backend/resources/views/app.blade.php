<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Executo</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @vite(['src/entries/shared.ts', 'src/entries/app.ts'])
    </head>
    <body class="min-h-screen bg-stone-950 text-stone-50 antialiased">
        <div id="app"></div>
    </body>
</html>
