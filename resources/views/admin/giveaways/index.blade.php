@extends('layouts.app')

@section('title', 'Gerenciar Sorteios')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Gerenciar Sorteios</h1>
            <p class="text-muted">Crie e administre seus sorteios.</p>
        </div>

        <a href="{{ route('admin.giveaways.create') }}"
           class="btn btn-primary">
            + Novo sorteio
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Criado por</th>
                        <th>Encerramento</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($giveaways as $giveaway)
                        <tr>
                            <td>
                                <strong>{{ $giveaway->title }}</strong>
                            </td>

                            <td>
                                {{ $giveaway->creator?->twitch_username
                                    ?? $giveaway->creator?->name
                                    ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $giveaway->ends_at?->format('d/m/Y H:i')
                                    ?? 'Sem data' }}
                            </td>

                            <td>
                                @if ($giveaway->status === 'published')
                                    <span class="badge bg-success">
                                        Publicado
                                    </span>
                                @elseif ($giveaway->status === 'ended')
                                    <span class="badge bg-secondary">
                                        Encerrado
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        Rascunho
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.giveaways.edit', $giveaway) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Editar
                                </a>

                                <form
                                    action="{{ route('admin.giveaways.destroy', $giveaway) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Deseja realmente excluir este sorteio?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="text-center py-4 text-muted">
                                Nenhum sorteio cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $giveaways->links() }}
    </div>

</div>
@endsection