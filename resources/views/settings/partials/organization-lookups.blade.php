<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <h2 class="text-sm font-semibold text-slate-900">Organization lookups</h2>
    <p class="text-xs text-slate-500 mt-1">Configure lists used across the portal. Only section is offered when enrolling users.</p>
    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
        @foreach([
            ['route' => 'departments.index', 'label' => 'Departments'],
            ['route' => 'sections.index', 'label' => 'Sections'],
            ['route' => 'titles.index', 'label' => 'Titles'],
            ['route' => 'qualifications.index', 'label' => 'Qualifications'],
            ['route' => 'stores.index', 'label' => 'Stores'],
            ['route' => 'supplies.index', 'label' => 'Supplies'],
            ['route' => 'roles.index', 'label' => 'Roles'],
        ] as $link)
            <a href="{{ route($link['route']) }}"
                class="px-3 py-2 text-sm text-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-700 transition-colors">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</div>
