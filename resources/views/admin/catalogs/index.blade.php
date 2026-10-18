@extends('layouts.app')

@section('title', 'Gerenciar '.$definition['title'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-400 hover:text-white">Administração</a>
            <h1 class="mt-2 text-3xl font-bold text-white">{{ $definition['title'] }}</h1>
        </div>
        <a href="{{ route('admin.catalogs.create', $catalog) }}" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400">Novo registro</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-900/60">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-slate-800 bg-slate-900 text-xs uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    @foreach (array_slice(array_keys($definition['fields']), 1, 3) as $field)
                        <th class="px-4 py-3">{{ $definition['fields'][$field]['label'] }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-100">{{ $item->name }}</td>
                        @foreach (array_slice(array_keys($definition['fields']), 1, 3) as $field)
                            <td class="px-4 py-3 text-slate-300">
                                @if (is_array($item->{$field}))
                                    {{ json_encode($item->{$field}, JSON_UNESCAPED_UNICODE) }}
                                @elseif (is_bool($item->{$field}))
                                    {{ $item->{$field} ? 'Sim' : 'Não' }}
                                @else
                                    {{ $item->{$field} ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                <a href="{{ route('admin.catalogs.edit', [$catalog, $item->id]) }}" class="font-medium text-violet-300 hover:text-violet-200">Editar</a>
                                <form method="POST" action="{{ route('admin.catalogs.destroy', [$catalog, $item->id]) }}" onsubmit="return confirm('Excluir este registro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-300 hover:text-red-200">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ min(count($definition['fields']), 4) + 1 }}" class="px-4 py-10 text-center text-slate-400">Nenhum registro cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection