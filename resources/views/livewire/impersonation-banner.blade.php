<div class="bg-yellow-500 text-yellow-900 px-4 py-2 text-sm font-medium">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <span>
                Estás impersonando a: <strong>{{ auth()->user()->name }}</strong>
                ({{ auth()->user()->company->name }})
            </span>
        </div>
        <button
            wire:click="leaveImpersonation"
            class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors"
        >
            Dejar Impersonación
        </button>
    </div>
</div>