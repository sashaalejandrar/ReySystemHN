<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión de Proveedores</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script id="tailwind-config">
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
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="flex h-screen w-full">
<aside class="flex w-64 flex-col border-r border-gray-200/10 bg-[#192233] p-4 text-white">
<div class="flex items-center gap-3 px-3 py-4">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Application Logo" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAp4pkaBaQqdHEayKVtxBmO1di8mVQ9yWxL_bh9UigInWFD4u6L1984fgttl5K1PVfZ32Pzd62SxHrl-g_6jK9gK0X_crO2fLe4IWh4fgIDNvub5CIaOSxY817_HGG-79HFRmrB4R17agFCEq1SMMp46t9w4cIGl_gj1QrK-poOiUF-z8dW6Cz9HfxwGPMEMDa-QIBxCbKxgIV5Dz0W0CGWMxTY6INISjkRObP1ukxBcttCizhX6mOp-nDjxtBISNP0Mgn_SkSBSh79");'></div>
<div class="flex flex-col">
<h1 class="text-white text-base font-medium leading-normal">BillingApp</h1>
<p class="text-gray-400 text-sm font-normal leading-normal">Workspace</p>
</div>
</div>
<nav class="mt-4 flex flex-col gap-2">
<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-300 hover:bg-primary/20 hover:text-white" href="#">
<span class="material-symbols-outlined">dashboard</span>
<p class="text-sm font-medium leading-normal">Panel</p>
</a>
<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-300 hover:bg-primary/20 hover:text-white" href="#">
<span class="material-symbols-outlined">receipt_long</span>
<p class="text-sm font-medium leading-normal">Facturas</p>
</a>
<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-300 hover:bg-primary/20 hover:text-white" href="#">
<span class="material-symbols-outlined">group</span>
<p class="text-sm font-medium leading-normal">Clientes</p>
</a>
<a class="flex items-center gap-3 rounded-lg bg-primary/20 px-3 py-2 text-white" href="#">
<span class="material-symbols-outlined">store</span>
<p class="text-sm font-medium leading-normal">Proveedores</p>
</a>
<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-300 hover:bg-primary/20 hover:text-white" href="#">
<span class="material-symbols-outlined">settings</span>
<p class="text-sm font-medium leading-normal">Configuración</p>
</a>
</nav>
<div class="mt-auto flex flex-col gap-2">
<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-300 hover:bg-primary/20 hover:text-white" href="#">
<span class="material-symbols-outlined">help</span>
<p class="text-sm font-medium leading-normal">Soporte</p>
</a>
</div>
</aside>
<div class="flex flex-1 flex-col overflow-y-auto">
<header class="sticky top-0 z-10 flex items-center justify-between whitespace-nowrap border-b border-gray-200/10 bg-background-light/80 px-8 py-3 backdrop-blur-sm dark:bg-background-dark/80">
<div class="flex items-center gap-4 text-gray-800 dark:text-white">
<h2 class="text-lg font-bold leading-tight tracking-[-0.015em]">Proveedores</h2>
</div>
<div class="flex flex-1 items-center justify-end gap-4">
<label class="relative hidden w-full max-w-xs md:block">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input class="form-input block w-full rounded-lg border-none bg-gray-200/50 py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder:text-gray-500 focus:border-primary focus:ring-primary dark:bg-gray-800/50 dark:text-white dark:placeholder:text-gray-400" placeholder="Buscar cualquier cosa..." value=""/>
</label>
<button class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-gray-200/50 text-gray-600 hover:bg-gray-200 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-700/50">
<span class="material-symbols-outlined text-xl">notifications</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="User Avatar" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuABFAI7f0j4-Cvxm26oy6bWtJUbXS_cFbkkyNUVdyu-s5Amba6-nLZkiO7ELaIaJaVTjnHqvTUJfSe6CBlAKwY_q6IHN-QNuxvbjQllctSl3BuHSu4MHiAFnaxVcMfSO5D0W_ZSJrENblFmTpiSWfD9uinp-5y2mgXOwCqtcmpMSL1QtctFA8xNiW6QBC7ETkZpQ3cPUmAwX6Yh5kY2CEH8KFYnoP7xTsoMs4Drm3XWuJVMFldLC7SGvyyGIphIFew_WjlDs95IQvwN");'></div>
</div>
</header>
<main class="flex-1 p-8">
<div class="flex items-center justify-between pb-6">
<div>
<h3 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Proveedores</h3>
<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Administra, agrega y edita la información de tus proveedores.</p>
</div>
<button class="flex cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/20 hover:bg-primary/90">
<span class="material-symbols-outlined text-lg">add</span>
<span>Crear Nuevo Proveedor</span>
</button>
</div>
<div class="w-full space-y-6">
<div class="flex items-center justify-between">
<label class="flex w-full max-w-sm flex-col">
<div class="relative flex w-full flex-1 items-stretch rounded-lg">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input class="form-input w-full rounded-lg border-gray-200/10 bg-[#192233] py-2.5 pl-11 pr-4 text-base font-normal leading-normal text-white placeholder:text-gray-400 focus:border-primary focus:ring-primary" placeholder="Buscar proveedores..." value=""/>
</div>
</label>
<div class="flex items-center gap-2">
<button class="flex items-center gap-2 rounded-lg border border-gray-200/10 bg-[#192233] px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-700/50">
<span class="material-symbols-outlined text-base">filter_list</span>
                                Filtrar
                             </button>
