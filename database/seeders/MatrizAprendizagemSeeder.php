<?php

namespace Database\Seeders;

use App\Models\MatrizAprendizagem;
use Illuminate\Database\Seeder;

class MatrizAprendizagemSeeder extends Seeder
{
    public function run(): void
    {
        $matrizes = [
            [
                'id' => 1,
                'nome' => 'Compreender a EJA como direito humano',
                'descricao' => "Reconhecer o analfabetismo como resultado de desigualdades sociais e históricas.\nCompreender a EJA como política de justiça social e garantia de cidadania.\nRelacionar a EJA aos fundamentos da pedagogia freiriana."
            ],
            [
                'id' => 2,
                'nome' => 'Desenvolver práticas pedagógicas contextualizadas e interculturais',
                'descricao' => "Valorizar e integrar os saberes dos educandos ao processo pedagógico.\nTrabalhar com temas geradores ligados à realidade local.\nAlfabetizar a partir de situações do cotidiano dos educandos.\nPlanejar atividades conectadas às culturas locais."
            ],
            [
                'id' => 3,
                'nome' => 'Fortalecer a escuta ativa e a relação afetiva com os educandos',
                'descricao' => "Valorizar acolhimento, respeito e reconhecimento dos sujeitos da EJA.\nDesenvolver escuta sensível, empatia e diálogo horizontal.\nIdentificar e enfrentar fatores que levam à evasão escolar.\nIntegrar a EJA ao mundo do trabalho e à economia solidária."
            ],
            [
                'id' => 4,
                'nome' => 'Relacionar os conteúdos escolares com as experiências profissionais dos alunos.',
                'descricao' => "Trabalhar temas ligados a trabalho, direitos e organização econômica.\nIncentivar a produção de materiais voltados à inserção no mundo do trabalho."
            ],
            [
                'id' => 5,
                'nome' => 'Articular a EJA com as questões ambientais e de sustentabilidade',
                'descricao' => "Desenvolver projetos ambientais vinculados ao território local.\nTrabalhar preservação ambiental e justiça socioambiental.\nIntegrar práticas sustentáveis à EJA."
            ],
            [
                'id' => 6,
                'nome' => 'Utilização da cultura digital como ferramenta pedagógica',
                'descricao' => "Utilizar tecnologias simples e acessíveis nas práticas pedagógicas.\nProduzir conteúdos digitais com educadores e educandos.\nUsar redes sociais como espaços de mobilização e aprendizagem.\nGarantir acessibilidade digital e inclusão."
            ],
            [
                'id' => 7,
                'nome' => 'Promover a participação dos educandos como sujeitos da construção do conhecimento',
                'descricao' => "Incentivar produções autorais dos educandos.\nCriar espaços coletivos de fala e escuta.\nEnvolver educandos na produção de materiais e eventos."
            ],
            [
                'id' => 8,
                'nome' => 'Fortalecer a articulação entre poder público e sociedade civil',
                'descricao' => "Compreender e fortalecer a gestão democrática da educação.\nMobilizar redes locais de apoio à EJA.\nEstimular ações intersetoriais entre políticas públicas.\nAmpliar a participação social na definição das políticas educacionais."
            ],
            [
                'id' => 9,
                'nome' => 'Desenvolver práticas de formação continuada e colaborativa',
                'descricao' => "Compreender a formação como processo contínuo e colaborativo.\nDesenvolver liderança em grupos de estudo e formação entre pares.\nUtilizar o HTPC como espaço de planejamento e reflexão coletiva.\nRegistrar e compartilhar boas práticas pedagógicas."
            ],
            [
                'id' => 10,
                'nome' => 'Trabalhar com diversidade, equidade e inclusão',
                'descricao' => "Reconhecer especificidades de povos e comunidades tradicionais.\nGarantir educação inclusiva e acessível para pessoas com deficiência.\nTrabalhar temas ligados a gênero, raça e direitos humanos.\nEnfrentar discriminações e violências no ambiente escolar."
            ],
            [
                'id' => 11,
                'nome' => 'Dominar estratégias de busca ativa e permanência na EJA',
                'descricao' => "Mapear a demanda por EJA com base em dados oficiais.\nPlanejar campanhas de divulgação acessíveis.\nRealizar visitas domiciliares e comunitárias.\nCriar estratégias institucionais de acolhimento."
            ],
            [
                'id' => 12,
                'nome' => 'Produzir materiais didáticos e autorais',
                'descricao' => "Produzir materiais pedagógicos contextualizados.\nIncentivar produção coletiva de textos com educandos.\nUtilizar arte, música e oralidade como recursos pedagógicos."
            ],
            [
                'id' => 13,
                'nome' => 'Fortalecer memória, autoria e continuidade da EJA',
                'descricao' => "Documentar sistematicamente as ações da modalidade.\nUtilizar o CREJA como espaço de memória e formação.\nRegistrar experiências pedagógicas e trajetórias.\nProduzir narrativas que valorizem a história da EJA."
            ],
            [
                'id' => 14,
                'nome' => 'Fortalecer a identidade do professor da EJA como educador popular',
                'descricao' => "Reconhecer o professor da EJA como sujeito político.\nValorizar o educador como mediador do conhecimento.\nEstimular formação continuada baseada na prática reflexiva."
            ]
        ];

        foreach ($matrizes as $item) {
            MatrizAprendizagem::firstOrCreate(
                ['id' => $item['id']],
                ['nome' => $item['nome'], 'descricao' => $item['descricao']]
            );
        }

        $this->command->info('✅ ' . count($matrizes) . ' matrizes inseridas/verificadas.');
    }
}