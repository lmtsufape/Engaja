@extends('cartas.auth._shell')

@section('title', 'Termos - Cartas para Esperançar')
@section('logoClass', 'cartas-logo--compact')

@section('auth-content')
    <div class="cartas-terms-box">
        <p><strong>1. Ciência sobre acesso aos conteúdos</strong></p>
        <p>Estou ciente de que as cartas, imagens, documentos e demais conteúdos inseridos nesta plataforma serão acessados exclusivamente pelas equipes responsáveis pela gestão da ação, incluindo profissionais autorizados do Projeto ALFA-EJA Brasil, do Instituto Paulo Freire, da Petrobras e da equipe técnica responsável pela administração e suporte da plataforma.</p>
        <p>Esse acesso ocorrerá para fins de organização, distribuição das correspondências, acompanhamento pedagógico, curadoria, monitoramento, documentação e avaliação da ação.</p>

        <p><strong>2. Autorização de uso da obra intelectual</strong></p>
        <p>Autorizo, de forma gratuita e sem exclusividade, a utilização total ou parcial dos conteúdos produzidos por mim no âmbito da ação Cartas para Esperançar, incluindo textos, cartas, desenhos, fotografias, ilustrações, poemas, relatos, depoimentos e demais produções autorais.</p>
        <p>A autorização compreende o uso para fins educativos, comunicacionais, institucionais, científicos, culturais e de memória do projeto, em materiais impressos e digitais.</p>

        <p><strong>3. Responsabilidade sobre o conteúdo enviado</strong></p>
        <p>Declaro que sou responsável pelas informações, imagens e produções que inserir na plataforma e que não incluirei conteúdos ofensivos, discriminatórios ou que violem direitos de terceiros.</p>

        <p><strong>4. Consentimento</strong></p>
        <p>Ao prosseguir, confirmo que li e estou de acordo com estes termos de participação.</p>
    </div>

    <form method="POST" action="{{ route('cartas.terms.accept') }}" class="cartas-form">
        @csrf
        <button type="submit" class="cartas-button">Li e estou de acordo com os termos</button>
    </form>
@endsection
