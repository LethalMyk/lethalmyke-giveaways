@extends('layouts.app')

@section('title', 'Editar Sorteio')

@section('content')
<div class="container py-4">

    <h1 class="h3 mb-4">Editar Sorteio</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm p-4">
        <form action="{{ route('admin.giveaways.update', $giveaway) }}"
              method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="title" class="form-label">
                    Título *
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-control"
                    maxlength="255"
                    value="{{ old('title', $giveaway->title) }}"
                    required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    Descrição
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="4">{{ old('description', $giveaway->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label for="ends_at" class="form-label">
                    Data de encerramento
                </label>

                <input
                    type="datetime-local"
                    id="ends_at"
                    name="ends_at"
                    class="form-control"
                    value="{{ old('ends_at', $giveaway->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="mb-4">
                <label for="status" class="form-label">
                    Status
                </label>

                <select id="status"
                        name="status"
                        class="form-select"
                        required>

                    <option value="draft"
                        @selected(old('status', $giveaway->status) === 'draft')>
                        Rascunho
                    </option>

                    <option value="published"
                        @selected(old('status', $giveaway->status) === 'published')>
                        Publicado
                    </option>

                    <option value="ended"
                        @selected(old('status', $giveaway->status) === 'ended')>
                        Encerrado
                    </option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Salvar alterações
                </button>

                <a href="{{ route('admin.giveaways.index') }}"
                   class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

</div>
@endsection