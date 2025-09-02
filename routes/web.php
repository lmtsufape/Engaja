<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\InscricaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'import'])->name('inscricoes.import');
    Route::post('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'cadastro'])->name('inscricoes.cadastro');

    Route::get('/eventos/{evento}/inscricoes/preview', [InscricaoController::class, 'preview'])->name('inscricoes.preview');
    Route::post('/eventos/{evento}/inscricoes/preview/save', [InscricaoController::class, 'savePage'])->name('inscricoes.preview.save');
    Route::post('/eventos/{evento}/inscricoes/confirmar', [InscricaoController::class, 'confirmar'])->name('inscricoes.confirmar');
    Route::get('/eventos/{evento}/inscritos', [\App\Http\Controllers\InscricaoController::class, 'inscritos'])->name('inscricoes.inscritos');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::resource('eventos', \App\Http\Controllers\EventoController::class);
});

Route::resource('eventos.atividades', AtividadeController::class)
    ->parameters(['atividades' => 'atividade'])
    ->shallow();

require __DIR__.'/auth.php';
