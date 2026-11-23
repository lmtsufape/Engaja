<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing') && ! config('engaja.import_ibge_localities_in_tests', false)) {
            return;
        }

        $path = database_path('data/ibge_municipios.json');
        $localidades = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($localidades) || $localidades === []) {
            throw new RuntimeException('A base de municípios do IBGE não foi encontrada.');
        }

        $now = now();
        $regiaoIds = [];
        foreach (['Norte', 'Nordeste I', 'Nordeste II', 'Outras'] as $regiaoNome) {
            $regiao = DB::table('regiaos')
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($regiaoNome)])
                ->first();

            $regiaoIds[$regiaoNome] = $regiao?->id ?? DB::table('regiaos')->insertGetId([
                'nome' => $regiaoNome,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $estadosExistentes = DB::table('estados')
            ->get()
            ->keyBy(fn ($estado) => mb_strtoupper(trim((string) $estado->sigla)));
        $estadoIds = [];

        foreach (collect($localidades)->unique('uf') as $localidade) {
            $sigla = mb_strtoupper(trim((string) $localidade['uf']));
            $estado = $estadosExistentes->get($sigla);

            if ($estado) {
                $estadoIds[$sigla] = (int) $estado->id;
                if ($estado->deleted_at !== null) {
                    DB::table('estados')->where('id', $estado->id)->update([
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                }

                continue;
            }

            $estadoIds[$sigla] = DB::table('estados')->insertGetId([
                'regiao_id' => $regiaoIds[$this->regiaoProjeto($sigla)],
                'nome' => $localidade['estado'],
                'sigla' => $sigla,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        $municipiosExistentes = DB::table('municipios')->get();
        $municipiosPorChave = $municipiosExistentes->keyBy(
            fn ($municipio) => $this->municipioKey((int) $municipio->estado_id, (string) $municipio->nome)
        );

        $novosMunicipios = [];
        $restaurarIds = [];

        foreach ($localidades as $localidade) {
            $estadoId = $estadoIds[mb_strtoupper((string) $localidade['uf'])];
            $key = $this->municipioKey($estadoId, (string) $localidade['nome']);
            $existente = $municipiosPorChave->get($key);

            if ($existente) {
                if ($existente->deleted_at !== null) {
                    $restaurarIds[] = (int) $existente->id;
                }

                continue;
            }

            $novosMunicipios[] = [
                'estado_id' => $estadoId,
                'nome' => $localidade['nome'],
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        foreach (array_chunk($novosMunicipios, 500) as $chunk) {
            DB::table('municipios')->insert($chunk);
        }

        foreach (array_chunk($restaurarIds, 500) as $ids) {
            DB::table('municipios')->whereIn('id', $ids)->update([
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Localidades podem estar referenciadas por participantes e momentos.
    }

    private function municipioKey(int $estadoId, string $nome): string
    {
        $nomeNormalizado = Str::of($nome)->ascii()->lower()->squish()->value();

        return $estadoId.'|'.$nomeNormalizado;
    }

    private function regiaoProjeto(string $uf): string
    {
        return match ($uf) {
            'AP', 'AM', 'PA' => 'Norte',
            'CE', 'RN' => 'Nordeste I',
            'BA', 'PB', 'PE', 'SE' => 'Nordeste II',
            default => 'Outras',
        };
    }
};
