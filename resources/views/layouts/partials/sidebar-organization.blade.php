@php
    $orgRoutesActive = request()->routeIs('settings.*', 'roles.*', 'departments.*');
@endphp
<details class="group" @if($orgRoutesActive) open @endif>
    <summary
        class="flex items-center justify-between gap-2 px-4 py-3 text-sm font-medium rounded-lg cursor-pointer select-none list-none text-slate-300 hover:bg-slate-700 [&::-webkit-details-marker]:hidden {{ $orgRoutesActive ? 'bg-blue-900/20 text-blue-400' : '' }}"
    >
        <span class="flex items-center min-w-0">
            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span class="truncate">Organization</span>
        </span>
        <svg class="w-4 h-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </summary>
    <div class="mt-1 ml-4 pl-3 space-y-0.5 border-l border-slate-600">
        <a
            href="{{ route('settings.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('settings.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Settings
        </a>
        <a
            href="{{ route('roles.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('roles.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Roles
        </a>
        <a
            href="{{ route('departments.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('departments.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Departments
        </a>
    </div>
</details>
