<?php
/* =====================================================================
   aplicar-pacote.php — aplica uma atualização do catálogo (pacote do
   painel) a uma pasta compartilhado/, pela linha de comando.

   Serve para a cópia do PROJETO (compartilhado/dados/produtos.js e as
   fotos), para que os próximos site/ e site.zip já saiam com o catálogo
   novo. No servidor, quem aplica é o administrador, pelo painel.
   Usa as MESMAS funções do painel (painel/lib/pacotes.php): mesma
   conferência do pacote, mesmas regras de cada mudança.

   Uso (PHP 8.1+, com GD):
     php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado
     php ferramentas/aplicar-pacote.php <pasta-do-pacote> <pasta-compartilhado> --simular
     php ferramentas/aplicar-pacote.php <pasta-do-pacote> <pasta-compartilhado> --incluir-opcionais

   - aplica todas as mudanças que o painel deixaria MARCADAS; as que ele
     deixaria desmarcadas (ex.: produto antigo que sumiu, produto da capa)
     só com --incluir-opcionais;
   - copia as fotos do pacote para img/produtos/ e img/produtos-recorte/
     (nunca sobrescreve: foto igual já lá = nada a fazer; diferente = erro);
   - NÃO apaga as fotos dos produtos que saem ou são trocados (páginas do
     site podem usá-las direto) e não guarda cópia de segurança (o projeto
     tem o git);
   - --simular só mostra o que faria.
   ===================================================================== */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('PAINEL', true);

$args = array_values(array_slice($argv, 1));
$simular = in_array('--simular', $args, true);
$opcionais = in_array('--incluir-opcionais', $args, true);
$posicionais = array_values(array_filter($args, static fn($a) => !str_starts_with($a, '--')));
$desconhecidos = array_diff(array_filter($args, static fn($a) => str_starts_with($a, '--')), ['--simular', '--incluir-opcionais']);
if (count($posicionais) !== 2 || $desconhecidos) {
    fwrite(STDERR, "Uso: php ferramentas/aplicar-pacote.php <pasta-do-pacote> <pasta-compartilhado> [--simular] [--incluir-opcionais]\n");
    exit(2);
}
$pastaPacote = realpath($posicionais[0]);
$compartilhado = realpath($posicionais[1]);
if ($pastaPacote === false || !is_file($pastaPacote . '/pacote.json')) { fwrite(STDERR, "Não achei pacote.json em {$posicionais[0]}\n"); exit(2); }
if ($compartilhado === false || !is_file($compartilhado . '/dados/produtos.js')) { fwrite(STDERR, "Não achei dados/produtos.js em {$posicionais[1]}\n"); exit(2); }

// Pasta privada temporária (notas, travas): nada do painel de verdade é tocado
$tmp = sys_get_temp_dir() . '/aplicar-pacote-' . bin2hex(random_bytes(5));
if (!mkdir($tmp, 0700)) { fwrite(STDERR, "Não consegui criar a pasta temporária $tmp\n"); exit(1); }
$GLOBALS['PAINEL_CFG_CLI'] = ['compartilhado' => $compartilhado, 'pasta_privada' => $tmp, 'pasta_pacotes' => dirname($pastaPacote)];

$lib = dirname(__DIR__) . '/painel/lib';
require $lib . '/nucleo.php';
require $lib . '/catalogo.php';
require $lib . '/imagens.php';
require $lib . '/pacotes.php';
date_default_timezone_set((string) cfg('fuso'));
set_error_handler(static function (int $nivel, string $msg, string $arq = '', int $linha = 0): bool {
    if (!(error_reporting() & $nivel)) return false;   // chamada com @
    throw new ErrorException($msg, 0, $nivel, $arq, $linha);
});

function apagarPasta(string $dir): void
{
    if (!is_dir($dir) || is_link($dir)) return;
    foreach (scandir($dir) ?: [] as $n) {
        if ($n === '.' || $n === '..') continue;
        $c = $dir . '/' . $n;
        is_dir($c) && !is_link($c) ? apagarPasta($c) : @unlink($c);
    }
    @rmdir($dir);
}

