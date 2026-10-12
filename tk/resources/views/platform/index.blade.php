<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Platform administration</h2>
                <p class="text-sm text-tk-muted">Super admin view across all tools and users</p>
            </div>
            <a href="{{ route('platform.analytics.overview') }}" class="tk-btn-primary text-sm">Analytics Command Center</a>
        </div>
    </x-slot>

    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        @foreach ($stats as $label => $value)
            <div class="tk-stat">
                <p class="tk-stat-label">{{ str_replace('_', ' ', $label) }}</p>
                <p class="tk-stat-value">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <section>
            <h3 class="font-semibold mb-3">Recent tools</h3>
            @foreach ($recentTools as $tool)
                <a href="{{ route('tools.show', $tool) }}" class="tk-card block p-4 mb-2 text-sm">
                    <p class="font-medium">{{ $tool->name }}</p>
                    <p class="text-tk-muted">Owner: {{ $tool->owner?->name ?? '—' }}</p>
                </a>
            @endforeach
        </section>
        <section>
            <h3 class="font-semibold mb-3">Users</h3>
            <div class="tk-card overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-tk-soft"><tr><th class="p-2 text-left">Name</th><th class="p-2">Role</th><th class="p-2"></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-t border-tk-line">
                                <td class="p-2">{{ $user->name }}<br><span class="text-xs text-tk-muted">{{ $user->email }}</span></td>
                                <td class="p-2 text-center">{{ $user->platform_role?->value }}</td>
                                <td class="p-2 text-right">
                                    @if (!$user->isSuperAdmin())
                                        <form method="POST" action="{{ route('platform.users.promote', $user) }}">
                                            @csrf
                                            <button class="text-xs text-tk-accent">Make super admin</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
