@php
    $settingsRoutesActive = request()->routeIs('settings.*', 'roles.*', 'departments.*', 'titles.*', 'qualifications.*', 'sections.*', 'stores.*', 'supplies.*');
@endphp
<details class="group" @if($settingsRoutesActive) open @endif>
    <summary
        class="flex items-center justify-between gap-2 px-4 py-3 text-sm font-medium rounded-lg cursor-pointer select-none list-none text-slate-300 hover:bg-slate-700 [&::-webkit-details-marker]:hidden {{ $settingsRoutesActive ? 'bg-blue-900/20 text-blue-400' : '' }}"
    >
        <span class="flex items-center min-w-0">
            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span class="truncate">Settings</span>
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
            Company settings
        </a>
        <a
            href="{{ route('sections.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('sections.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Sections
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
        <a
            href="{{ route('titles.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('titles.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Titles
        </a>
        <a
            href="{{ route('qualifications.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('qualifications.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Qualifications
        </a>
        <a
            href="{{ route('stores.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('stores.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Stores
        </a>
        <a
            href="{{ route('supplies.index') }}"
            class="flex items-center px-3 py-2 text-sm rounded-md transition duration-150 {{ request()->routeIs('supplies.*') ? 'bg-blue-900/30 text-blue-300 font-medium' : 'text-slate-300 hover:bg-slate-700' }}"
        >
            Supplies
        </a>
    </div>
</details>
