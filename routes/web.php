<?php

use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\DimensaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscalaController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\IndicadorController;
use App\Http\Controllers\EvidenciaController;
use App\Http\Controllers\PresencaController;
use App\Http\Controllers\PresencaImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestaoController;
use App\Http\Controllers\TemplateAvaliacaoController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ModeloCertificadoController;
use App\Http\Controllers\CertificadoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboards/presencas', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboards.presencas');
Route::get('/dashboard/export', [DashboardController::class, 'export'])->middleware(['auth', 'verified'])->name('dashboard.export');
Route::get('/dashboards/avaliacoes', [DashboardController::class, 'avaliacoes'])->middleware(['auth', 'verified'])->name('dashboards.avaliacoes');
Route::get('/dashboards/avaliacoes/dados', [DashboardController::class, 'avaliacoesData'])->middleware(['auth', 'verified'])->name('dashboards.avaliacoes.data');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/eventos/{evento}/inscrever', [InscricaoController::class, 'inscrever'])->name('inscricoes.inscrever');
    Route::delete('/eventos/{evento}/cancelar', [InscricaoController::class, 'cancelar'])->name('inscricoes.cancelar');

    Route::post('/atividades/{atividade}/presenca/checkin', [AtividadeController::class, 'checkin'])->name('atividades.presenca.checkin');
});

Route::middleware(['auth', 'permission:presenca.abrir'])->group(function () {
    Route::patch('/atividades/{atividade}/presenca/toggle', [AtividadeController::class, 'togglePresenca'])->name('atividades.presenca.toggle');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/atividades/{atividade}/presencas/import', [PresencaImportController::class, 'import'])->name('atividades.presencas.import');
    Route::post('/atividades/{atividade}/presencas/import', [PresencaImportController::class, 'cadastro'])->name('atividades.presencas.cadastro');

    Route::get('/atividades/{atividade}/presencas/preview', [PresencaImportController::class, 'preview'])->name('atividades.presencas.preview');
    Route::post('/atividades/{atividade}/presencas/savepage', [PresencaImportController::class, 'savePage'])->name('atividades.presencas.savepage');
    Route::post('/atividades/{atividade}/presencas/confirmar', [PresencaImportController::class, 'confirmar'])->name('atividades.presencas.confirmar');

    Route::get('/meus-certificados', [ProfileController::class, 'certificados'])->name('profile.certificados');
});

Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'import'])->name('inscricoes.import');
    Route::post('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'cadastro'])->name('inscricoes.cadastro');
    Route::get('/eventos/{evento}/inscricoes/preview', [InscricaoController::class, 'preview'])->name('inscricoes.preview');
    Route::post('/eventos/{evento}/inscricoes/preview/save', [InscricaoController::class, 'savePage'])->name('inscricoes.preview.save');
    Route::post('/eventos/{evento}/inscricoes/confirmar', [InscricaoController::class, 'confirmar'])->name('inscricoes.confirmar');
    Route::get('/eventos/{evento}/inscritos', [InscricaoController::class, 'inscritos'])->name('inscricoes.inscritos');
    Route::get('/eventos/{evento}/inscricoes/selecionar', [InscricaoController::class, 'selecionar'])->name('inscricoes.selecionar');
    Route::post('/eventos/{evento}/inscricoes/selecionar', [InscricaoController::class, 'selecionarStore'])->name('inscricoes.selecionar.store');

    Route::resource('dimensaos', DimensaoController::class);
    Route::resource('indicadors', IndicadorController::class);
    Route::resource('evidencias', EvidenciaController::class);
    Route::resource('escalas', EscalaController::class);
    Route::resource('questaos', QuestaoController::class);
    Route::resource('templates-avaliacao', TemplateAvaliacaoController::class)
        ->parameters(['templates-avaliacao' => 'template']);
    Route::resource('avaliacoes', AvaliacaoController::class)
        ->parameters(['avaliacoes' => 'avaliacao']);
});

Route::middleware(['auth', 'role:administrador|gestor'])
    ->prefix('certificados')
    ->name('certificados.')
    ->group(function () {
        Route::resource('modelos', ModeloCertificadoController::class)
            ->parameters(['modelos' => 'modelo']);
        Route::post('emitir', [CertificadoController::class, 'emitir'])->name('emitir');
    });

Route::middleware(['auth', 'role:administrador|gestor'])
    ->prefix('usuarios')
    ->name('usuarios.')
    ->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('{managedUser}/editar', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('{managedUser}', [UserManagementController::class, 'update'])->name('update');
        Route::post('certificados/emitir', [CertificadoController::class, 'emitirPorParticipantes'])->name('certificados.emitir');
    });

Route::middleware(['auth', 'role:administrador|participante'])->group(function () {
    Route::resource('eventos', EventoController::class);
});

Route::resource('eventos.atividades', AtividadeController::class)
    ->parameters(['atividades' => 'atividade'])
    ->shallow();

Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

Route::get('/eventos/{evento_id}/{atividade_id}/cadastro-e-inscricao', [EventoController::class, 'cadastro_inscricao'])->name('evento.cadastro_inscricao');
Route::post('/eventos/cadastro-e-inscricao/store', [EventoController::class, 'store_cadastro_inscricao'])->name('evento.store_cadastro_inscricao');

Route::get('/presenca/{atividade}/confirmar', [PresencaController::class, 'confirmarPresenca'])->name('presenca.confirmar');
Route::post('/presenca/{atividade}/confirmar', [PresencaController::class, 'store'])->name('presenca.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/meus-certificados', [ProfileController::class, 'certificados'])->name('profile.certificados');
    Route::get('/certificados/preview', [CertificadoController::class, 'preview'])->name('certificados.preview');
    Route::get('/certificados/{certificado}', [CertificadoController::class, 'show'])
        ->whereNumber('certificado')
        ->name('certificados.show');
    Route::get('/certificados/{certificado}/download', [CertificadoController::class, 'download'])
        ->whereNumber('certificado')
        ->name('certificados.download');
});
Route::middleware(['auth', 'role:administrador|gestor'])->group(function () {
    Route::get('/certificados/emitidos', [CertificadoController::class, 'emitidos'])->name('certificados.emitidos');
    Route::get('/certificados/{certificado}/edit', [CertificadoController::class, 'edit'])
        ->whereNumber('certificado')
        ->name('certificados.edit');
    Route::put('/certificados/{certificado}', [CertificadoController::class, 'update'])
        ->whereNumber('certificado')
        ->name('certificados.update');
});

Route::get( '/formulario-avaliacao/{avaliacao}', [AvaliacaoController::class, 'formularioAvaliacao'])->name('avaliacao.formulario');
Route::post('/formulario-avaliacao/{avaliacao}', [AvaliacaoController::class, 'responderFormulario'])->name('avaliacao.formulario.responder');
Route::get('/validacao/{codigo}', [CertificadoController::class, 'validar'])->name('certificados.validacao');

require __DIR__ . '/auth.php';
