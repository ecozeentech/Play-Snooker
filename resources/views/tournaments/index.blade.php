<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Tournaments</h2>
            <a href="{{ route('tournaments.create') }}" class="btn-gold">Host a tournament</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="status" aria-label="Filter by status" onchange="this.form.submit()" class="form-input-dark min-h-[44px] w-auto">
                <option value="">All statuses</option>
                @foreach (['upcoming', 'ongoing', 'finished', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="type" aria-label="Filter by type" onchange="this.form.submit()" class="form-input-dark min-h-[44px] w-auto">
                <option value="">All types</option>
                <option value="digital" @selected(request('type') === 'digital')>Digital</option>
                <option value="physical" @selected(request('type') === 'physical')>Physical</option>
            </select>
        </form>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($tournaments as $tournament)
                <a href="{{ route('tournaments.show', $tournament) }}" data-aos="fade-up" class="glass-card p-5 flex flex-col gap-3 hover:border-gold-400/40 transition">
                    <div class="flex items-center justify-between">
                        <span class="badge bg-white/10">{{ ucfirst($tournament->type) }}</span>
                        <span class="badge {{ match($tournament->status) {
                            'upcoming' => 'bg-gold-500/20 text-gold-200',
                            'ongoing' => 'bg-baize-400/20 text-baize-200',
                            'finished' => 'bg-white/10 text-baize-100/70',
                            default => 'bg-red-500/20 text-red-300',
                        } }}">{{ ucfirst($tournament->status) }}</span>
                    </div>
                    <h3 class="font-display text-lg font-semibold">{{ $tournament->name }}</h3>
                    <p class="text-sm text-baize-200/60">{{ ucwords(str_replace('_', ' ', $tournament->format)) }}</p>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <span>{{ $tournament->registrations_count }}/{{ $tournament->max_players }} players</span>
                        <span class="text-gold-300 font-semibold">{{ number_format($tournament->prize_pool, 0) }} {{ $tournament->currency }}</span>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-baize-200/50 py-8 text-center">No tournaments match your filters yet.</p>
            @endforelse
        </div>

        {{ $tournaments->links() }}
    </div>
</x-app-layout>
