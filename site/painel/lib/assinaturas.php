<?php
/* =====================================================================
   Painel Balanças.com — assinaturas de e-mail
   Cada pessoa da equipe gera a própria assinatura (index.php?a=assinaturas;
   tela em lib/visoes-assinaturas.php, visual e código em assets/assinaturas.css
   e assets/assinaturas.js). Qualquer usuário logado (Administrador ou Editor).

   As imagens da assinatura NÃO ficam no painel: ficam numa pasta pública na
   raiz do site (PASTA_ASSINATURAS), porque quem as baixa é o programa de
   e-mail de quem recebe a mensagem, sem login. A pasta vem do projeto
   (hospedagem/<PASTA_ASSINATURAS>/) em todo site.zip e atualizacao.zip:
   ferramentas/montar-site.py lê o nome e a lista de arquivos DAQUI e para
   com erro se faltar algum.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// Endereço oficial do site, sem www (www e http redirecionam para ele no .htaccess da raiz). As imagens
// precisam responder nele direto, sem redirecionamento. É o mesmo DOMINIO de ferramentas/montar-site.py.
const SITE_OFICIAL = 'https://balancass.com';
// Pasta pública das imagens, na raiz do site. O nome é difícil de adivinhar, mas NÃO é segredo: aparece no
// código de todo e-mail enviado com a assinatura e no código deste projeto (num repositório público, qualquer
// um vê). NUNCA renomeie nem apague: as assinaturas já instaladas apontam para ela. Este é o ÚNICO lugar do
// código com o nome (o montar-site.py lê daqui).
const PASTA_ASSINATURAS = 'assinatura-knjkycy9nj726exg';
// As 10 imagens (PNG em tamanho dobrado, para ficarem nítidas em tela de celular). Não renomeie, não converta
// nem recomprima: os nomes e os tamanhos estão no código das assinaturas. "Testar agora" confere uma por uma.
const IMAGENS_ASSINATURA = ['plaqueta.png', 'ic-whatsapp.png', 'ic-tel.png', 'ic-cel.png', 'ic-email.png',
    'ic-site.png', 'ic-local.png', 'bt-whats.png', 'bt-facebook.png', 'bt-mapa.png'];

/** Endereço público das imagens, terminando em "/": é o que vai no código da assinatura copiada. */
function enderecoImagensAssinatura(): string
{
    return SITE_OFICIAL . '/' . PASTA_ASSINATURAS . '/';
}

/** As mesmas imagens vistas a partir do painel (no próprio servidor): a prévia usa este caminho. */
function caminhoLocalImagensAssinatura(): string
{
    return (string) cfg('url_site') . PASTA_ASSINATURAS . '/';
}

/**
 * Cargo de quem está logado, se houver alguém com o mesmo nome na equipe do site (área Empresa → Equipe;
 * sem ajuste, a equipe do empresa.js). Compara sem acento, sem maiúsculas e sem espaços sobrando; se não
 * achar o nome inteiro, aceita o primeiro e o último nome (“Rafael F. Tessaro” = “Rafael Tessaro”), desde
 * que só uma pessoa da equipe combine. Sem ninguém: '' (a pessoa digita o cargo).
 */
function cargoNaEquipe(string $nome): string
{
    try {
        $equipe = valoresAtuais(lerAjustes()['ajustes'], lerPadroesEmpresa()['dados'])['equipe'];
    } catch (Throwable $t) {
        return '';   // ajustes.js com problema: a área Empresa avisa; aqui o cargo só fica em branco
    }
    $chave = chaveDeNome($nome);
    if ($chave === '') return '';
    $pontas = static function (string $k): string {
        $p = explode(' ', $k);
        return count($p) > 1 ? $p[0] . ' ' . $p[count($p) - 1] : $k;
    };
    $parecidos = [];
    foreach ($equipe as $pessoa) {
        if (!is_array($pessoa) || !is_string($pessoa['nome'] ?? null) || !is_string($pessoa['cargo'] ?? null)) continue;
        $k = chaveDeNome($pessoa['nome']);
        if ($k === $chave) return limparLinha($pessoa['cargo']) ?? '';
        if ($pontas($k) === $pontas($chave)) $parecidos[] = limparLinha($pessoa['cargo']) ?? '';
    }
    return count($parecidos) === 1 ? $parecidos[0] : '';
}

/**
 * Content-Security-Policy só desta página (troca a do painel, que vem de cabecalhosSeguranca()).
 * Os outros cabeçalhos de segurança e de não guardar em cache continuam os do painel.
 * - style-src 'unsafe-inline': a prévia e a própria assinatura são HTML de e-mail, que só funciona com
 *   style="…" em cada elemento (os programas de e-mail ignoram folhas de estilo); a prévia é escrita
 *   dentro do quadro (iframe about:blank, que herda esta política). Scripts continuam só do painel.
 * - img-src SITE_OFICIAL: o botão "Testar agora" busca as imagens no endereço público, como o
 *   programa de e-mail de quem recebe.
 * - frame-src 'self': o quadro da prévia.
 */
function cabecalhoCspAssinaturas(): void
{
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
        . "img-src 'self' data: " . SITE_OFICIAL . "; frame-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
}

function acaoAssinaturas(): void
{
    $eu = usuarioAtual();
    cabecalhoCspAssinaturas();
    telaAssinaturas($eu, cargoNaEquipe((string) $eu['nome']));
}
