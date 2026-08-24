@php
    $nav = [
        ['route' => 'dashboard',      'icon' => '🏠', 'label' => 'nav.dashboard', 'can' => null],
        ['route' => 'sales.create',   'icon' => '🧾', 'label' => 'nav.new_sale',  'can' => 'sale.create'],
        ['route' => 'sales.index',    'icon' => '📋', 'label' => 'nav.sales',     'can' => 'sale.view'],
        ['route' => 'returns.index',  'icon' => '↩️', 'label' => 'nav.returns',   'can' => 'sale.return'],
        ['route' => 'purchases.index','icon' => '📦', 'label' => 'nav.purchases', 'can' => 'purchase.view'],
        ['route' => 'products.index', 'icon' => '🏷️', 'label' => 'nav.products',  'can' => 'product.view'],
        ['route' => 'inventory.index','icon' => '📊', 'label' => 'nav.inventory', 'can' => 'stock.view'],
        ['route' => 'customers.index','icon' => '👥', 'label' => 'nav.customers', 'can' => 'customer.view'],
        ['route' => 'suppliers.index','icon' => '🚚', 'label' => 'nav.suppliers', 'can' => 'supplier.view'],
        ['route' => 'expenses.index', 'icon' => '💸', 'label' => 'nav.expenses',  'can' => 'expense.view'],
        ['route' => 'reports.index',  'icon' => '📈', 'label' => 'nav.reports',   'can' => null],
        ['route' => 'audit.index',    'icon' => '🕵️', 'label' => 'nav.audit',     'can' => 'audit.view'],
        ['route' => 'users.index',    'icon' => '🔐', 'label' => 'nav.users',     'can' => 'user.manage'],
        ['route' => 'settings.edit',  'icon' => '⚙️', 'label' => 'nav.settings',  'can' => 'settings.manage'],
    ];
@endphp

{{-- Collapsed on desktop it becomes a 64px icon rail; on mobile it slides in as a drawer. --}}
<aside id="sidebar"
       class="fixed inset-y-0 start-0 z-40 bg-slate-900 text-slate-200 flex-col hidden lg:flex lg:static
              transition-[width] duration-150">
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800 shrink-0">
        <span class="text-lg font-semibold truncate sidebar-label">{{ settings('shop.name') }}</span>
        <span class="hidden sidebar-mark text-xl">🏪</span>
        <button id="menu-close" type="button" class="lg:hidden text-xl">✕</button>
    </div>

    <nav class="flex-1 overflow-y-auto p-2 space-y-1">
        @foreach ($nav as $item)
            @if (! $item['can'] || auth()->user()->can($item['can']))
                @php($active = request()->routeIs(str($item['route'])->before('.')->value().'*'))
                <a href="{{ route($item['route']) }}"
                   title="{{ __($item['label']) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-slate-800
                          {{ $active ? 'bg-slate-800 text-white font-medium' : '' }}">
                    <span class="text-base shrink-0">{{ $item['icon'] }}</span>
                    <span class="sidebar-label truncate">{{ __($item['label']) }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>
