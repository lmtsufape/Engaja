<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\BiGeralImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportBiGeral extends Command
{
    protected $signature = 'bi:import-geral {arquivo}';
    protected $description = 'Importa o arquivo CSV com os dados para BI';

    public function handle()
    {
        Excel::import(
            new BiGeralImport(),
            $this->argument('arquivo')
        );

        $this->info('Importação concluída');
    }
}

