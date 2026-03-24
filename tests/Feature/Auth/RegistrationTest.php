<?php

namespace Tests\Feature\Auth;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Regiao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('public');

        $regiao = Regiao::create(['nome' => 'Nordeste']);
        $estado = Estado::create([
            'nome' => 'Ceara',
            'sigla' => 'CE',
            'regiao_id' => $regiao->id,
        ]);
        $municipio = Municipio::create([
            'nome' => 'Fortaleza',
            'estado_id' => $estado->id,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cpf' => '390.533.447-05',
            'telefone' => '(85) 99999-0000',
            'municipio_id' => $municipio->id,
            'escola_unidade' => 'Escola Teste',
            'tipo_organizacao' => config('engaja.organizacoes')[0] ?? null,
            'tag' => Participante::TAGS[0],
            'identidade_genero' => 'Mulher Cisgênero',
            'raca_cor' => 'Parda',
            'comunidade_tradicional' => 'Não',
            'faixa_etaria' => 'Adulto (18 a 59 anos)',
            'pcd' => 'Não',
            'orientacao_sexual' => 'Heterossexual',
            'profile_photo' => UploadedFile::fake()->image('perfil.jpg'),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');

        $this->assertDatabaseHas('participantes', [
            'cpf' => '39053344705',
            'telefone' => '85999990000',
            'municipio_id' => $municipio->id,
            'escola_unidade' => 'Escola Teste',
            'tag' => Participante::TAGS[0],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }
}
