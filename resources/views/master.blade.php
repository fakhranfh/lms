<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ config('app.name') }} - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-tertiary-container": "#eff0f1",
                        error: "#DC2626",
                        "on-primary-fixed": "#00174b",
                        "surface-tint": "#0053db",
                        success: "#16A34A",
                        "success-container": "#f0fdf4",
                        "on-success-container": "#15803d",
                        primary: "#004ac6",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#eeefff",
                        "error-container": "#ffdad6",
                        secondary: "#585f6c",
                        "on-surface-variant": "#434655",
                        "on-background": "#191b23",
                        "surface-container-highest": "#e1e2ed",
                        "on-secondary-fixed-variant": "#404754",
                        "surface-container-lowest": "#ffffff",
                        "surface-container": "#ededf9",
                        "inverse-surface": "#2e3039",
                        "tertiary-fixed-dim": "#c5c7c8",
                        "on-tertiary-fixed": "#191c1d",
                        "inverse-primary": "#b4c5ff",
                        "primary-container": "#2563eb",
                        "secondary-fixed": "#dce2f3",
                        tertiary: "#525556",
                        surface: "#FFFFFF",
                        "tertiary-fixed": "#e1e3e4",
                        info: "#2563EB",
                        "on-tertiary": "#ffffff",
                        "secondary-fixed-dim": "#c0c7d6",
                        "surface-dim": "#d9d9e5",
                        outline: "#737686",
                        "on-secondary-fixed": "#151c27",
                        "outline-variant": "#c3c6d7",
                        "tertiary-container": "#6b6d6e",
                        "inverse-on-surface": "#f0f0fb",
                        "on-surface": "#191b23",
                        warning: "#D97706",
                        background: "#F9FAFB",
                        "surface-variant": "#e1e2ed",
                        "surface-container-low": "#f3f3fe",
                        "on-tertiary-fixed-variant": "#454748",
                        "on-primary-fixed-variant": "#003ea8",
                        "surface-bright": "#faf8ff",
                        "primary-fixed-dim": "#b4c5ff",
                        "on-secondary-container": "#5e6572",
                        "on-secondary": "#ffffff",
                        "on-error": "#ffffff",
                        "surface-container-high": "#e7e7f3",
                        "on-error-container": "#93000a",
                        "secondary-container": "#dce2f3",
                        "primary-fixed": "#dbe1ff"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        gutter: "1rem",
                        "space-xl": "2rem",
                        "container-max-width": "640px",
                        "space-md": "1rem",
                        "space-sm": "0.75rem",
                        "space-xxs": "0.25rem",
                        "space-xs": "0.5rem",
                        "space-lg": "1.5rem"
                    },
                    fontFamily: {
                        "body-sm": ["Inter"],
                        "label-md": ["Inter"],
                        "label-sm": ["Inter"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "headline-sm": ["Plus Jakarta Sans"]
                    },
                    fontSize: {
                        "body-sm": ["13px", { lineHeight: "18px", fontWeight: "400" }],
                        "label-md": ["12px", { lineHeight: "16px", letterSpacing: "0.02em", fontWeight: "500" }],
                        "label-sm": ["11px", { lineHeight: "14px", letterSpacing: "0.05em", fontWeight: "600" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "700" }],
                        "headline-lg": ["30px", { lineHeight: "36px", fontWeight: "700" }],
                        "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "body-lg": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "headline-sm": ["20px", { lineHeight: "28px", fontWeight: "600" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined[data-weight="fill"] {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('styles')
    @livewireStyles
</head>

<body class="@yield('body_class', $body_class ?? 'bg-background text-on-background min-h-screen p-gutter font-body-md') flex flex-col">
    @yield('content')
    @stack('scripts')
    @livewireScripts
</body>

</html>
