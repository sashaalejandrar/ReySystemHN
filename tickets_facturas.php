<!DOCTYPE html>

<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión de Tickets y Facturas</title>
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
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 24px;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="flex h-screen">
<!-- SideNavBar -->
<aside class="flex w-64 flex-col bg-[#111722] p-4 text-white">
<div class="flex flex-col gap-4">
<div class="flex items-center gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Logo de la aplicación" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCy-Uk3x4J4xy0-UtkimCih1OLRMNk2tSOo4p972AYuJJX0uudi0PPpb7gThkMO_oFyeB-b3h7Mb2GqSpbU_s2EXUCEVIDNJbZR6sP9CWvzjxRfYMrN40gjPG5XdAZL23ZXAGlBMpw4X-7pFtBuUai3zA9cO0eD_Jth6bTHmyj_9vbKuAOg50rBXQJ48L1265fghkr7E6p_UKsTO7zon83SVN63VNg5pAc4irtulyXRpTmn-I6ItyBtuCF6rANxxFZjikotndU9T0Vq");'></div>
<div class="flex flex-col">
<h1 class="text-white text-base font-medium leading-normal">App de Cobros</h1>
<p class="text-[#92a4c9] text-sm font-normal leading-normal">Finanzas</p>
</div>
</div>
<nav class="flex flex-col gap-2 mt-4">
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#232f48] transition-colors" href="#">
<span class="material-symbols-outlined text-white text-xl">dashboard</span>
<p class="text-white text-sm font-medium leading-normal">Dashboard</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-[#232f48]" href="#">
<span class="material-symbols-outlined text-white text-xl !font-bold" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
<p class="text-white text-sm font-medium leading-normal">Gestión</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#232f48] transition-colors" href="#">
<span class="material-symbols-outlined text-white text-xl">group</span>
<p class="text-white text-sm font-medium leading-normal">Clientes</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#232f48] transition-colors" href="#">
<span class="material-symbols-outlined text-white text-xl">settings</span>
<p class="text-white text-sm font-medium leading-normal">Configuración</p>
</a>
</nav>
</div>
<div class="mt-auto">
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#232f48] transition-colors" href="#">
<span class="material-symbols-outlined text-white text-xl">logout</span>
<p class="text-white text-sm font-medium leading-normal">Cerrar Sesión</p>
</a>
</div>
</aside>
<!-- Main Content -->
<main class="flex-1 flex flex-col overflow-y-auto">
<!-- TopNavBar -->
<header class="flex flex-shrink-0 items-center justify-between whitespace-nowrap border-b border-solid border-gray-200 dark:border-b-[#232f48] bg-background-light dark:bg-background-dark px-6 py-3">
<h2 class="text-gray-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">Gestión de Tickets y Facturas</h2>
<div class="flex items-center gap-4">
<button class="flex items-center justify-center rounded-lg h-10 w-10 bg-white dark:bg-[#232f48] text-gray-600 dark:text-white hover:bg-gray-100 dark:hover:bg-primary/20 transition-colors">
<span class="material-symbols-outlined text-xl">notifications</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar del usuario" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCaPidoLVPrA7CMn6kTH8PdtJ6DPCG4Q6gtLaXJ9aqPU0KyZF4c8cm0hJkmgTnT_vECKBdAOpTGi2Rte-bCRH4RBZBpwB3er3CsmA4aKCvJWLMKLjEx2iFC3Pjyr8TwqEoUFK4_6PNskVAQTaeUqr6UT2wHpunLw7AmjnrPW3Hab5fjgipVHp6HIvT6FL1JsHEsF32uo_pqQVCHXJdaRxZwm23N4DIyl_29720dxwgvtjtz8oxhg7eLNh_KEWuARlpUKJfzbpkPE6U8");'></div>
</div>
</header>
<!-- Content Area -->
<div class="flex-1 p-6">
<!-- Tabs -->
<div class="border-b border-gray-200 dark:border-[#324467]">
<div class="flex gap-8">
<a class="flex flex-col items-center justify-center border-b-[3px] border-b-primary text-primary pb-[13px] pt-2" href="#">
<p class="text-sm font-bold leading-normal tracking-[0.015em]">Tickets</p>
</a>
<a class="flex flex-col items-center justify-center border-b-[3px] border-b-transparent text-gray-500 dark:text-[#92a4c9] pb-[13px] pt-2 hover:text-primary dark:hover:text-white transition-colors" href="#">
<p class="text-sm font-bold leading-normal tracking-[0.015em]">Facturas</p>
</a>
</div>
</div>
<!-- ToolBar -->
<div class="flex flex-wrap items-center justify-between gap-4 py-4">
<div class="flex flex-wrap items-center gap-2">
<div class="relative min-w-48">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xl">search</span>
<input class="w-full pl-10 pr-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467] focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm dark:text-white" placeholder="Buscar ticket..." type="text"/>
</div>
<button class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467] hover:bg-gray-50 dark:hover:bg-[#232f48] transition-colors text-sm font-medium">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-300 text-xl">filter_list</span>
<span>Filtros</span>
</button>
</div>
<button class="flex items-center justify-center gap-2 overflow-hidden rounded-lg h-10 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] px-4 hover:bg-primary/90 transition-colors">
<span class="material-symbols-outlined !text-xl" style="font-variation-settings: 'FILL' 1;">add</span>
<span class="truncate">Crear Nuevo Ticket</span>
</button>
</div>
<!-- Table -->
<div class="overflow-hidden rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#111722]">
<div class="overflow-x-auto">
<table class="w-full min-w-[800px]">
<thead>
<tr class="bg-gray-50 dark:bg-[#192233]">
<th class="px-4 py-3 text-left text-gray-600 dark:text-white text-sm font-medium leading-normal">Número</th>
<th class="px-4 py-3 text-left text-gray-600 dark:text-white text-sm font-medium leading-normal">Cliente</th>
<th class="px-4 py-3 text-left text-gray-600 dark:text-white text-sm font-medium leading-normal">Fecha de Emisión</th>
<th class="px-4 py-3 text-left text-gray-600 dark:text-white text-sm font-medium leading-normal">Monto</th>
<th class="px-4 py-3 text-left text-gray-600 dark:text-white text-sm font-medium leading-normal">Estado</th>
<th class="px-4 py-3 text-left text-gray-500 dark:text-[#92a4c9] text-sm font-medium leading-normal">Acciones</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
<tr class="hover:bg-gray-50 dark:hover:bg-[#192233]/50 transition-colors">
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">T-00123</td>
<td class="h-[72px] px-4 py-2 text-gray-900 dark:text-white text-sm font-medium leading-normal">Empresa Ejemplo S.A.</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">2023-10-26</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">$1,500.00</td>
<td class="h-[72px] px-4 py-2 text-sm font-normal leading-normal">
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300">Pagado</span>
</td>
<td class="h-[72px] px-4 py-2">
<button class="text-gray-500 dark:text-[#92a4c9] hover:text-gray-700 dark:hover:text-white">
<span class="material-symbols-outlined">more_vert</span>
</button>
</td>
</tr>
<tr class="hover:bg-gray-50 dark:hover:bg-[#192233]/50 transition-colors">
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">T-00122</td>
<td class="h-[72px] px-4 py-2 text-gray-900 dark:text-white text-sm font-medium leading-normal">Juan Pérez</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">2023-10-25</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">$350.50</td>
<td class="h-[72px] px-4 py-2 text-sm font-normal leading-normal">
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300">Pendiente</span>
</td>
<td class="h-[72px] px-4 py-2">
<button class="text-gray-500 dark:text-[#92a4c9] hover:text-gray-700 dark:hover:text-white">
<span class="material-symbols-outlined">more_vert</span>
</button>
</td>
</tr>
<tr class="hover:bg-gray-50 dark:hover:bg-[#192233]/50 transition-colors">
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">T-00121</td>
<td class="h-[72px] px-4 py-2 text-gray-900 dark:text-white text-sm font-medium leading-normal">Servicios Digitales SRL</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">2023-10-25</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">$2,750.00</td>
<td class="h-[72px] px-4 py-2 text-sm font-normal leading-normal">
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300">Pagado</span>
</td>
<td class="h-[72px] px-4 py-2">
<button class="text-gray-500 dark:text-[#92a4c9] hover:text-gray-700 dark:hover:text-white">
<span class="material-symbols-outlined">more_vert</span>
</button>
</td>
</tr>
<tr class="hover:bg-gray-50 dark:hover:bg-[#192233]/50 transition-colors">
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">T-00119</td>
<td class="h-[72px] px-4 py-2 text-gray-900 dark:text-white text-sm font-medium leading-normal">Consultoría Global</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">2023-10-23</td>
<td class="h-[72px] px-4 py-2 text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">$5,200.00</td>
<td class="h-[72px] px-4 py-2 text-sm font-normal leading-normal">
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300">Vencido</span>
</td>
<td class="h-[72px] px-4 py-2">
<button class="text-gray-500 dark:text-[#92a4c9] hover:text-gray-700 dark:hover:text-white">
<span class="material-symbols-outlined">more_vert</span>
</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Pagination -->
<div class="flex items-center justify-between border-t border-gray-200 dark:border-[#324467] px-4 py-3 mt-4 rounded-b-lg bg-white dark:bg-[#111722]">
<p class="text-sm text-gray-600 dark:text-gray-400">
                        Mostrando <span class="font-medium">1</span> a <span class="font-medium">4</span> de <span class="font-medium">25</span> resultados
                    </p>
<div class="flex items-center gap-2">
<button class="flex items-center justify-center h-8 w-8 rounded-md border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#192233] text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#232f48] transition-colors">
<span class="material-symbols-outlined text-xl">chevron_left</span>
</button>
<button class="flex items-center justify-center h-8 w-8 rounded-md border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#192233] text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#232f48] transition-colors">
<span class="material-symbols-outlined text-xl">chevron_right</span>
</button>
</div>
</div>
</div>
</main>
</div>
</body></html>