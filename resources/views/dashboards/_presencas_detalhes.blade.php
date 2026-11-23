<div class="px-3 py-2 border-bottom small text-muted">
    Inscritos: <strong>{{ $inscritosCount }}</strong> |
    Presentes: <strong>{{ $presentesCount }}</strong> |
    Ausentes: <strong>{{ $ausentesCount }}</strong>
</div>

<div id="pres-{{ $atividade->id }}-participantes" class="p-2">
    <div class="section-title fw-bold mb-2 px-2">Participantes</div>
    @if($inscricoes->isEmpty())
        <div class="text-muted small p-3">Nenhum participante registrado.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35%;">Nome</th>
                        <th style="width: 25%;">E-mail</th>
                        <th style="width: 15%;">CPF</th>
                        <th style="width: 13%;">Vínculo</th>
                        <th style="width: 12%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inscricoes as $insc)
                        @php
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                            
                            $isPresente = $presentesIds->contains($insc->id);
                            
                            if ($isPresente) {
                                if ($insc->ouvinte ?? false) {
                                    $statusLabel = 'Ouvinte';
                                    $statusClass = 'bg-info text-dark';
                                } else {
                                    $statusLabel = 'Presente';
                                    $statusClass = 'bg-success text-white';
                                }
                            } else {
                                $statusLabel = 'Ausente';
                                $statusClass = 'bg-warning text-dark';
                            }
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? 'Participante #'.$part->id }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $statusClass }} rounded">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
