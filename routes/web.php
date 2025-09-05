<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\InscricaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::post('/eventos/{evento}/inscrever', [InscricaoController::class, 'inscrever'])->name('inscricoes.inscrever');
    Route::delete('/eventos/{evento}/cancelar', [InscricaoController::class, 'cancelar'])->name('inscricoes.cancelar');
});

Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'import'])->name('inscricoes.import');
    Route::post('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'cadastro'])->name('inscricoes.cadastro');
    Route::get('/eventos/{evento}/inscricoes/preview', [InscricaoController::class, 'preview'])->name('inscricoes.preview');
    Route::post('/eventos/{evento}/inscricoes/preview/save', [InscricaoController::class, 'savePage'])->name('inscricoes.preview.save');
    Route::post('/eventos/{evento}/inscricoes/confirmar', [InscricaoController::class, 'confirmar'])->name('inscricoes.confirmar');
    Route::get('/eventos/{evento}/inscritos', [InscricaoController::class, 'inscritos'])->name('inscricoes.inscritos');
});

Route::middleware(['auth', 'role:administrador|participante'])->group(function () {
    Route::resource('eventos', EventoController::class);
});

Route::resource('eventos.atividades', AtividadeController::class)
    ->parameters(['atividades' => 'atividade'])
    ->shallow();

Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

Route::get('/eventos/{evento}/cadastro-e-inscricao', [EventoController::class, 'cadastro_inscricao'])->name('evento.cadastro_inscricao');
Route::post('/eventos/cadastro-e-inscricao/store', [EventoController::class, 'store_cadastro_inscricao'])->name('evento.store_cadastro_inscricao');

require __DIR__ . '/auth.php';