<button class="flex items-center gap-2 rounded-lg border border-gray-200/10 bg-[#192233] px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-700/50">
<span class="material-symbols-outlined text-base">swap_vert</span>
                                Ordenar
                             </button>
</div>
</div>
<div class="overflow-hidden rounded-xl border border-gray-200/10 bg-[#192233]/50">
<table class="min-w-full">
<thead class="bg-gray-800/50">
<tr>
<th class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Nombre de la Empresa</th>
<th class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Persona de Contacto</th>
<th class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Teléfono</th>
<th class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Email</th>
<th class="w-3/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Dirección</th>
<th class="w-1/12 px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-400" scope="col">Acciones</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200/10">
<tr class="hover:bg-gray-800/40">
<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-white">Tech Solutions Inc.</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">Alex Doe</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">+1-202-555-0182</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">alex.doe@techsolutions.com</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">123 Innovation Drive, Tech City</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
<div class="flex items-center justify-end gap-3">
<button class="text-primary/80 hover:text-primary"><span class="material-symbols-outlined text-xl">edit</span></button>
<button class="text-red-500/80 hover:text-red-500"><span class="material-symbols-outlined text-xl">delete</span></button>
</div>
</td>
</tr>
<tr class="hover:bg-gray-800/40">
<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-white">Creative Supplies Co.</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">Maria Garcia</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">+1-310-555-0145</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">maria.g@creativesupplies.co</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">456 Design Avenue, Artstown</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
<div class="flex items-center justify-end gap-3">
<button class="text-primary/80 hover:text-primary"><span class="material-symbols-outlined text-xl">edit</span></button>
<button class="text-red-500/80 hover:text-red-500"><span class="material-symbols-outlined text-xl">delete</span></button>
</div>
</td>
</tr>
<tr class="hover:bg-gray-800/40">
<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-white">Global Logistics LLC</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">John Smith</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">+1-415-555-0199</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">j.smith@globallogistics.com</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">789 Shipping Lane, Portville</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
<div class="flex items-center justify-end gap-3">
<button class="text-primary/80 hover:text-primary"><span class="material-symbols-outlined text-xl">edit</span></button>
<button class="text-red-500/80 hover:text-red-500"><span class="material-symbols-outlined text-xl">delete</span></button>
</div>
</td>
</tr>
<tr class="hover:bg-gray-800/40">
<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-white">Office Essentials Ltd.</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">Priya Patel</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">+1-646-555-0133</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">priya.p@officeessentials.net</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">101 Business Blvd, Metroburg</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
<div class="flex items-center justify-end gap-3">
<button class="text-primary/80 hover:text-primary"><span class="material-symbols-outlined text-xl">edit</span></button>
<button class="text-red-500/80 hover:text-red-500"><span class="material-symbols-outlined text-xl">delete</span></button>
</div>
</td>
</tr>
<tr class="hover:bg-gray-800/40">
<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-white">Marketing Gurus Agency</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">Chen Wei</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">+1-212-555-0178</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">chen.w@marketinggurus.io</td>
<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">212 Ad Street, Campaign City</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
<div class="flex items-center justify-end gap-3">
<button class="text-primary/80 hover:text-primary"><span class="material-symbols-outlined text-xl">edit</span></button>
<button class="text-red-500/80 hover:text-red-500"><span class="material-symbols-outlined text-xl">delete</span></button>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<div class="flex items-center justify-between pt-2">
<div class="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando <span class="font-semibold text-gray-800 dark:text-white">1</span> a <span class="font-semibold text-gray-800 dark:text-white">5</span> de <span class="font-semibold text-gray-800 dark:text-white">42</span> resultados
                        </div>
<nav class="flex items-center justify-center">
<a class="flex size-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800" href="#">
<span class="material-symbols-outlined text-xl">chevron_left</span>
</a>
<a class="flex size-9 items-center justify-center rounded-lg bg-primary/20 text-sm font-bold leading-normal tracking-[0.015em] text-primary" href="#">1</a>
<a class="flex size-9 items-center justify-center rounded-lg text-sm font-normal leading-normal text-white hover:bg-gray-800" href="#">2</a>
<a class="flex size-9 items-center justify-center rounded-lg text-sm font-normal leading-normal text-white hover:bg-gray-800" href="#">3</a>
<span class="flex size-9 items-center justify-center rounded-lg text-sm font-normal leading-normal text-white">...</span>
<a class="flex size-9 items-center justify-center rounded-lg text-sm font-normal leading-normal text-white hover:bg-gray-800" href="#">8</a>
<a class="flex size-9 items-center justify-center rounded-lg text-sm font-normal leading-normal text-white hover:bg-gray-800" href="#">9</a>
<a class="flex size-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800" href="#">
<span class="material-symbols-outlined text-xl">chevron_right</span>
</a>
</nav>
</div>
</div>
</main>
</div>
</div>
</body></html>