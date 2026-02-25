<?php

namespace Database\Seeders;

use App\Models\SituacaoDesafiadora;
use Illuminate\Database\Seeder;

class SituacaoDesafiadoraSeeder extends Seeder
{
    public function run(): void
    {
        $situacoes = [
            // ==========================================
            // CATEGORIA 1: POLÍTICA EDUCACIONAL DA EJA
            // ==========================================
            [
                'id' => 1,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Ausência de um Projeto de EJA em Escala Municipal',
                'descricao' => "Em grande parte dos territórios analisados, a EJA não se sustenta sobre um projeto político-pedagógico municipal próprio, construído de forma coletiva e capaz de orientar, de maneira consistente, a organização da oferta, o currículo, a avaliação e as práticas pedagógicas.\nA modalidade tende a operar por meio de adaptações do ensino regular ou de diretrizes fragmentadas, o que fragiliza sua identidade, reduz sua coerência interna e dificulta a consolidação de uma política educacional com intencionalidade clara e continuidade histórica."
            ],
            [
                'id' => 2,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Dependência de Programas e Apoios Externos nos municípios menores',
                'descricao' => "A política de EJA apresenta forte dependência de programas federais, projetos temporários ou ações induzidas por assessorias externas.\nEmbora essas iniciativas tenham papel relevante na ativação da política, sua centralidade revela a baixa capacidade dos sistemas locais de sustentarem autonomamente processos formativos, pedagógicos e de gestão.\nQuando tais indutores cessam, observa-se descontinuidade das ações, esvaziamento das equipes e perda de acúmulos institucionais, reforçando um ciclo de fragilidade estrutural da política."
            ],
            [
                'id' => 3,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Financiamento Insuficiente e Pouco Transparente da EJA',
                'descricao' => "O subfinanciamento estrutural da EJA permanece como um dos principais entraves à sua consolidação enquanto política pública.\nA baixa previsibilidade orçamentária, aliada à pouca transparência sobre os recursos efetivamente destinados à modalidade, compromete investimentos em infraestrutura, materiais didáticos, formação continuada, transporte, alimentação e tecnologias educacionais.\nA EJA tende a disputar recursos residuais dentro do sistema educacional, reforçando sua posição periférica."
            ],
            [
                'id' => 4,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - EJA como Modalidade de Baixa Centralidade no Sistema Educacional',
                'descricao' => "A EJA ocupa, historicamente, um lugar subalterno na hierarquia das políticas educacionais, sendo frequentemente tratada como ação compensatória ou de segunda ordem.\nEssa baixa centralidade se expressa na ausência da EJA nos discursos estratégicos da educação, na menor visibilidade pública de suas ações e na fragilidade dos investimentos simbólicos e materiais.\nA modalidade não é reconhecida plenamente como política estruturante do direito à educação ao longo da vida."
            ],
            [
                'id' => 5,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Inexistência de Sistemas Próprios de Avaliação e Monitoramento da EJA',
                'descricao' => "Inexistem, nas redes municipais, instrumentos sistemáticos de avaliação e monitoramento capazes de captar as especificidades da EJA.\nA ausência de indicadores próprios sobre articulação territorial, relação com o mundo do trabalho, inovação pedagógica e percepção dos sujeitos limita a capacidade de autoavaliação da política e a tomada de decisões baseadas em evidências qualificadas."
            ],
            [
                'id' => 6,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Fragmentação Territorial da Oferta da EJA',
                'descricao' => "A organização da oferta de EJA ocorre de forma fragmentada no território, com escolas e iniciativas pouco articuladas entre si.\nEssa fragmentação dificulta a construção de redes de troca pedagógica, enfraquece a integração entre diferentes etapas e modalidades da EJA e acentua desigualdades entre contextos urbanos, rurais e comunidades específicas.\nA política se materializa como um conjunto de experiências isoladas, e não como um sistema territorialmente integrado."
            ],
            [
                'id' => 7,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Baixa Incorporação da Educação Popular como Matriz Política',
                'descricao' => "Embora a EJA dialogue simbolicamente com referências da educação popular, essa matriz aparece mais como retórica do que como fundamento efetivo da formulação, gestão e avaliação da política.\nFalta à política educacional assumir de forma explícita quais concepções de educação de jovens e adultos orientam suas decisões, o que enfraquece sua coerência interna e sua capacidade de responder às realidades sociais dos educandos."
            ],
            [
                'id' => 8,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Fragilidade da Participação Social na Formulação da Política de EJA',
                'descricao' => "Mesmo nos territórios onde existem conselhos e espaços formais de participação, a presença ativa de educandos, movimentos sociais e coletivos populares na formulação da política de EJA é limitada.\nA participação tende a ser consultiva, episódica ou burocratizada, reduzindo o potencial da gestão democrática e enfraquecendo o vínculo entre a política educacional e as demandas reais dos territórios."
            ],
            [
                'id' => 9,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Ausência de uma Narrativa Pública Forte sobre o Sentido da EJA',
                'descricao' => "A política de EJA carece de uma narrativa pública consistente que a afirme como direito, estratégia de desenvolvimento territorial e instrumento de justiça social.\nNa ausência dessa narrativa, a modalidade permanece vulnerável à despriorização política, aos cortes orçamentários e à invisibilidade institucional.\nA EJA segue sendo percebida como gasto ou medida paliativa, e não como investimento estruturante na democratização da educação."
            ],
            [
                'id' => 10,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Fragilidades Estruturais no Acesso à EJA',
                'descricao' => "O acesso à EJA é marcado por uma lógica predominantemente passiva, na qual a oferta se organiza a partir da demanda espontânea e não de estratégias ativas de busca, mapeamento, identificação e mobilização dos sujeitos com direito à escolarização.\nA política educacional carece de mecanismos estruturados para mapear territórios, identificar públicos historicamente excluídos e promover ações sistemáticas de ingresso.\nComo resultado, amplos contingentes de jovens, adultos e idosos permanecem invisibilizados, fora do alcance da política, mesmo em contextos onde há oferta formal de vagas."
            ],
            [
                'id' => 11,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Fragilidade das Estratégias de Permanência na EJA',
                'descricao' => "A permanência dos educandos na EJA não é tratada como eixo estruturante da política, mas como responsabilidade difusa das escolas ou dos próprios estudantes.\nA ausência de políticas integradas de permanência — articulando transporte, alimentação, cuidado, flexibilização de tempos e apoio socioeducativo — transforma a evasão em fenômeno naturalizado.\nA política tende a reagir ao abandono após sua ocorrência, em vez de estruturar estratégias preventivas que reconheçam as condições concretas de vida dos educandos."
            ],
            [
                'id' => 12,
                'categoria' => 'POLÍTICA EDUCACIONAL DA EJA',
                'nome' => 'Política EJA - Apagamento da Memória Histórica e Institucional da EJA',
                'descricao' => "A política educacional da EJA apresenta profunda fragilidade na preservação e valorização de sua memória histórica, tanto institucional quanto popular.\nAs experiências acumuladas, as lutas sociais pelo direito à educação, os projetos exitosos e os saberes construídos ao longo do tempo permanecem dispersos, não sistematizados ou restritos à memória oral de educadores e militantes.\nEsse apagamento compromete a identidade da política, impede aprendizagens institucionais e enfraquece o sentimento de pertencimento dos sujeitos envolvidos com a EJA."
            ],

            // ==========================================
            // CATEGORIA 2: GESTÃO DA EJA
            // ==========================================
            [
                'id' => 13,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Gestão Capturada pela Urgência e pela Escassez',
                'descricao' => "A gestão da EJA opera predominantemente sob a lógica da emergência permanente, absorvida por demandas operacionais e administrativas que consomem sua capacidade de planejamento e reflexão estratégica.\nA administração da escassez — de recursos, equipes e tempo institucional — captura o trabalho gestor, produzindo sobrecarga, isolamento técnico e impossibilitando a condução da política como projeto estruturante de médio e longo prazo."
            ],
            [
                'id' => 14,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Fragilidade da EJA como Política Pública Institucionalizada',
                'descricao' => "A EJA ocupa posição periférica na agenda educacional, frequentemente tratada como política compensatória, residual ou dependente de programas e indutores externos.\nEssa fragilidade institucional reduz sua autonomia, compromete a continuidade das ações e enfraquece sua dimensão política enquanto direito educacional ao longo da vida, deslocando-a para um campo técnico-administrativo de baixa prioridade estratégica."
            ],
            [
                'id' => 15,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Baixa Capacidade de Governança Pedagógica da Modalidade',
                'descricao' => "O acompanhamento da EJA pelas instâncias gestoras é marcado por práticas burocráticas e fiscalizatórias, com baixa incidência de interlocução pedagógica qualificada.\nA coordenação da modalidade é frequentemente acumulada, difusa ou fragilizada, o que limita a construção de referenciais pedagógicos, o diálogo com as escolas e a consolidação de uma identidade formativa própria da EJA."
            ],
            [
                'id' => 16,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Fragilidades na Produção, Leitura e Uso Estratégico de Dados locais sobre a EJA',
                'descricao' => "Os sistemas de informação existentes são utilizados prioritariamente para fins de registro formal, sem desdobramentos analíticos consistentes.\nA gestão carece de instrumentos e cultura institucional para compreender trajetórias educacionais, padrões de evasão, permanência e aprendizagem, o que limita a tomada de decisão informada e a capacidade de monitoramento contínuo da política."
            ],
            [
                'id' => 17,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Descontinuidade Administrativa e Erosão da Memória Institucional',
                'descricao' => "A rotatividade de equipes técnicas e coordenações compromete a continuidade das ações e a consolidação de aprendizagens institucionais.\nA ausência de mecanismos formais de preservação da memória administrativa e pedagógica da EJA obriga a retomadas recorrentes, fragilizando processos formativos, projetos em curso e a identidade histórica da política nos territórios."
            ],
            [
                'id' => 18,
                'categoria' => 'GESTÃO DA EJA',
                'nome' => 'Gestão da EJA - Isolamento Institucional da Gestão frente ao Território e às Políticas Intersetoriais',
                'descricao' => "A gestão da EJA atua de forma fragmentada em relação a outras políticas públicas e ao território.\nA intersetorialidade ocorre de maneira pontual e não sistêmica, e os canais de diálogo com movimentos sociais e comunidades são percebidos como frágeis.\nSoma-se a isso a tensão permanente entre normativas rígidas e realidades territoriais complexas, produzindo soluções informais e pouco institucionalizadas."
            ],

            // ==========================================
            // CATEGORIA 3: DOCENTES DA EJA
            // ==========================================
            [
                'id' => 19,
                'categoria' => 'DOCENTES DA EJA',
                'nome' => 'Docentes da EJA - Formação Inicial Inadequada para a Educação de Jovens e Adultos',
                'descricao' => "A maioria dos professores ingressa na EJA sem formação específica EJA, educação popular ou metodologias próprias para o trabalho com adultos.\nO exercício da docência ocorre sob lógica de improvisação, tentativa e erro, gerando insegurança profissional e fragilidade pedagógica."
            ],
            [
                'id' => 20,
                'categoria' => 'DOCENTES DA EJA',
                'nome' => 'Docentes da EJA - Isolamento Pedagógico e Fragilidade do Trabalho Coletivo',
                'descricao' => "O planejamento docente é predominantemente individual, com poucos espaços institucionais para reflexão coletiva qualificada, interdisciplinar e de construção compartilhada de projetos pedagógicos.\nEssa quase “solidão” profissional limita a inovação e reforça práticas fragmentadas."
            ],
            [
                'id' => 21,
                'categoria' => 'DOCENTES DA EJA',
                'nome' => 'Docentes da EJA - Precarização do Vínculo e da Identidade Docente na EJA',
                'descricao' => "A EJA é frequentemente assumida como complemento de carga horária ou por contratos temporários, o que dificulta a construção de pertencimento, continuidade pedagógica e compromisso de longo prazo com a modalidade.\nA rotatividade impacta diretamente a qualidade das experiências educativas."
            ],
            [
                'id' => 22,
                'categoria' => 'DOCENTES DA EJA',
                'nome' => 'Docentes da EJA - Subordinação do Tempo Formativo às Demandas Burocráticas',
                'descricao' => "Os espaços formais de formação em serviço são frequentemente ocupados por tarefas administrativas, repasses e controle institucional.\nO estudo pedagógico, a reflexão sobre práticas e o aprofundamento teórico-metodológico tornam-se residuais."
            ],
            [
                'id' => 23,
                'categoria' => 'DOCENTES DA EJA',
                'nome' => 'Docentes da EJA - Tensão entre Expectativas Institucionais e Realidade dos Educandos',
                'descricao' => "Os professores lidam com currículos, avaliações e normativas pouco flexíveis frente às condições reais dos educandos.\nEssa tensão produz sentimentos de frustração, desgaste emocional e, em alguns casos, desresponsabilização pedagógica frente à evasão e ao baixo rendimento."
            ],

            // ==========================================
            // CATEGORIA 4: EDUCANDOS DA EJA
            // ==========================================
            [
                'id' => 24,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Escolarização Interrompida e Relação Frágil com a Instituição Escolar',
                'descricao' => "Os educandos da EJA carregam trajetórias marcadas por exclusões sucessivas, experiências de fracasso escolar e longos períodos fora da escola.\nA relação com a instituição escolar é atravessada por desconfiança, insegurança e baixa expectativa de sucesso, o que impacta diretamente a permanência e o engajamento pedagógico."
            ],
            [
                'id' => 25,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Conflito entre Tempos da Vida, do Trabalho e da Escola',
                'descricao' => "A organização rígida da oferta educacional entra em choque com as dinâmicas de sobrevivência dos educandos, especialmente trabalhadores informais, sazonais, rurais, ribeirinhos e mulheres responsáveis pelo cuidado familiar.\nA escola, ao não reconhecer esses tempos, acaba operando como fator de exclusão indireta."
            ],
            [
                'id' => 26,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Baixa Percepção de Sentido Prático da Escolarização',
                'descricao' => "Há recorrente dificuldade dos educandos em estabelecer vínculos entre a experiência escolar e a melhoria concreta de suas condições de vida.\nA desconexão entre currículo, mundo do trabalho e projetos de vida fragiliza o valor social atribuído à EJA, produzindo desmotivação e evasão."
            ],
            [
                'id' => 27,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Invisibilidade dos Saberes e da Experiência Social dos Educandos',
                'descricao' => "Os conhecimentos construídos no trabalho, na vida comunitária, na economia popular e nos movimentos sociais são pouco reconhecidos como saber legítimo.\nEssa desvalorização simbólica compromete a autoestima dos educandos e reforça a percepção de que a escola não dialoga com suas realidades."
            ],
            [
                'id' => 28,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Barreiras Estruturais à Permanência',
                'descricao' => "Questões como transporte, alimentação, segurança, cuidado com filhos e acesso precário à tecnologia configuram obstáculos cotidianos à permanência.\nEssas barreiras não são episódicas, mas estruturais, e afetam de maneira desigual grupos específicos, especialmente mulheres e populações em maior vulnerabilidade social."
            ],
            [
                'id' => 29,
                'categoria' => 'EDUCANDOS DA EJA',
                'nome' => 'Educandos da EJA - Baixa participação da EJA no Grêmio Estudantil',
                'descricao' => "Praticamente inexiste organização estudantil na EJA e nenhuma evidência de políticas locais de incentivo à organização e participação da modalidade em Grêmios ou semelhantes, impossibilitando um maior protagonismo do aluno da EJA na gestão escolar."
            ],
        ];

        foreach ($situacoes as $item) {
            SituacaoDesafiadora::firstOrCreate(
                ['id' => $item['id']],
                [
                    'categoria' => $item['categoria'],
                    'nome' => $item['nome'],
                    'descricao' => $item['descricao']
                ]
            );
        }

        $this->command->info('✅ ' . count($situacoes) . ' situações desafiadoras inseridas/verificadas com sucesso.');
    }
}