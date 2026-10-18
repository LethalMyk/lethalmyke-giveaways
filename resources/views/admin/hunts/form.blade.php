@extends('layouts.app')

@section('title', $hunt->exists ? 'Editar hunt' : 'Nova hunt')

@section('content')
@php
    $relationJson = [
        'creatures_json' => old('creatures_json', $hunt->exists ? $hunt->creatureEntries->map(fn ($row) => $row->only(['name', 'image_url', 'source_url', 'notes']))->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]'),
        'requirements_json' => old('requirements_json', $hunt->exists ? $hunt->requirements->map(fn ($row) => $row->only(['type', 'name', 'description', 'url']))->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]'),
        'supplies_json' => old('supplies_json', $hunt->exists ? $hunt->supplies->map(fn ($row) => $row->only(['name', 'quantity', 'image_url', 'notes']))->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]'),
        'loots_json' => old('loots_json', $hunt->exists ? $hunt->loots->map(fn ($row) => $row->only(['name', 'image_url', 'estimated_value', 'notes']))->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]'),
        'selected_vocations' => array_map('intval', old('vocations', $hunt->exists ? $hunt->vocations->modelKeys() : [])),
    ];
@endphp
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div><a href="{{ route('admin.hunts.index') }}" class="text-sm text-slate-400 hover:text-white">Hunt Places</a><h1 class="mt-2 text-3xl font-bold text-white">{{ $hunt->exists ? 'Editar hunt' : 'Nova hunt' }}</h1></div>
        <a href="{{ route('admin.vocations.index') }}" class="text-sm font-medium text-cyan-200 hover:text-cyan-100">Gerenciar vocações</a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-400/30 bg-red-500/10 p-4 text-sm text-red-100"><ul class="mb-0 list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $hunt->exists ? route('admin.hunts.update', $hunt) : route('admin.hunts.store') }}" class="space-y-5">
        @csrf
        @if($hunt->exists) @method('PUT') @endif

        <section class="tool-card grid gap-4 sm:grid-cols-2">
            <div><label for="name" class="tool-label">Nome *</label><input id="name" name="name" required maxlength="255" value="{{ old('name', $hunt->name) }}" class="tool-input"></div>
            <div><label for="slug" class="tool-label">Slug de compartilhamento</label><input id="slug" name="slug" maxlength="255" value="{{ old('slug', $hunt->slug) }}" placeholder="gerado-a-partir-do-nome" class="tool-input"></div>
            <div><label for="level_min" class="tool-label">Level mínimo *</label><input id="level_min" name="level_min" type="number" min="1" max="2000" required value="{{ old('level_min', $hunt->level_min) }}" class="tool-input"></div>
            <div><label for="level_max" class="tool-label">Level máximo</label><input id="level_max" name="level_max" type="number" min="1" max="2000" value="{{ old('level_max', $hunt->level_max) }}" class="tool-input"></div>
            <div><label for="party_type" class="tool-label">Tamanho do grupo *</label><select id="party_type" name="party_type" required class="tool-input"><option value="solo" @selected(old('party_type', $hunt->party_type) === 'solo')>Solo</option><option value="duo" @selected(old('party_type', $hunt->party_type) === 'duo')>Duo</option><option value="team" @selected(old('party_type', $hunt->party_type) === 'team')>Time</option></select></div>
            <div class="grid grid-cols-2 gap-3"><div><label for="minimum_party_size" class="tool-label">Time mínimo</label><input id="minimum_party_size" name="minimum_party_size" type="number" min="2" max="10" value="{{ old('minimum_party_size', $hunt->minimum_party_size) }}" class="tool-input"></div><div><label for="maximum_party_size" class="tool-label">Time máximo</label><input id="maximum_party_size" name="maximum_party_size" type="number" min="2" max="10" value="{{ old('maximum_party_size', $hunt->maximum_party_size) }}" class="tool-input"></div></div>
            <fieldset class="sm:col-span-2"><legend class="tool-label">Vocações</legend><div class="flex flex-wrap gap-2">@forelse($vocations as $vocation)<label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-slate-200"><input type="checkbox" name="vocations[]" value="{{ $vocation->id }}" @checked(in_array($vocation->id, $relationJson['selected_vocations'], true)) class="h-4 w-4 accent-cyan-400">{{ $vocation->name }}</label>@empty<a href="{{ route('admin.vocations.create') }}" class="text-sm text-cyan-200">Cadastre as vocações</a>@endforelse</div></fieldset>
            <div><label for="difficulty" class="tool-label">Dificuldade</label><input id="difficulty" name="difficulty" maxlength="80" value="{{ old('difficulty', $hunt->difficulty) }}" class="tool-input"></div>
            <div><label for="access_description" class="tool-label">Acesso ou requisito especial</label><input id="access_description" name="access_description" maxlength="3000" value="{{ old('access_description', $hunt->access_description) }}" class="tool-input"></div>
        </section>

        <section class="tool-card">
            <h2 class="mb-4 text-base font-bold text-white">Estimativas Rubinot</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label for="xp_min_per_hour" class="tool-label">XP mínimo / h</label><input id="xp_min_per_hour" name="xp_min_per_hour" type="number" min="0" value="{{ old('xp_min_per_hour', $hunt->xp_min_per_hour) }}" class="tool-input"></div>
                <div><label for="xp_max_per_hour" class="tool-label">XP máximo / h</label><input id="xp_max_per_hour" name="xp_max_per_hour" type="number" min="0" value="{{ old('xp_max_per_hour', $hunt->xp_max_per_hour) }}" class="tool-input"></div>
                <div><label for="profit_min_per_hour" class="tool-label">Lucro mínimo / h</label><input id="profit_min_per_hour" name="profit_min_per_hour" type="number" value="{{ old('profit_min_per_hour', $hunt->profit_min_per_hour) }}" class="tool-input"></div>
                <div><label for="profit_max_per_hour" class="tool-label">Lucro máximo / h</label><input id="profit_max_per_hour" name="profit_max_per_hour" type="number" value="{{ old('profit_max_per_hour', $hunt->profit_max_per_hour) }}" class="tool-input"></div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Não informe valores do Tibia Global como se fossem do Rubinot; valores ausentes permanecem em branco.</p>
        </section>

        <section class="tool-card grid gap-4 sm:grid-cols-2">
            <div><label for="cover_image_url" class="tool-label">Imagem principal (URL)</label><input id="cover_image_url" name="cover_image_url" type="url" value="{{ old('cover_image_url', $hunt->cover_image_url) }}" class="tool-input"></div>
            <div><label for="video_url" class="tool-label">Vídeo (URL)</label><input id="video_url" name="video_url" type="url" value="{{ old('video_url', $hunt->video_url) }}" class="tool-input"></div>
            <div><label for="location_name" class="tool-label">Região</label><input id="location_name" name="location_name" value="{{ old('location_name', $hunt->location_name) }}" class="tool-input"></div>
            <div><label for="map_image_url" class="tool-label">Mapa (URL)</label><input id="map_image_url" name="map_image_url" type="url" value="{{ old('map_image_url', $hunt->map_image_url) }}" class="tool-input"></div>
            <div class="sm:col-span-2"><label for="location_description" class="tool-label">Como chegar</label><textarea id="location_description" name="location_description" rows="3" class="tool-input">{{ old('location_description', $hunt->location_description) }}</textarea></div>
            <div class="sm:col-span-2"><label for="description" class="tool-label">Descrição detalhada</label><textarea id="description" name="description" rows="5" class="tool-input">{{ old('description', $hunt->description) }}</textarea></div>
            <div class="sm:col-span-2"><label for="notes" class="tool-label">Observações e estratégia</label><textarea id="notes" name="notes" rows="4" class="tool-input">{{ old('notes', $hunt->notes) }}</textarea></div>
        </section>

        <section class="tool-card space-y-4">
            <h2 class="text-base font-bold text-white">Conteúdo estruturado</h2>
            @foreach ([
                'creatures_json' => ['Criaturas (JSON)', '[{"name":"Nome da criatura","image_url":"https://...","source_url":"https://...","notes":"Estratégia opcional"}]'],
                'requirements_json' => ['Requisitos (JSON)', '[{"type":"quest","name":"Nome da quest","description":"Descrição opcional","url":"https://..."}]'],
                'supplies_json' => ['Suprimentos (JSON)', '[{"name":"Potion","quantity":100,"image_url":"https://...","notes":"Opcional"}]'],
                'loots_json' => ['Melhores loots (JSON)', '[{"name":"Item","image_url":"https://...","estimated_value":0,"notes":"Estimativa opcional"}]'],
            ] as $field => [$label, $placeholder])
                <div><label for="{{ $field }}" class="tool-label">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" rows="4" placeholder="{{ $placeholder }}" class="tool-input">{{ $relationJson[$field] }}</textarea>@error($field)<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror</div>
            @endforeach
        </section>

        <section class="tool-card grid gap-4 sm:grid-cols-2">
            <div><label for="last_reviewed_at" class="tool-label">Última revisão</label><input id="last_reviewed_at" name="last_reviewed_at" type="datetime-local" value="{{ old('last_reviewed_at', $hunt->last_reviewed_at?->format('Y-m-d\TH:i')) }}" class="tool-input"></div>
            <div><label for="outdated_reason" class="tool-label">Motivo de desatualização</label><input id="outdated_reason" name="outdated_reason" value="{{ old('outdated_reason', $hunt->outdated_reason) }}" class="tool-input"></div>
            <label class="flex items-center gap-3 text-sm text-amber-100"><input type="checkbox" name="is_outdated" value="1" @checked(old('is_outdated', $hunt->is_outdated)) class="h-4 w-4 accent-amber-400">Marcar como desatualizada</label>
            <label class="flex items-center gap-3 text-sm font-semibold text-emerald-100"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $hunt->is_published)) class="h-4 w-4 accent-emerald-400">Publicar no Hunt Places</label>
        </section>

        <div class="flex flex-wrap gap-3 pb-8"><button type="submit" class="rounded-lg border border-violet-400/50 bg-violet-500/15 px-5 py-2.5 text-sm font-bold text-violet-100 hover:bg-violet-500/25">Salvar hunt</button><a href="{{ route('admin.hunts.index') }}" class="rounded-lg border border-slate-700 px-5 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800">Cancelar</a></div>
    </form>
</div>
@endsection