@extends('public.booking.layout')
@section('title', 'Meus agendamentos — ' . $tenant->name)

@section('content')
<div class="max-w-2xl mx-auto pb-12">

    {{-- Topo --}}
    <div class="bg-white border-b border-gray-100 px-5 py-4 flex items-center gap-3 sticky top-0 z-10">
        <a href="{{ route('public.booking.show', $tenant->booking_slug) }}"
           class="text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-400">{{ $tenant->name }}</p>
            <p class="font-semibold text-gray-900">Meus agendamentos</p>
        </div>
        <form method="POST" action="{{ route('public.booking.auth.logout', $tenant->booking_slug) }}">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-red-500">Sair</button>
        </form>
    </div>

    <div class="px-5 mt-4 space-y-2">
        @forelse ($appointments as $apt)
            @php $cfg = $apt->statusConfig(); @endphp
            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary-50 flex flex-col items-center justify-center shrink-0">
                        <p class="text-[10px] font-semibold text-primary-600 uppercase">{{ $apt->date->locale('pt_BR')->isoFormat('MMM') }}</p>
                        <p class="text-lg font-bold text-primary-700 leading-none">{{ $apt->date->format('d') }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 truncate">{{ $apt->service?->name }}</p>
                        <p class="text-xs text-gray-500">com {{ $apt->professional?->name }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ substr($apt->start_time, 0, 5) }} – {{ substr($apt->end_time, 0, 5) }}
                        </p>
                    </div>
                    <span class="text-[10px] font-medium px-2 py-1 rounded-full {{ $cfg['bg'] }} {{ $cfg['text'] }} shrink-0">
                        {{ $cfg['label'] }}
                    </span>
                </div>
                @if (in_array($apt->status, ['scheduled', 'confirmed']) && $apt->date->isFuture())
                    <form method="POST" action="{{ route('public.booking.cancel', [$tenant->booking_slug, $apt->id]) }}"
                          onsubmit="return confirm('Cancelar este agendamento?')"
                          class="mt-3 pt-3 border-t border-gray-100">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">
                            Cancelar agendamento
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-gray-100 p-10 text-center">
                <p class="text-sm text-gray-400">Você ainda não tem agendamentos.</p>
                <a href="{{ route('public.booking.show', $tenant->booking_slug) }}"
                   class="inline-block mt-3 text-sm font-medium text-primary-600 hover:underline">Agendar agora</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
