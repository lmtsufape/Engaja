<div>
    <div wire:loading class="text-center p-4">
        Carregando dados...
    </div>
    <div wire:loading.remove>
        <div id="rankingMunicipiosChart" data-dados='@json($dados)'
                data-titulo="{{ $titulo }}">
        </div>
    </div>
</div>