$codigo = 0;
try {
    $id = basename($pastaPacote);
    $cat = lerCatalogo();
    $pac = lerPacote($id, $cat['categorias']);
    if ($pac['erro'] !== null) throw new ErroPacote('Pacote com problema: ' . $pac['erro']);
    echo 'Pacote ' . $pac['id'] . ' — ' . $pac['titulo'] . ' (' . count($pac['operacoes']) . " mudanças)\n";
    echo 'Catálogo: ' . $compartilhado . '/dados/produtos.js (' . count($cat['produtos']) . " produtos)\n";

    comTrava('catalogo', static function () use ($pac, $simular, $opcionais): void {
        $c = lerCatalogo();
        $estados = estadosDoPacote($pac, $c['produtos']);
        $indices = [];
        foreach ($estados as $i => $st) {
            $o = $pac['operacoes'][$i];
            $rotulo = sprintf('%2d. %-10s %s', $i + 1, $o['tipo'], $o['produto']['id'] ?? $o['alvo']);
            if ($st['estado'] === 'aplicavel' || ($st['estado'] === 'opcional' && $opcionais)) { $indices[] = $i; continue; }
            echo '  pula ' . $rotulo . ' — ' . ($st['situacao'] !== '' ? $st['situacao'] : $st['estado'])
                . ($st['estado'] === 'opcional' ? ' (use --incluir-opcionais para aplicar)' : '') . "\n";
        }
        if (!$indices) { echo "Nada a aplicar: o catálogo já está com todas as mudanças possíveis.\n"; return; }
        $copiadas = [];
        $r = executarOperacoes($pac, $c, $indices, [], $copiadas, !$simular);
        try {
            foreach ($r['feitas'] as $i) {
                $o = $pac['operacoes'][$i];
                $txt = match ($o['tipo']) {
                    'adicionar'  => 'cadastra ' . nomeCompleto($o['produto']) . ' (' . $o['produto']['id'] . ')',
                    'substituir' => 'troca ' . $o['alvo'] . ' por ' . nomeCompleto($o['produto']) . ' (' . $o['produto']['id'] . ')',
                    'foto'       => 'foto nova de ' . $o['alvo'] . ': ' . $o['imagem'],
                    'remover'    => 'tira ' . $o['alvo'] . ' do catálogo (as fotos dele ficam na pasta)',
                };
                echo '  ' . ($simular ? 'faria ' : 'ok   ') . sprintf('%2d. ', $i + 1) . $txt . "\n";
            }
            if ($simular) { echo 'SIMULAÇÃO: nada foi gravado (' . count($r['feitas']) . " mudanças seriam aplicadas).\n"; return; }
            gravarCatalogo($c['categorias'], $r['produtos']);
        } catch (Throwable $t) {
            foreach ($copiadas as [$tipo, $arq]) @unlink(pastaFotos($tipo) . '/' . $arq);   // o catálogo não foi gravado
            throw $t;
        }
        foreach ($copiadas as [$tipo, $arq]) echo '  foto ' . ($tipo === 'produtos' ? 'img/produtos/' : 'img/produtos-recorte/') . $arq . "\n";
        echo 'Pronto: ' . count($r['feitas']) . ' mudanças aplicadas (' . implode(', ', frasesContagem($r['n'], 'feito')) . '); '
            . count($copiadas) . ' fotos copiadas; catálogo com ' . count($r['produtos']) . " produtos.\n";
    });
} catch (ErroPacote | ErroCatalogo | ErroUsuario $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    $codigo = 1;
} catch (Throwable $t) {
    fwrite(STDERR, 'ERRO inesperado: ' . get_class($t) . ': ' . $t->getMessage() . ' (' . basename($t->getFile()) . ':' . $t->getLine() . ")\n");
    $codigo = 1;
} finally {
    apagarPasta($tmp);
}
exit($codigo);
