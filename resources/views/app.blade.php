<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content" />
        <meta name="theme-color" content="#ffffff">
        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Innerr') }}</title>
        </x-inertia::head>
    </head>
    <body class="relative bg-sand-50 text-sand-900 dark:bg-sand-900 dark:text-sand-100 font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
