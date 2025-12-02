<!DOCTYPE html>

<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Perfil de Usuario</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1152d4",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex h-full grow">
<!-- SideNavBar -->
<aside class="flex flex-col w-64 bg-background-light dark:bg-[#111722] p-4 border-r border-gray-200 dark:border-gray-800">
<div class="flex flex-col justify-between h-full">
<div class="flex flex-col gap-4">
<div class="flex items-center gap-3 px-2">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Logo de la empresa" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCaWX6zJ15aANX2BqSw5WhQ6EiX6mre_HfWZz8ioWlVEkBo-Ws05am6jPU6nGELG-XmYbO1ZQWLZTtR71oiDV-UpFtxkhNHDnlzKQ4H61dVeAX8BXtwNtBYnRgxstZxYo-ca9uTws-HnK-bm8rDBNZi5oyj1oOriF718SB0AWr12piAcEWD_DiPh5nf0alJbZZ8cPY49b1F7mB-RPKLqWifu18gPLad6p7jRk2bKIeNNPyVVkwjztUynwxShUpjtQv0HH25-394uno0");'></div>
<div class="flex flex-col">
<h1 class="text-gray-900 dark:text-white text-base font-bold leading-normal">Nombre Empresa</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">Aplicación de Cobros</p>
</div>
</div>
<nav class="flex flex-col gap-2 mt-4">
<a class="flex items-center gap-3 px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-primary/20 rounded-lg" href="#">
<span class="material-symbols-outlined">home</span>
<p class="text-sm font-medium leading-normal">Inicio</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-primary/20 rounded-lg" href="#">
<span class="material-symbols-outlined">receipt_long</span>
<p class="text-sm font-medium leading-normal">Cobros</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-primary/20 rounded-lg" href="#">
<span class="material-symbols-outlined">group</span>
<p class="text-sm font-medium leading-normal">Clientes</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary text-white" href="#">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
<p class="text-sm font-medium leading-normal">Perfil</p>
</a>
</nav>
</div>
<div class="flex flex-col gap-1">
<a class="flex items-center gap-3 px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-primary/20 rounded-lg" href="#">
<span class="material-symbols-outlined">settings</span>
<p class="text-sm font-medium leading-normal">Configuración</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-primary/20 rounded-lg" href="#">
<span class="material-symbols-outlined">help</span>
<p class="text-sm font-medium leading-normal">Ayuda</p>
</a>
</div>
</div>
</aside>
<!-- Main Content -->
<main class="flex-1 flex flex-col bg-background-light dark:bg-background-dark">
<!-- TopNavBar -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-800 px-10 py-3 bg-background-light dark:bg-[#111722]">
<div class="flex items-center gap-8">
<h2 class="text-gray-900 dark:text-white text-lg font-bold leading-tight">Perfil de Usuario</h2>
<label class="flex flex-col min-w-40 !h-10 max-w-64">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full">
<div class="text-gray-500 dark:text-[#92a4c9] flex border-none bg-gray-100 dark:bg-[#232f48] items-center justify-center pl-4 rounded-l-lg border-r-0">
<span class="material-symbols-outlined">search</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-900 dark:text-white focus:outline-0 focus:ring-0 border-none bg-gray-100 dark:bg-[#232f48] focus:border-none h-full placeholder:text-gray-500 dark:placeholder:text-[#92a4c9] px-4 rounded-l-none border-l-0 pl-2 text-base font-normal leading-normal" placeholder="Buscar..." value=""/>
</div>
</label>
</div>
<div class="flex flex-1 justify-end gap-4 items-center">
<div class="flex gap-2">
<button class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-gray-100 dark:bg-[#232f48] text-gray-700 dark:text-white text-sm font-bold">
<span class="material-symbols-outlined">notifications</span>
</button>
<button class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-gray-100 dark:bg-[#232f48] text-gray-700 dark:text-white text-sm font-bold">
<span class="material-symbols-outlined">chat_bubble</span>
</button>
</div>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar del usuario" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCbKbXV3qilKwhsK2nQlbClbelgkqj_c3SXmOsIeSGeXDKwEUL6cyb5Wn9f5kBMd4rTS4iO1EJvzqDgDbKixAZzRHlpIroNRFirH8QXzTa9inb5DJpAx3NleHtlD3fTn8QCqnmWXgTIaDv-tX8oFDMeiay97XtdxZDN4UWF17yX5Q0d3VZnKJBjm308B07YGXDNyOIpCy7OBMSLkoFk8i0InJovtPn-TD6T4aTOUzXtnZDQIIP8z1o--f4lTSiZTxgYCs2A0y0Vq_wq");'></div>
</div>
</header>
<!-- Profile Content -->
<div class="flex-1 overflow-y-auto p-6 lg:p-10">
<div class="max-w-7xl mx-auto">
<!-- HeaderImage -->
<div class="w-full bg-center bg-no-repeat bg-cover flex flex-col justify-end overflow-hidden rounded-xl min-h-[220px]" data-alt="Banner de perfil de usuario con un degradado abstracto azul" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDGIFUCF3U1RJKSfsgG2Flbrlh7qkBbkwYtsgWDVYL0PtlhUQdrYDRPtJMjWqds2Gn_Q0CJwymDjgb_ZmCQlLxwxPgmRcs_EjFaIP7GotLmEEvmXcacJwjR8ztUrPPIDWeWo24A-8wpnAROz3hUsCtUWhB9w7gBeCDYKsKfCgomMaD82DP2bWkllksCF2GdyYhO-6PNMWCWr_ixVXgTfNMFW2aARICW_7d_QeYsUqJGuCW2KgyYaNmPYHGHTl1r3ww7ZteuVsxLy1Bc");'></div>
<!-- ProfileHeader -->
<div class="flex p-4 @container -mt-16">
<div class="flex w-full flex-col gap-4 @[520px]:flex-row @[520px]:justify-between @[520px]:items-end">
<div class="flex gap-4">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full min-h-32 w-32 border-4 border-background-light dark:border-background-dark" data-alt="Foto de perfil de Elara Vance" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAC1jklLnPj6ymJ2yknqA8uHNz_LraZethGcYhPDa2w4B8EzNqVOTIjuvbQXvkR-nGb0W63AVVvv6_z7aGCzHlvJoldSAAhpDieKpQB-cuZwPkZZykkZwwXZGSYnQekNYCFCBdY5n_XUnVDoh7FtkJrn8VvGoWPV5RaNgC1BHyXeZlO03lLFy34-FVBYX8PYit8zCYn3qtx4xEmxYZmwcwv9n_vn1CRZPsgphqebt2pc4gQ7WVrkZfQ6mK0rgsl7B1h7FlAn0pYJbDW");'></div>
<div class="flex flex-col justify-center pt-16">
<p class="text-gray-900 dark:text-white text-[22px] font-bold leading-tight">Elara Vance</p>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Gerente de Cuentas, Departamento de Finanzas</p>
</div>
</div>
<div class="flex w-full max-w-[480px] gap-3 @[480px]:w-auto">
<button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-gray-200 dark:bg-[#232f48] text-gray-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em] flex-1 @[480px]:flex-auto">
<span class="truncate">Editar Perfil</span>
</button>
<button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] flex-1 @[480px]:flex-auto">
<span class="truncate">Configuración</span>
</button>
</div>
</div>
</div>
<!-- Main Profile Body -->
<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
<!-- Left Column -->
<div class="lg:col-span-2">
<!-- Tabs -->
<div class="pb-3">
<div class="flex border-b border-gray-200 dark:border-gray-800 px-4 gap-8">
<a class="flex flex-col items-center justify-center border-b-[3px] border-b-primary text-gray-900 dark:text-white pb-[13px] pt-4" href="#">
<p class="text-sm font-bold leading-normal">Actividad</p>
</a>
<a class="flex flex-col items-center justify-center border-b-[3px] border-b-transparent text-gray-500 dark:text-[#92a4c9] hover:border-gray-400 dark:hover:border-gray-500 pb-[13px] pt-4" href="#">
<p class="text-sm font-bold leading-normal">Publicaciones</p>
</a>
<a class="flex flex-col items-center justify-center border-b-[3px] border-b-transparent text-gray-500 dark:text-[#92a4c9] hover:border-gray-400 dark:hover:border-gray-500 pb-[13px] pt-4" href="#">
<p class="text-sm font-bold leading-normal">Información</p>
</a>
</div>
</div>
<!-- Activity Feed -->
<div class="space-y-6 mt-6">
<h3 class="text-lg font-bold text-gray-900 dark:text-white px-4">Actividad Reciente</h3>
<!-- Activity Item 1 -->
<div class="bg-white dark:bg-[#111722] p-5 rounded-xl shadow-sm">
<div class="flex items-start space-x-4">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de Elara Vance" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBExrPfftHueHN7emW4ZO_0tCqz-MmEtrtf4Nnq0wzG4RyVCOIFNAELRfv7NObR7yyzhSSgWvc4WhBsrpDo5svP1ohBaS4z-cdIeY6QFqjwm-A9qQYKzej0SeRp1ZD7Vzn3yOUHqSEovJwC-pxf4e3BxXWseE9qGK-H0mWynjafWPd-kOgiiYIQj_cyOHdNhmuwR6VkOA4-aPb6sFWWk6AMG-U3-kIg61dSZjjAINB3OQ3MwZ6DGF60V8KtoZSSkjqTk2HlnTr4yblm");'></div>
<div class="flex-1">
<p class="text-sm text-gray-800 dark:text-gray-300"><span class="font-bold text-gray-900 dark:text-white">Elara Vance</span> ha creado una nueva publicación: <span class="font-semibold text-primary">"Resultados Financieros Q3"</span></p>
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">hace 2 horas</p>
<div class="mt-3 p-4 rounded-lg bg-gray-50 dark:bg-background-dark border border-gray-200 dark:border-gray-800">
<p class="text-sm text-gray-700 dark:text-gray-300">¡Equipo! Acabo de publicar el informe de resultados del tercer trimestre. Gran trabajo de todos para alcanzar nuestros objetivos. Por favor, revisen el documento adjunto y dejen sus comentarios.</p>
</div>
</div>
</div>
</div>
<!-- Activity Item 2 -->
<div class="bg-white dark:bg-[#111722] p-5 rounded-xl shadow-sm">
<div class="flex items-start space-x-4">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de Elara Vance" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB97mgCkwOvK6Oup5-rKse01Pr-vkJZj_RLpMhlk7lhHnqgXusgSq-s5FuYU3wYYSgG-w4VjcXqJJE7eGvBb6CfbovfOZaPypBW9rmaBSOxDs5QsDQDtr6KxdS0xqsKYJAgazhaH8l_ta__a7JBvtyD5sO0YgWEBdbVrjtxn-JTVYjnjwhi8luhy9C8Apna5Re90_h2v7v5SETAi5u4-huiMrdxuo0GkmDqaeLIfRq8m3AlFc7T9iFdH4PPcCkUWQpAVDGDbSd92L1H");'></div>
<div class="flex-1">
<p class="text-sm text-gray-800 dark:text-gray-300"><span class="font-bold text-gray-900 dark:text-white">Elara Vance</span> comentó en la publicación de <span class="font-semibold text-primary">Liam Chen</span></p>
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">hace 5 horas</p>
<div class="mt-3 p-4 rounded-lg bg-gray-50 dark:bg-background-dark border border-gray-200 dark:border-gray-800">
<p class="text-sm italic text-gray-600 dark:text-gray-400">"Excelente iniciativa, Liam. Esto mejorará significativamente nuestro flujo de trabajo. ¡Apoyo total!"</p>
</div>
</div>
</div>
</div>
</div>
</div>
<!-- Right Column -->
<div class="lg:col-span-1 space-y-8">
<!-- Biography Card -->
<div class="bg-white dark:bg-[#111722] p-6 rounded-xl shadow-sm">
<h3 class="text-lg font-bold text-gray-900 dark:text-white">Acerca de mí</h3>
<p class="mt-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                        Gerente de cuentas con más de 10 años de experiencia en el sector financiero. Apasionada por optimizar procesos y construir relaciones sólidas con los clientes. En mi tiempo libre, disfruto del senderismo y la fotografía.
                                    </p>
</div>
<!-- Contact Info Card -->
<div class="bg-white dark:bg-[#111722] p-6 rounded-xl shadow-sm">
<h3 class="text-lg font-bold text-gray-900 dark:text-white">Información de Contacto</h3>
<div class="mt-4 space-y-4">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-gray-500 dark:text-gray-400">email</span>
<p class="text-sm text-gray-800 dark:text-gray-200">elara.vance@empresa.com</p>
</div>
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-gray-500 dark:text-gray-400">call</span>
<p class="text-sm text-gray-800 dark:text-gray-200">+1 (555) 123-4567</p>
</div>
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-gray-500 dark:text-gray-400">location_on</span>
<p class="text-sm text-gray-800 dark:text-gray-200">Oficina Central, Ciudad</p>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</main>
</div>
</div>
</body></html>