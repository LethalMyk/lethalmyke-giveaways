@extends('layouts.app')

@section('title', ($item ? 'Editar ' : 'Novo registro: ').$definition['title'])

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.catalogs.index', $catalog) }}" class="text-sm text-slate-400 hover:text-white">← {{ $definition['title'] }}</a>
        <h1 class="mt-2 text-3xl font-bold text-white">{{ $item ? 'Editar registro' : 'Novo registro' }}</h1>
    </div>

    <form method="POST" action="{{ $item ? route('admin.catalogs.update', [$catalog, $item->id]) : route('admin.catalogs.store', $catalog) }}" class="space-y-5 rounded-lg border border-slate-800 bg-slate-900/60 p-5 sm:p-7">
        @csrf
        @if ($item)
            @method('PUT')
        @endif

        @foreach ($definition['fields'] as $name => $field)
            @php
                $value = old($name, $item?->{$name});
                if ($field['type'] === 'json' && is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            @endphp

            @if ($field['type'] === 'checkbox')
                <label class="flex items-center gap-3 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $item?->{$name} ?? true)) class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-400">
                    {{ $field['label'] }}
                </label>
            @else
                <div>
                    <label for="{{ $name }}" class="mb-2 block text-sm font-medium text-slate-200">{{ $field['label'] }}</label>
                    @if (in_array($field['type'], ['textarea', 'json'], true))
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $field['type'] === 'json' ? 8 : 4 }}" @required(str_contains($field['rules'], 'required')) class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-violet-400 focus:outline-none">{{ $value }}</textarea>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $field['type'] }}" value="{{ $value }}" @required(str_contains($field['rules'], 'required')) class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-violet-400 focus:outline-none">
                    @endif
                    @error($name)
                        <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        @endforeach

        @if ($errors->has('materials'))
            <p class="text-sm text-red-300">{{ $errors->first('materials') }}</p>
        @endif

        <div class="flex flex-wrap gap-3 border-t border-slate-800 pt-5">
            <button type="submit" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400">{{ $item ? 'Salvar alterações' : 'Cadastrar' }}</button>
            <a href="{{ route('admin.catalogs.index', $catalog) }}" class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection