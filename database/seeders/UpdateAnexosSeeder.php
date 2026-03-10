<?php

namespace Database\Seeders;

use App\Models\MatrizAprendizagem;
use App\Models\SituacaoDesafiadora;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateAnexosSeeder extends Seeder
{
    public function run(): void
    {
        // Desvincula relações antes de limpar os registros
        DB::table('evento_situacao_desafiadora')->delete();
        DB::table('evento_matriz_aprendizagem')->delete();

        SituacaoDesafiadora::truncate();
        MatrizAprendizagem::truncate();

        // ════════════════════════════════════════════════════════════════════
        // ANEXO 2 — Situações Desafiadoras da EJA
        // ════════════════════════════════════════════════════════════════════

        $situacoes = [

            // 1. Política EJA
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.1. Ausência de um Projeto de EJA em Escala Municipal',
                'descricao' => 'Em grande parte dos territórios analisados, a EJA não se sustenta sobre um projeto político-pedagógico municipal próprio, construído de forma coletiva e capaz de orientar, de maneira consistente, a organização da oferta, o currículo, a avaliação e as práticas pedagógicas. A modalidade tende a operar por meio de adaptações do ensino regular ou de diretrizes fragmentadas, o que fragiliza sua identidade, reduz sua coerência interna e dificulta a consolidação de uma política educacional com intencionalidade clara e continuidade histórica.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.2. Dependência de Programas e Apoios Externos nos municípios menores',
                'descricao' => 'A política de EJA apresenta forte dependência de programas federais, projetos temporários ou ações induzidas por assessorias externas. Embora essas iniciativas tenham papel relevante na ativação da política, sua centralidade revela a baixa capacidade dos sistemas locais de sustentarem autonomamente processos formativos, pedagógicos e de gestão. Quando tais indutores cessam, observa-se descontinuidade das ações, esvaziamento das equipes e perda de acúmulos institucionais, reforçando um ciclo de fragilidade estrutural da política.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.3. Financiamento Insuficiente e Pouco Transparente da EJA',
                'descricao' => 'O subfinanciamento estrutural da EJA permanece como um dos principais entraves à sua consolidação enquanto política pública. A baixa previsibilidade orçamentária, aliada à pouca transparência sobre os recursos efetivamente destinados à modalidade, compromete investimentos em infraestrutura, materiais didáticos, formação continuada, transporte, alimentação e tecnologias educacionais. A EJA tende a disputar recursos residuais dentro do sistema educacional, reforçando sua posição periférica.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.4. EJA como Modalidade de Baixa Centralidade no Sistema Educacional',
                'descricao' => 'A EJA ocupa, historicamente, um lugar subalterno na hierarquia das políticas educacionais, sendo frequentemente tratada como ação compensatória ou de segunda ordem. Essa baixa centralidade se expressa na ausência da EJA nos discursos estratégicos da educação, na menor visibilidade pública de suas ações e na fragilidade dos investimentos simbólicos e materiais. A modalidade não é reconhecida plenamente como política estruturante do direito à educação ao longo da vida.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.5. Inexistência de Sistemas Próprios de Avaliação e Monitoramento da EJA',
                'descricao' => 'Inexistem, nas redes municipais, instrumentos sistemáticos de avaliação e monitoramento capazes de captar as especificidades da EJA. A ausência de indicadores próprios sobre articulação territorial, relação com o mundo do trabalho, inovação pedagógica e percepção dos sujeitos limita a capacidade de autoavaliação da política e a tomada de decisões baseadas em evidências qualificadas.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.6. Fragmentação Territorial da Oferta da EJA',
                'descricao' => 'A organização da oferta de EJA ocorre de forma fragmentada no território, com escolas e iniciativas pouco articuladas entre si. Essa fragmentação dificulta a construção de redes de troca pedagógica, enfraquece a integração entre diferentes etapas e modalidades da EJA e acentua desigualdades entre contextos urbanos, rurais e comunidades específicas. A política se materializa como um conjunto de experiências isoladas, e não como um sistema territorialmente integrado.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.7. Baixa Incorporação da Educação Popular como Matriz Política',
                'descricao' => 'Embora a EJA dialogue simbolicamente com referências da educação popular, essa matriz aparece mais como retórica do que como fundamento efetivo da formulação, gestão e avaliação da política. Falta à política educacional assumir de forma explícita quais concepções de educação de jovens e adultos orientam suas decisões, o que enfraquece sua coerência interna e sua capacidade de responder às realidades sociais dos educandos.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.8. Fragilidade da Participação Social na Formulação da Política de EJA',
                'descricao' => 'Mesmo nos territórios onde existem conselhos e espaços formais de participação, a presença ativa de educandos, movimentos sociais e coletivos populares na formulação da política de EJA é limitada. A participação tende a ser consultiva, episódica ou burocratizada, reduzindo o potencial da gestão democrática e enfraquecendo o vínculo entre a política educacional e as demandas reais dos territórios.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.9. Ausência de uma Narrativa Pública Forte sobre o Sentido da EJA',
                'descricao' => 'A política de EJA carece de uma narrativa pública consistente que a afirme como direito, estratégia de desenvolvimento territorial e instrumento de justiça social. Na ausência dessa narrativa, a modalidade permanece vulnerável à despriorização política, aos cortes orçamentários e à invisibilidade institucional. A EJA segue sendo percebida como gasto ou medida paliativa, e não como investimento estruturante na democratização da educação.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.10. Fragilidades Estruturais no Acesso à EJA',
                'descricao' => 'O acesso à EJA é marcado por uma lógica predominantemente passiva, na qual a oferta se organiza a partir da demanda espontânea e não de estratégias ativas de busca, mapeamento, identificação e mobilização dos sujeitos com direito à escolarização. A política educacional carece de mecanismos estruturados para mapear territórios, identificar públicos historicamente excluídos e promover ações sistemáticas de ingresso. Como resultado, amplos contingentes de jovens, adultos e idosos permanecem invisibilizados, fora do alcance da política, mesmo em contextos onde há oferta formal de vagas.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.11. Fragilidade das Estratégias de Permanência na EJA',
                'descricao' => 'A permanência dos educandos na EJA não é tratada como eixo estruturante da política, mas como responsabilidade difusa das escolas ou dos próprios estudantes. A ausência de políticas integradas de permanência — articulando transporte, alimentação, cuidado, flexibilização de tempos e apoio socioeducativo — transforma a evasão em fenômeno naturalizado. A política tende a reagir ao abandono após sua ocorrência, em vez de estruturar estratégias preventivas que reconheçam as condições concretas de vida dos educandos.',
            ],
            [
                'categoria' => 'Política EJA',
                'nome'      => '1.12. Apagamento da Memória Histórica e Institucional da EJA',
                'descricao' => 'A política educacional da EJA apresenta profunda fragilidade na preservação e valorização de sua memória histórica, tanto institucional quanto popular. As experiências acumuladas, as lutas sociais pelo direito à educação, os projetos exitosos e os saberes construídos ao longo do tempo permanecem dispersos, não sistematizados ou restritos à memória oral de educadores e militantes. Esse apagamento compromete a identidade da política, impede aprendizagens institucionais e enfraquece o sentimento de pertencimento dos sujeitos envolvidos com a EJA.',
            ],

            // 2. Gestão da EJA
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.1. Gestão Capturada pela Urgência e pela Escassez',
                'descricao' => 'A gestão da EJA opera predominantemente sob a lógica da emergência permanente, absorvida por demandas operacionais e administrativas que consomem sua capacidade de planejamento e reflexão estratégica. A administração da escassez — de recursos, equipes e tempo institucional — captura o trabalho gestor, produzindo sobrecarga, isolamento técnico e impossibilitando a condução da política como projeto estruturante de médio e longo prazo.',
            ],
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.2. Fragilidade da EJA como Política Pública Institucionalizada',
                'descricao' => 'A EJA ocupa posição periférica na agenda educacional, frequentemente tratada como política compensatória, residual ou dependente de programas e indutores externos. Essa fragilidade institucional reduz sua autonomia, compromete a continuidade das ações e enfraquece sua dimensão política enquanto direito educacional ao longo da vida, deslocando-a para um campo técnico-administrativo de baixa prioridade estratégica.',
            ],
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.3. Baixa Capacidade de Governança Pedagógica da Modalidade',
                'descricao' => 'O acompanhamento da EJA pelas instâncias gestoras é marcado por práticas burocráticas e fiscalizatórias, com baixa incidência de interlocução pedagógica qualificada. A coordenação da modalidade é frequentemente acumulada, difusa ou fragilizada, o que limita a construção de referenciais pedagógicos, o diálogo com as escolas e a consolidação de uma identidade formativa própria da EJA.',
            ],
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.4. Fragilidades na Produção, Leitura e Uso Estratégico de Dados locais sobre a EJA',
                'descricao' => 'Os sistemas de informação existentes são utilizados prioritariamente para fins de registro formal, sem desdobramentos analíticos consistentes. A gestão carece de instrumentos e cultura institucional para compreender trajetórias educacionais, padrões de evasão, permanência e aprendizagem, o que limita a tomada de decisão informada e a capacidade de monitoramento contínuo da política.',
            ],
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.5. Descontinuidade Administrativa e Erosão da Memória Institucional',
                'descricao' => 'A rotatividade de equipes técnicas e coordenações compromete a continuidade das ações e a consolidação de aprendizagens institucionais. A ausência de mecanismos formais de preservação da memória administrativa e pedagógica da EJA obriga a retomadas recorrentes, fragilizando processos formativos, projetos em curso e a identidade histórica da política nos territórios.',
            ],
            [
                'categoria' => 'Gestão da EJA',
                'nome'      => '2.6. Isolamento Institucional da Gestão frente ao Território e às Políticas Intersetoriais',
                'descricao' => 'A gestão da EJA atua de forma fragmentada em relação a outras políticas públicas e ao território. A intersetorialidade ocorre de maneira pontual e não sistêmica, e os canais de diálogo com movimentos sociais e comunidades são percebidos como frágeis. Soma-se a isso a tensão permanente entre normativas rígidas e realidades territoriais complexas, produzindo soluções informais e pouco institucionalizadas.',
            ],

            // 3. Docentes da EJA
            [
                'categoria' => 'Docentes da EJA',
                'nome'      => '3.1. Formação Inicial Inadequada para a Educação de Jovens e Adultos',
                'descricao' => 'A maioria dos professores ingressa na EJA sem formação específica EJA, educação popular ou metodologias próprias para o trabalho com adultos. O exercício da docência ocorre sob lógica de improvisação, tentativa e erro, gerando insegurança profissional e fragilidade pedagógica.',
            ],
            [
                'categoria' => 'Docentes da EJA',
                'nome'      => '3.2. Isolamento Pedagógico e Fragilidade do Trabalho Coletivo',
                'descricao' => 'O planejamento docente é predominantemente individual, com poucos espaços institucionais para reflexão coletiva qualificada, interdisciplinar e de construção compartilhada de projetos pedagógicos. Essa quase "solidão" profissional limita a inovação e reforça práticas fragmentadas.',
            ],
            [
                'categoria' => 'Docentes da EJA',
                'nome'      => '3.3. Precarização do Vínculo e da Identidade Docente na EJA',
                'descricao' => 'A EJA é frequentemente assumida como complemento de carga horária ou por contratos temporários, o que dificulta a construção de pertencimento, continuidade pedagógica e compromisso de longo prazo com a modalidade. A rotatividade impacta diretamente a qualidade das experiências educativas.',
            ],
            [
                'categoria' => 'Docentes da EJA',
                'nome'      => '3.4. Subordinação do Tempo Formativo às Demandas Burocráticas',
                'descricao' => 'Os espaços formais de formação em serviço são frequentemente ocupados por tarefas administrativas, repasses e controle institucional. O estudo pedagógico, a reflexão sobre práticas e o aprofundamento teórico-metodológico tornam-se residuais.',
            ],
            [
                'categoria' => 'Docentes da EJA',
                'nome'      => '3.5. Tensão entre Expectativas Institucionais e Realidade dos Educandos',
                'descricao' => 'Os professores lidam com currículos, avaliações e normativas pouco flexíveis frente às condições reais dos educandos. Essa tensão produz sentimentos de frustração, desgaste emocional e, em alguns casos, desresponsabilização pedagógica frente à evasão e ao baixo rendimento.',
            ],

            // 4. Educandos da EJA
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.1. Escolarização Interrompida e Relação Frágil com a Instituição Escolar',
                'descricao' => 'Os educandos da EJA carregam trajetórias marcadas por exclusões sucessivas, experiências de fracasso escolar e longos períodos fora da escola. A relação com a instituição escolar é atravessada por desconfiança, insegurança e baixa expectativa de sucesso, o que impacta diretamente a permanência e o engajamento pedagógico.',
            ],
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.2. Conflito entre Tempos da Vida, do Trabalho e da Escola',
                'descricao' => 'A organização rígida da oferta educacional entra em choque com as dinâmicas de sobrevivência dos educandos, especialmente trabalhadores informais, sazonais, rurais, ribeirinhos e mulheres responsáveis pelo cuidado familiar. A escola, ao não reconhecer esses tempos, acaba operando como fator de exclusão indireta.',
            ],
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.3. Baixa Percepção de Sentido Prático da Escolarização',
                'descricao' => 'Há recorrente dificuldade dos educandos em estabelecer vínculos entre a experiência escolar e a melhoria concreta de suas condições de vida. A desconexão entre currículo, mundo do trabalho e projetos de vida fragiliza o valor social atribuído à EJA, produzindo desmotivação e evasão.',
            ],
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.4. Invisibilidade dos Saberes e da Experiência Social dos Educandos',
                'descricao' => 'Os conhecimentos construídos no trabalho, na vida comunitária, na economia popular e nos movimentos sociais são pouco reconhecidos como saber legítimo. Essa desvalorização simbólica compromete a autoestima dos educandos e reforça a percepção de que a escola não dialoga com suas realidades.',
            ],
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.5. Barreiras Estruturais à Permanência',
                'descricao' => 'Questões como transporte, alimentação, segurança, cuidado com filhos e acesso precário à tecnologia configuram obstáculos cotidianos à permanência. Essas barreiras não são episódicas, mas estruturais, e afetam de maneira desigual grupos específicos, especialmente mulheres e populações em maior vulnerabilidade social.',
            ],
            [
                'categoria' => 'Educandos da EJA',
                'nome'      => '4.6. Baixa participação da EJA no Grêmio Estudantil',
                'descricao' => 'Praticamente inexiste organização estudantil na EJA e nenhuma evidência de políticas locais de incentivo à organização e participação da modalidade em Grêmios ou semelhantes, impossibilitando um maior protagonismo do aluno da EJA na gestão escolar.',
            ],
        ];

        foreach ($situacoes as $item) {
            SituacaoDesafiadora::create($item);
        }

        // ════════════════════════════════════════════════════════════════════
        // ANEXO 3 — Matriz de Aprendizagens
        // ════════════════════════════════════════════════════════════════════

        $matrizes = [

            // 1. EJA como direito humano
            [
                'nome'      => '1.1. EJA como direito humano – Reconhecer o analfabetismo como resultado de desigualdades sociais e históricas',
                'descricao' => 'Compreende o analfabetismo como expressão de processos históricos de exclusão, pobreza, racismo estrutural e negação de direitos. Desloca a interpretação do campo individual para o campo das responsabilidades coletivas e das políticas públicas.',
            ],
            [
                'nome'      => '1.2. EJA como direito humano – Compreender a EJA como política de justiça social e garantia de cidadania',
                'descricao' => 'Envolve entender a EJA como parte do direito à educação ao longo da vida, vinculada à dignidade humana, à participação social e ao acesso a outros direitos civis, políticos, sociais e culturais.',
            ],
            [
                'nome'      => '1.3. EJA como direito humano – Relacionar a EJA aos fundamentos da pedagogia freiriana',
                'descricao' => 'Implica reconhecer o diálogo, a leitura crítica da realidade, a problematização e a consciência histórica como fundamentos metodológicos e políticos que orientam a prática educativa na EJA.',
            ],

            // 2. Práticas Pedagógicas
            [
                'nome'      => '2.4. Práticas Pedagógicas – Valorizar e integrar os saberes dos educandos ao processo pedagógico',
                'descricao' => 'Refere-se ao reconhecimento dos conhecimentos construídos no trabalho, na família, na cultura e na comunidade como saberes legítimos, incorporando-os à organização das atividades e à construção do currículo.',
            ],
            [
                'nome'      => '2.5. Práticas Pedagógicas – Trabalhar com temas geradores ligados à realidade local',
                'descricao' => 'Envolve organizar o ensino a partir de questões vividas no território, como trabalho, meio ambiente, cultura e saúde, estruturando o currículo em torno de problemas reais e significativos.',
            ],
            [
                'nome'      => '2.6. Práticas Pedagógicas – Alfabetizar a partir de situações do cotidiano dos educandos',
                'descricao' => 'Significa relacionar leitura e escrita a documentos, práticas sociais e situações concretas da vida diária, integrando alfabetização e letramento de forma contextualizada.',
            ],
            [
                'nome'      => '2.7. Práticas Pedagógicas – Planejar atividades conectadas às culturas locais',
                'descricao' => 'Consiste em incorporar manifestações culturais, festas populares, saberes tradicionais e expressões artísticas do território como conteúdos formativos da experiência escolar.',
            ],

            // 3. Escuta Ativa
            [
                'nome'      => '3.1. Escuta Ativa – Valorizar acolhimento, respeito e reconhecimento dos sujeitos da EJA',
                'descricao' => 'Refere-se à construção de relações pedagógicas baseadas no reconhecimento da trajetória de vida dos educandos, considerando suas experiências, expectativas e formas próprias de expressão.',
            ],
            [
                'nome'      => '3.2. Escuta Ativa – Desenvolver escuta sensível, empatia e diálogo horizontal',
                'descricao' => 'Implica adotar práticas comunicativas que favoreçam a participação ativa dos educandos, promovendo interações não autoritárias e valorizando a construção compartilhada do conhecimento.',
            ],
            [
                'nome'      => '3.3. Escuta Ativa – Identificar e enfrentar fatores que levam à evasão escolar',
                'descricao' => 'Consiste em analisar causas estruturais e subjetivas do abandono escolar, como jornadas de trabalho extensas, responsabilidades familiares e experiências anteriores de fracasso.',
            ],
            [
                'nome'      => '3.4. Escuta Ativa – Integrar a EJA ao mundo do trabalho e à economia solidária',
                'descricao' => 'Envolve articular conteúdos escolares às condições concretas de trabalho e geração de renda, considerando formas coletivas e solidárias de organização econômica.',
            ],

            // 4. Currículo Vivo
            [
                'nome'      => '4.1. Currículo Vivo – Trabalhar temas ligados a trabalho, direitos e organização econômica',
                'descricao' => 'Refere-se à incorporação de conteúdos relacionados a direitos trabalhistas, organização sindical, economia familiar e cooperativismo como parte do processo formativo.',
            ],
            [
                'nome'      => '4.2. Currículo Vivo – Incentivar a produção de materiais voltados à inserção no mundo do trabalho',
                'descricao' => 'Consiste na elaboração de currículos, cartas e registros de experiência, articulando práticas escolares às trajetórias profissionais dos educandos.',
            ],

            // 5. Sustentabilidade
            [
                'nome'      => '5.1. Sustentabilidade – Desenvolver projetos ambientais vinculados ao território local',
                'descricao' => 'Envolve trabalhar temas ambientais a partir de realidades específicas como manguezais, rios, agricultura familiar e pesca, relacionando preservação e modos de vida locais.',
            ],
            [
                'nome'      => '5.2. Sustentabilidade – Trabalhar preservação ambiental e justiça socioambiental',
                'descricao' => 'Refere-se à compreensão das relações entre meio ambiente, desigualdade social e formas de produção e consumo, analisando impactos sobre comunidades vulnerabilizadas.',
            ],
            [
                'nome'      => '5.3. Sustentabilidade – Integrar práticas sustentáveis à EJA',
                'descricao' => 'Consiste em incorporar princípios de agroecologia, permacultura e uso racional de recursos naturais ao currículo e às práticas pedagógicas.',
            ],

            // 6. Cultura Digital
            [
                'nome'      => '6.1. Cultura Digital – Utilizar tecnologias simples e acessíveis nas práticas pedagógicas',
                'descricao' => 'Implica o uso pedagógico de ferramentas digitais amplamente disponíveis, integrando comunicação, registro e produção de conteúdo ao cotidiano escolar.',
            ],
            [
                'nome'      => '6.2. Cultura Digital – Produzir conteúdos digitais com educadores e educandos',
                'descricao' => 'Envolve a criação de vídeos, áudios e podcasts como forma de expressão autoral, ampliando as linguagens utilizadas na EJA.',
            ],
            [
                'nome'      => '6.3. Cultura Digital – Usar redes sociais como espaços de mobilização e aprendizagem',
                'descricao' => 'Refere-se à utilização das plataformas digitais como ambientes de circulação de informações, divulgação de ações e fortalecimento de vínculos comunitários.',
            ],
            [
                'nome'      => '6.4. Cultura Digital – Garantir acessibilidade digital e inclusão',
                'descricao' => 'Consiste na adoção de recursos como Libras, audiodescrição e legendas, assegurando acesso pleno aos conteúdos digitais por diferentes públicos.',
            ],

            // 7. Protagonismo do Educando
            [
                'nome'      => '7.1. Protagonismo do Educando – Incentivar produções autorais dos educandos',
                'descricao' => 'Refere-se à criação de condições para que educandos expressem suas experiências por meio da escrita de histórias de vida, crônicas, poemas e cartas, reconhecendo a narrativa como forma legítima de produção de conhecimento.',
            ],
            [
                'nome'      => '7.2. Protagonismo do Educando – Criar espaços coletivos de fala e escuta',
                'descricao' => 'Envolve a organização de rodas de conversa, assembleias e círculos de diálogo como práticas regulares, favorecendo a participação ativa e a construção coletiva de decisões e reflexões pedagógicas.',
            ],
            [
                'nome'      => '7.3. Protagonismo do Educando – Envolver educandos na produção de materiais e eventos',
                'descricao' => 'Consiste em incluir os educandos na coautoria de materiais didáticos, murais, feiras e atividades públicas, ampliando sua presença na organização da vida escolar.',
            ],

            // 8. Articulação Social
            [
                'nome'      => '8.1. Articulação Social – Compreender e fortalecer a gestão democrática da educação',
                'descricao' => 'Refere-se à compreensão dos processos decisórios coletivos como parte constitutiva da política educacional, reconhecendo a participação como dimensão organizadora da vida escolar.',
            ],
            [
                'nome'      => '8.2. Articulação Social – Mobilizar redes locais de apoio à EJA',
                'descricao' => 'Envolve identificar e dialogar com sindicatos, associações, movimentos sociais e organizações comunitárias, integrando a escola às dinâmicas sociais do território.',
            ],
            [
                'nome'      => '8.3. Articulação Social – Estimular ações intersetoriais entre políticas públicas',
                'descricao' => 'Consiste na articulação entre Educação, Saúde, Assistência Social e Trabalho, considerando a complexidade das condições de vida dos educandos.',
            ],
            [
                'nome'      => '8.4. Articulação Social – Ampliar a participação social na definição das políticas educacionais',
                'descricao' => 'Implica fortalecer conselhos escolares, fóruns populares e espaços deliberativos na formulação, acompanhamento e avaliação da política de EJA.',
            ],

            // 9. Formação Docente
            [
                'nome'      => '9.1. Formação Docente – Compreender a formação como processo contínuo e colaborativo',
                'descricao' => 'Refere-se à formação como prática permanente, coletiva e dialógica, vinculada à experiência cotidiana e à reflexão crítica sobre a prática pedagógica.',
            ],
            [
                'nome'      => '9.2. Formação Docente – Desenvolver liderança em grupos de estudo e formação entre pares',
                'descricao' => 'Envolve a organização de espaços formativos conduzidos pelos próprios educadores, estimulando autonomia intelectual e corresponsabilidade profissional.',
            ],
            [
                'nome'      => '9.3. Formação Docente – Utilizar o HTPC como espaço de planejamento e reflexão coletiva',
                'descricao' => 'Consiste na valorização do tempo coletivo institucional como momento de estudo, avaliação e construção compartilhada do trabalho pedagógico.',
            ],
            [
                'nome'      => '9.4. Formação Docente – Registrar e compartilhar boas práticas pedagógicas',
                'descricao' => 'Implica documentar experiências e sistematizar aprendizagens, favorecendo a circulação de práticas e a construção de memória pedagógica.',
            ],

            // 10. Diversidade e Inclusão
            [
                'nome'      => '10.1. Diversidade e Inclusão – Reconhecer especificidades de povos e comunidades tradicionais',
                'descricao' => 'Refere-se à consideração das identidades culturais, modos de vida e saberes de quilombolas, indígenas, ribeirinhos e populações do campo na organização das práticas pedagógicas.',
            ],
            [
                'nome'      => '10.2. Diversidade e Inclusão – Garantir educação inclusiva e acessível para pessoas com deficiência',
                'descricao' => 'Envolve a adoção de materiais adaptados, recursos acessíveis e estratégias pedagógicas que assegurem participação plena nos processos educativos.',
            ],
            [
                'nome'      => '10.3. Diversidade e Inclusão – Trabalhar temas ligados a gênero, raça e direitos humanos',
                'descricao' => 'Consiste na incorporação de debates sobre desigualdades estruturais, discriminação e direitos fundamentais como parte do currículo da EJA.',
            ],
            [
                'nome'      => '10.4. Diversidade e Inclusão – Enfrentar discriminações e violências no ambiente escolar',
                'descricao' => 'Implica desenvolver práticas institucionais que reconheçam e enfrentem racismo, sexismo, transfobia e etarismo presentes nas relações escolares.',
            ],

            // 11. Busca Ativa e Permanência
            [
                'nome'      => '11.1. Busca Ativa e Permanência – Mapear a demanda por EJA com base em dados oficiais',
                'descricao' => 'Refere-se ao uso de informações do IBGE, CADÚNICO e Censo Escolar para identificar públicos não escolarizados e planejar estratégias de ingresso.',
            ],
            [
                'nome'      => '11.2. Busca Ativa e Permanência – Planejar campanhas de divulgação acessíveis',
                'descricao' => 'Consiste na elaboração de materiais e linguagens que dialoguem com a população jovem, adulta e idosa, ampliando a visibilidade da oferta.',
            ],
            [
                'nome'      => '11.3. Busca Ativa e Permanência – Realizar visitas domiciliares e comunitárias',
                'descricao' => 'Envolve estratégias presenciais de aproximação com territórios e famílias, reconhecendo barreiras de acesso e possibilidades de mobilização.',
            ],
            [
                'nome'      => '11.4. Busca Ativa e Permanência – Criar estratégias institucionais de acolhimento',
                'descricao' => 'Implica considerar transporte, alimentação, cuidado com filhos e vínculos pedagógicos como dimensões estruturantes da permanência.',
            ],

            // 12. Materiais Didáticos
            [
                'nome'      => '12.1. Materiais Didáticos – Produzir materiais pedagógicos contextualizados',
                'descricao' => 'Refere-se à elaboração de cadernos, jogos e murais baseados na realidade local, incorporando linguagens e referências do território.',
            ],
            [
                'nome'      => '12.2. Materiais Didáticos – Incentivar produção coletiva de textos com educandos',
                'descricao' => 'Envolve a construção compartilhada de livretos, jornais e histórias em quadrinhos, integrando autoria e aprendizagem.',
            ],
            [
                'nome'      => '12.3. Materiais Didáticos – Utilizar arte, música e oralidade como recursos pedagógicos',
                'descricao' => 'Consiste na incorporação de múltiplas linguagens expressivas ao processo formativo, ampliando as formas de aprendizagem.',
            ],

            // 13. Memória da EJA
            [
                'nome'      => '13.1. Memória da EJA – Documentar sistematicamente as ações da modalidade',
                'descricao' => 'Refere-se ao registro organizado de fotos, vídeos, relatórios e listas de presença como parte da preservação da trajetória institucional.',
            ],
            [
                'nome'      => '13.2. Memória da EJA – Utilizar o CREJA como espaço de memória e formação',
                'descricao' => 'Envolve consolidar o Centro de Referência como repositório de experiências, pesquisas e materiais formativos da EJA.',
            ],
            [
                'nome'      => '13.3. Memória da EJA – Registrar experiências pedagógicas e trajetórias',
                'descricao' => 'Consiste na sistematização de práticas e histórias de educandos e educadores como patrimônio pedagógico.',
            ],
            [
                'nome'      => '13.4. Memória da EJA – Produzir narrativas que valorizem a história da EJA',
                'descricao' => 'Implica a criação de materiais que expressem a identidade histórica e cultural da modalidade nos territórios.',
            ],

            // 14. Identidade do Educador Popular
            [
                'nome'      => '14.1. Identidade do Educador Popular – Reconhecer o professor da EJA como sujeito político',
                'descricao' => 'Refere-se à compreensão da docência na EJA como prática ética, social e transformadora, situada no campo dos direitos humanos.',
            ],
            [
                'nome'      => '14.2. Identidade do Educador Popular – Valorizar o educador como mediador do conhecimento',
                'descricao' => 'Envolve reconhecer o papel do professor como facilitador do diálogo e da construção coletiva do saber.',
            ],
            [
                'nome'      => '14.3. Identidade do Educador Popular – Estimular formação continuada baseada na prática reflexiva',
                'descricao' => 'Consiste na articulação entre ação pedagógica, reflexão crítica e estudo permanente como constitutivos da identidade profissional.',
            ],
        ];

        foreach ($matrizes as $item) {
            MatrizAprendizagem::create($item);
        }
    }
}
