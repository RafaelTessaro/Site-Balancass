<?php
/* =====================================================================
   Painel Balanças.com — atualizações do catálogo ("pacotes")
   O catálogo do servidor (produtos.js e fotos) é editado no painel e
   nunca vem nas atualizações do site. Por isso, mudanças preparadas por
   quem cuida do site (produtos novos, modelos que saíram de linha
   trocados pelos atuais, fotos novas, produtos que saem do site) chegam
   como um PACOTE dentro do painel:
     painel/pacotes/<id>/pacote.json     ← lista de mudanças (só o PHP lê)
     painel/pacotes/<id>/fotos/*.webp    ← fotos com fundo branco
     painel/pacotes/<id>/recortes/*.webp ← as mesmas, com fundo transparente
   Um administrador revê cada mudança e aplica as marcadas de uma vez:
   UMA cópia de segurança antes (Cópias → Restaurar desfaz tudo, fotos
   inclusive). O que foi aplicado/dispensado fica em pacotes.php (privado).
   O pacote é conferido com as MESMAS regras do formulário do produto; com
   qualquer problema ele aparece como "Pacote com problema" e não aplica.
   Categoria/subcategoria que não existe mais no catálogo do servidor (o
   dono renomeou ou apagou em Categorias) trava só aquela mudança.
   Também usado pela linha de comando (ferramentas/aplicar-pacote.php).
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// Nome da pasta = id do pacote: AAAA-MM-texto (ex.: 2026-10-catalogo)
const RE_PACOTE = '/^[0-9]{4}-[0-9]{2}-[a-z0-9-]{1,40}$/';
const TIPOS_OPERACAO = ['adicionar', 'substituir', 'foto', 'remover'];
// Campos aceitos em cada mudança (qualquer outro = pacote com problema)
// "nome_antigo" (opcional): nome do produto antigo para a tela, quando ele já não está no catálogo
// (o dono excluiu, ou instalação do zero); "depois_de": um código ou uma lista em ordem de preferência
const CAMPOS_OPERACAO = [
    'adicionar'  => ['tipo', 'produto', 'depois_de', 'motivo'],
    'substituir' => ['tipo', 'alvo', 'nome_antigo', 'produto', 'motivo'],
    'foto'       => ['tipo', 'alvo', 'nome_antigo', 'imagem', 'recorte', 'antes', 'motivo'],
    'remover'    => ['tipo', 'alvo', 'nome_antigo', 'motivo'],
];
const CAMPOS_PACOTE = ['id', 'titulo', 'resumo', 'criado', 'operacoes'];
const CAMPOS_PRODUTO_PACOTE = ['id', 'nome', 'marca', 'categoria', 'subcategoria', 'resumo', 'descricao', 'especificacoes',
    'imagem', 'recorte', 'estoque', 'condicao', 'preco', 'destaque', 'visivel'];
const LIMITES_PACOTE = ['bytes' => 1048576, 'operacoes' => 200, 'titulo' => 120, 'resumo' => 400, 'motivo' => 300, 'foto_min' => 100,
    'nome_antigo' => 120, 'ancoras' => 5];
// Títulos dos grupos da tela de revisão, na ordem da tela
const GRUPOS_PACOTE = ['adicionar' => 'Produtos novos', 'substituir' => 'Trocados pelo modelo atual', 'foto' => 'Fotos novas', 'remover' => 'Saem do site'];

final class ErroPacote extends RuntimeException {}
final class PacoteSemMudancas extends RuntimeException {}

function pastaPacotes(): string
{
    // A linha de comando aponta para outra pasta (config 'pasta_pacotes'); no painel é painel/pacotes/
    return rtrim((string) (cfg('pasta_pacotes') ?? dirname(__DIR__) . '/pacotes'), '/');
}

/** Endereço (a partir de painel/index.php) de uma foto do pacote: arquivos estáticos em painel/pacotes/. */
function urlFotoPacote(string $id, string $arquivo, string $sub = 'fotos'): string
{
    return 'pacotes/' . rawurlencode($id) . '/' . ($sub === 'recortes' ? 'recortes' : 'fotos') . '/' . rawurlencode($arquivo);
}

/** Ids dos pacotes que estão no servidor (pastas com pacote.json), mais novos primeiro. */
function idsPacotes(): array
{
    $ids = [];
    foreach (glob(pastaPacotes() . '/*/pacote.json') ?: [] as $arq) {
        $pasta = dirname($arq);
        $id = basename($pasta);
        if (preg_match(RE_PACOTE, $id) && !is_link($pasta) && !is_link($arq)) $ids[] = $id;
    }
    rsort($ids, SORT_STRING);
    return $ids;
}

/* ------------------------------------------------------------------
   Leitura e conferência (nada do JSON é usado como caminho: só nomes
   simples que passam pelas expressões regulares do painel)
   ------------------------------------------------------------------ */
/**
 * Lê e confere o pacote $id contra as categorias do catálogo atual.
 * Devolve ['id', 'titulo', 'resumo', 'criado', 'operacoes' => [...], 'versao' => hash do JSON,
 * 'erro' => null | 'motivo do problema'] — com erro, 'operacoes' vem vazia.
 */
function lerPacote(string $id, array $categorias): array
{
    static $cache = [];
    $chave = $id . '|' . md5(serialize($categorias));
    if (isset($cache[$chave])) return $cache[$chave];
    $pac = ['id' => $id, 'titulo' => $id, 'resumo' => '', 'criado' => '', 'operacoes' => [], 'versao' => '', 'erro' => null,
        'categorias' => $categorias];   // as de agora: estadoOperacao confere em qual categoria/subcategoria cada produto entra
    try {
        $pac = conferirPacote($id, $categorias) + $pac;
    } catch (ErroPacote $e) {
        $pac['erro'] = $e->getMessage();
        // O título ajuda a reconhecer o pacote mesmo com problema
        $bruto = @json_decode((string) @file_get_contents(pastaPacotes() . '/' . $id . '/pacote.json', false, null, 0, LIMITES_PACOTE['bytes']), true);
        $t = is_array($bruto) ? limparLinha($bruto['titulo'] ?? '') : null;
        if (is_string($t) && $t !== '') $pac['titulo'] = cortar($t, LIMITES_PACOTE['titulo']);
    }
    return $cache[$chave] = $pac;
}

function conferirPacote(string $id, array $categorias): array
{
    if (!preg_match(RE_PACOTE, $id)) throw new ErroPacote('o nome da pasta não segue o padrão AAAA-MM-nome');
    $pasta = pastaPacotes() . '/' . $id;
    $arq = $pasta . '/pacote.json';
    if (!@is_file($arq) || is_link($arq) || is_link($pasta)) throw new ErroPacote('falta o arquivo pacote.json');
    $tam = (int) @filesize($arq);
    if ($tam <= 0 || $tam > LIMITES_PACOTE['bytes']) throw new ErroPacote('o pacote.json está vazio ou é grande demais');
    $src = @file_get_contents($arq);
    if (!is_string($src)) throw new ErroPacote('não consegui ler o pacote.json');
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src) ?? $src;
    try {
        $d = json_decode($src, true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        throw new ErroPacote('o pacote.json não é um JSON válido (' . $ex->getMessage() . ')');
    }
    if (!is_array($d) || array_is_list($d)) throw new ErroPacote('o pacote.json precisa ser um objeto { … }');
    foreach (array_keys($d) as $k) {
        if (!in_array($k, CAMPOS_PACOTE, true)) throw new ErroPacote('campo desconhecido no pacote: "' . $k . '"');
    }
    if (($d['id'] ?? null) !== $id) throw new ErroPacote('o "id" do pacote.json precisa ser igual ao nome da pasta (' . $id . ')');
    $titulo = limparLinha($d['titulo'] ?? null);
    if (!is_string($d['titulo'] ?? null) || $titulo === null || $titulo === '' || tamanho($titulo) > LIMITES_PACOTE['titulo']) {
        throw new ErroPacote('"titulo" precisa ser um texto de até ' . LIMITES_PACOTE['titulo'] . ' caracteres');
    }
    $resumo = limparLinha($d['resumo'] ?? '');
    if (!is_string($d['resumo'] ?? '') || $resumo === null || tamanho($resumo) > LIMITES_PACOTE['resumo']) {
        throw new ErroPacote('"resumo" precisa ser um texto de até ' . LIMITES_PACOTE['resumo'] . ' caracteres');
    }
    $criado = $d['criado'] ?? null;
    $dt = is_string($criado) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $criado) ? DateTime::createFromFormat('!Y-m-d', $criado) : false;
    if (!$dt || $dt->format('Y-m-d') !== $criado) throw new ErroPacote('"criado" precisa ser uma data AAAA-MM-DD');
    $ops = $d['operacoes'] ?? null;
    if (!is_array($ops) || !array_is_list($ops) || !$ops) throw new ErroPacote('"operacoes" precisa ser uma lista com pelo menos uma mudança');
    if (count($ops) > LIMITES_PACOTE['operacoes']) throw new ErroPacote('mudanças demais (no máximo ' . LIMITES_PACOTE['operacoes'] . ')');

    $erros = [];
    $operacoes = [];
    $usados = [];   // códigos citados (cada produto aparece numa mudança só)
    $fotos = [];    // cada foto do pacote é de uma mudança só
    foreach ($ops as $i => $op) {
        $n = $i + 1;
        try {
            $o = conferirOperacao($op, $categorias, $pasta);
        } catch (ErroPacote $e) {
            $erros[] = 'mudança ' . $n . ': ' . $e->getMessage();
            continue;
        }
        $citados = array_filter([$o['alvo'] ?? null, $o['produto']['id'] ?? null]);
        foreach ($citados as $c) {
            if (isset($usados[$c])) $erros[] = 'mudança ' . $n . ': o produto “' . $c . '” já aparece na mudança ' . $usados[$c];
            else $usados[$c] = $n;
        }
        foreach (fotosDaOperacao($o) as [$tipo, $arqFoto]) {
            if (isset($fotos[$tipo . '/' . $arqFoto])) $erros[] = 'mudança ' . $n . ': a foto ' . $arqFoto . ' já é usada na mudança ' . $fotos[$tipo . '/' . $arqFoto];
            else $fotos[$tipo . '/' . $arqFoto] = $n;
        }
        $operacoes[] = $o;
    }
    if ($erros) {
        $mais = count($erros) > 3 ? ' (e mais ' . (count($erros) - 3) . ')' : '';
        throw new ErroPacote(implode('; ', array_slice($erros, 0, 3)) . $mais);
    }
    return ['id' => $id, 'titulo' => $titulo, 'resumo' => $resumo, 'criado' => $criado, 'operacoes' => $operacoes,
        'versao' => substr(hash('sha256', $src), 0, 16), 'erro' => null];
}

/** Uma mudança do pacote, conferida e normalizada (lança ErroPacote com o motivo). */
function conferirOperacao($op, array $categorias, string $pasta): array
{
    if (!is_array($op) || array_is_list($op)) throw new ErroPacote('cada mudança precisa ser um objeto { … }');
    $tipo = $op['tipo'] ?? null;
    if (!is_string($tipo) || !in_array($tipo, TIPOS_OPERACAO, true)) throw new ErroPacote('"tipo" precisa ser adicionar, substituir, foto ou remover');
    foreach (array_keys($op) as $k) {
        if (!in_array($k, CAMPOS_OPERACAO[$tipo], true)) throw new ErroPacote('campo desconhecido "' . $k . '" numa mudança do tipo ' . $tipo);
    }
    $motivo = limparLinha($op['motivo'] ?? null);
    if (!is_string($op['motivo'] ?? null) || $motivo === null || $motivo === '' || tamanho($motivo) > LIMITES_PACOTE['motivo']) {
        throw new ErroPacote('"motivo" precisa ser um texto de até ' . LIMITES_PACOTE['motivo'] . ' caracteres');
    }
    $o = ['tipo' => $tipo, 'motivo' => $motivo];
    if ($tipo !== 'adicionar') {
        $o['alvo'] = codigoDoPacote($op['alvo'] ?? null, '"alvo"');
        $nomeAntigo = limparLinha($op['nome_antigo'] ?? '');
        if (!is_string($op['nome_antigo'] ?? '') || $nomeAntigo === null || tamanho($nomeAntigo) > LIMITES_PACOTE['nome_antigo']) {
            throw new ErroPacote('"nome_antigo" precisa ser um texto de até ' . LIMITES_PACOTE['nome_antigo'] . ' caracteres');
        }
        $o['nome_antigo'] = $nomeAntigo;
    }
    if ($tipo === 'adicionar' || $tipo === 'substituir') {
        [$p, $erros] = produtoDoPacote($op['produto'] ?? null, $categorias, $pasta);
        if ($erros) throw new ErroPacote('produto “' . (is_string($op['produto']['id'] ?? null) ? $op['produto']['id'] : '?') . '”: ' . implode(' ', array_slice($erros, 0, 3)));
        $o['produto'] = $p;
        if ($tipo === 'substituir' && $p['id'] === $o['alvo']) throw new ErroPacote('o produto novo precisa ter um código diferente do antigo');
    }
    if ($tipo === 'adicionar') {
        // Um código ou uma lista em ordem de preferência: vale o primeiro que estiver no catálogo (assim a
        // ordem final não depende de quais mudanças são aplicadas juntas)
        $ancoras = $op['depois_de'] ?? [];
        if (is_string($ancoras)) $ancoras = [$ancoras];
        if (!is_array($ancoras) || !array_is_list($ancoras) || count($ancoras) > LIMITES_PACOTE['ancoras']) {
            throw new ErroPacote('"depois_de" precisa ser o código de um produto ou uma lista de até ' . LIMITES_PACOTE['ancoras'] . ' códigos');
        }
        $o['depois_de'] = array_values(array_unique(array_map(static fn($a) => codigoDoPacote($a, '"depois_de"'), $ancoras)));
    }
    if ($tipo === 'foto') {
        $erro = erroFotoDoPacote($pasta, 'fotos', $op['imagem'] ?? null, $o['alvo'], true);
        if ($erro === null) $erro = erroFotoDoPacote($pasta, 'recortes', $op['recorte'] ?? null, $o['alvo'], false);
        if ($erro !== null) throw new ErroPacote($erro);
        $o['imagem'] = $op['imagem'];
        $o['recorte'] = (string) ($op['recorte'] ?? '');
        $antes = $op['antes'] ?? '';
        if (!is_string($antes) || ($antes !== '' && !preg_match(RE_ARQUIVO_FOTO, $antes))) throw new ErroPacote('"antes" precisa ser o nome de uma foto (ex.: prix5.webp)');
        $o['antes'] = $antes;
    }
    return $o;
}

function codigoDoPacote($v, string $campo): string
{
    if (!is_string($v) || strlen($v) > LIMITES['id'] || !preg_match(RE_ID, $v)) throw new ErroPacote($campo . ' precisa ser o código de um produto (ex.: toledo-prix-7e)');
    return $v;
}

/**
 * Produto de um pacote, conferido com as MESMAS regras do formulário do produto
 * (produtoDoFormulario: limites, categoria/subcategoria do catálogo atual, estoque,
 * condição, ficha técnica) + fotos WebP de verdade dentro da pasta do pacote.
 * Devolve [produto | null, erros]. Estoque, condição e preço vazios = consulte, novo, "".
 */
function produtoDoPacote($d, array $categorias, string $pasta): array
{
    if (!is_array($d) || array_is_list($d)) return [null, ['o "produto" precisa ser um objeto { … }.']];
    $erros = [];
    foreach (array_keys($d) as $k) if (!in_array($k, CAMPOS_PRODUTO_PACOTE, true)) $erros[] = 'Campo desconhecido "' . $k . '".';
    foreach (['nome', 'marca', 'categoria', 'subcategoria', 'resumo', 'descricao', 'imagem', 'recorte', 'estoque', 'condicao', 'preco'] as $k) {
        if (array_key_exists($k, $d) && !is_string($d[$k])) $erros[] = '"' . $k . '" precisa ser texto.';
    }
    foreach (['destaque', 'visivel'] as $k) if (array_key_exists($k, $d) && !is_bool($d[$k])) $erros[] = '"' . $k . '" precisa ser true ou false.';
    $id = $d['id'] ?? null;
    if (!is_string($id) || strlen($id) > LIMITES['id'] || !preg_match(RE_ID, $id)) $erros[] = 'Código (id): só letras minúsculas sem acento, números e hífen.';
    $specs = $d['especificacoes'] ?? null;
    if (!is_array($specs) || !array_is_list($specs) || !$specs) {
        $erros[] = 'Ficha técnica: "especificacoes" precisa ser uma lista [["Nome", "Valor"], …].';
    } else {
        foreach ($specs as $n => $s) {
            if (!is_array($s) || !array_is_list($s) || count($s) !== 2 || !is_string($s[0]) || !is_string($s[1]) || trim($s[0]) === '' || trim($s[1]) === '') {
                $erros[] = 'Ficha técnica: a linha ' . ($n + 1) . ' precisa ser ["Nome", "Valor"], os dois preenchidos.';
                break;
            }
        }
    }
    if ($erros) return [null, $erros];

    $f = [
        'id' => $id, 'nome' => $d['nome'] ?? '', 'marca' => $d['marca'] ?? '', 'categoria' => $d['categoria'] ?? '',
        'subcategoria' => $d['subcategoria'] ?? '', 'resumo' => $d['resumo'] ?? '', 'descricao' => $d['descricao'] ?? '',
        'preco' => $d['preco'] ?? '', 'estoque' => ($d['estoque'] ?? '') !== '' ? $d['estoque'] : 'consulte',
        'condicao' => ($d['condicao'] ?? '') !== '' ? $d['condicao'] : 'novo',
        'spec_nome' => array_column($specs, 0), 'spec_valor' => array_column($specs, 1),
        'destaque' => !empty($d['destaque']) ? '1' : '', 'visivel' => ($d['visivel'] ?? true) ? '1' : '',
    ];
    // Categoria e subcategoria: aqui só o formato (código e nome válidos). Se elas existem no catálogo
    // de AGORA é situação de cada mudança (estadoOperacao → destinoDoProdutoDoPacote): o dono pode ter
    // renomeado ou apagado uma subcategoria (tela Categorias) e o resto do pacote continua aplicável.
    $catPac = is_string($d['categoria'] ?? null) ? $d['categoria'] : '';
    $subPac = limparLinha($d['subcategoria'] ?? '') ?? '';
    $catsForm = $categorias;
    if (strlen($catPac) <= LIMITES['id'] && preg_match(RE_ID, $catPac) && $subPac !== '' && tamanho($subPac) <= LIMITES_CATEGORIA['subcategoria']) {
        $catsForm = [['id' => $catPac, 'nome' => $catPac, 'subcategorias' => [$subPac]]];
    }
    // Sem outros produtos: código repetido é situação ("Já está no catálogo"), não erro do pacote
    [$p, $errosForm] = produtoDoFormulario($f, $catsForm, [], null);
    foreach ($errosForm as $m) $erros[] = $m;
    if (($p['id'] ?? '') !== $id) $erros[] = 'Código (id) inválido.';

    $e1 = erroFotoDoPacote($pasta, 'fotos', $d['imagem'] ?? null, $id, true);
    $e2 = erroFotoDoPacote($pasta, 'recortes', $d['recorte'] ?? null, $id, false);
    foreach ([$e1, $e2] as $x) if ($x !== null) $erros[] = ucfirst($x) . '.';
    if ($erros) return [null, $erros];

    $p['imagem'] = $d['imagem'];
    if (($d['recorte'] ?? '') !== '') $p['recorte'] = $d['recorte'];
    unset($p['nota']);
    if (($p['visivel'] ?? true) === true) unset($p['visivel']);   // no arquivo, "visivel" só aparece quando é false
    return [ordenarCampos($p), []];
}

/**
 * Confere uma foto do pacote: nome no padrão das fotos do painel (<código>-<10 hex>.webp),
 * arquivo comum dentro de fotos/ ou recortes/ da pasta do pacote, WebP de verdade, até 900 px.
 * Devolve o problema (texto) ou null.
 */
function erroFotoDoPacote(string $pasta, string $sub, $arquivo, string $id, bool $obrigatoria): ?string
{
    $campo = $sub === 'fotos' ? '"imagem"' : '"recorte"';
    if ($arquivo === null || $arquivo === '') return $obrigatoria ? 'falta a foto (' . $campo . ')' : null;
    if (!is_string($arquivo) || basename($arquivo) !== $arquivo || !preg_match(RE_FOTO_DO_PAINEL, $arquivo) || !str_ends_with($arquivo, '.webp')) {
        return $campo . ' fora do padrão das fotos do painel (<código>-<10 letras de 0 a f>.webp)';
    }
    $prefixo = rtrim(substr($id, 0, 50), '-') . '-';   // o mesmo nome que o painel dá às fotos enviadas
    if (!str_starts_with($arquivo, $prefixo) || !preg_match('/^[0-9a-f]{10}\.webp$/', substr($arquivo, strlen($prefixo)))) {
        return 'a foto ' . $arquivo . ' não é de “' . $id . '” (o nome precisa começar com ' . $prefixo . ')';
    }
    $caminho = $pasta . '/' . $sub . '/' . $arquivo;
    if (!@is_file($caminho) || is_link($caminho) || is_link($pasta . '/' . $sub)) return 'a foto ' . $sub . '/' . $arquivo . ' não está na pasta do pacote';
    $tam = (int) @filesize($caminho);
    if ($tam <= 0 || $tam > (int) cfg('foto_max_bytes')) return 'a foto ' . $sub . '/' . $arquivo . ' está vazia ou é grande demais';
    $info = @getimagesize($caminho);
    if (!is_array($info) || ($info[2] ?? null) !== IMAGETYPE_WEBP || ($info['mime'] ?? '') !== 'image/webp') {
        return 'a foto ' . $sub . '/' . $arquivo . ' não é uma imagem WebP de verdade';
    }
    $max = (int) cfg('foto_max_lado');
    if ($info[0] > $max || $info[1] > $max) return 'a foto ' . $sub . '/' . $arquivo . ' passa de ' . $max . ' px (' . $info[0] . ' × ' . $info[1] . ')';
    if ($info[0] < LIMITES_PACOTE['foto_min'] || $info[1] < LIMITES_PACOTE['foto_min']) return 'a foto ' . $sub . '/' . $arquivo . ' é pequena demais (' . $info[0] . ' × ' . $info[1] . ')';
    return null;
}

/**
 * Identidade da mudança, que não depende da posição dela na lista: o registro (pacotes.php) guarda
 * isto. Assim um pacote reenviado com uma mudança a mais ou em outra ordem não faz uma mudança nova
 * "herdar" o "aplicada" de outra.
 */
function chaveOperacao(array $o): string
{
    return match ($o['tipo']) {
        'adicionar'  => 'adicionar:' . $o['produto']['id'],
        'substituir' => 'substituir:' . $o['alvo'] . '>' . $o['produto']['id'],
        'foto'       => 'foto:' . $o['alvo'] . ':' . $o['imagem'],
        'remover'    => 'remover:' . $o['alvo'],
    };
}

/** Rótulo da cópia de segurança de antes de aplicar (aspas simples no título: sem ”” no fim). */
function rotuloCopiaPacote(string $titulo): string
{
    return 'Antes de: aplicar a atualização ‘' . $titulo . '’';
}

/** Fotos que a mudança traz: [[tipo da pasta do site ('produtos'|'recortes'), arquivo], …] */
function fotosDaOperacao(array $o): array
{
    $out = [];
    $img = $o['tipo'] === 'foto' ? ($o['imagem'] ?? '') : ($o['produto']['imagem'] ?? '');
    $rec = $o['tipo'] === 'foto' ? ($o['recorte'] ?? '') : ($o['produto']['recorte'] ?? '');
    if ($img !== '') $out[] = ['produtos', $img];
    if ($rec !== '') $out[] = ['recortes', $rec];
    return $out;
}

/* ------------------------------------------------------------------
   Registro privado (pacotes.php), com cada mudança pela sua chave (chaveOperacao), nunca pela posição:
   { "<id>": { "aplicado": ts, "por": login, "chaves": [chaves], "dispensado": ts|null,
               "historico": [ { "quando": ts, "por": login, "chaves": [...], "copia": nome }, … ] } }
   ------------------------------------------------------------------ */
function registroPacotes(): array
{
    $d = lerJsonPrivado('pacotes.php', []);
    return is_array($d) ? $d : [];
}

function registroDoPacote(string $id): array
{
    $r = registroPacotes()[$id] ?? [];
    if (!is_array($r)) $r = [];
    $chaves = array_values(array_unique(array_filter(is_array($r['chaves'] ?? null) ? $r['chaves'] : [], 'is_string')));
    sort($chaves);
    return [
        'aplicado' => (int) ($r['aplicado'] ?? 0) ?: null,
        'por' => is_string($r['por'] ?? null) ? $r['por'] : '',
        'chaves' => $chaves,
        'dispensado' => (int) ($r['dispensado'] ?? 0) ?: null,
        'historico' => array_values(array_filter(is_array($r['historico'] ?? null) ? $r['historico'] : [], 'is_array')),
    ];
}

/** Produtos como estavam antes de cada aplicação (das cópias de segurança): nome e ficha de quem já saiu do catálogo. */
function produtosAntesDoPacote(array $reg): array
{
    $out = [];
    foreach (array_slice(array_reverse($reg['historico']), 0, 5) as $h) {
        $nome = basename((string) ($h['copia'] ?? ''));
        if (!preg_match(RE_COPIA, $nome)) continue;
        try { $d = lerJsonPrivado('copias/' . $nome); } catch (Throwable $t) { continue; }   // sem a cópia, mostra só o código
        foreach (is_array($d['produtos'] ?? null) ? $d['produtos'] : [] as $p) {
            if (is_array($p) && is_string($p['id'] ?? null) && !isset($out[$p['id']])) $out[$p['id']] = $p;
        }
    }
    return $out;
}

/** Quando (e por quem) a mudança de chave $chave foi aplicada pela última vez, ou null. */
function aplicacaoDaOperacao(array $reg, string $chave): ?array
{
    if (!in_array($chave, $reg['chaves'], true)) return null;
    foreach (array_reverse($reg['historico']) as $h) {
        if (in_array($chave, (array) ($h['chaves'] ?? []), true)) {
            return ['quando' => (int) ($h['quando'] ?? 0), 'por' => (string) ($h['por'] ?? ''), 'copia' => (string) ($h['copia'] ?? '')];
        }
    }
    return ['quando' => (int) ($reg['aplicado'] ?? 0), 'por' => $reg['por'], 'copia' => ''];
}

function marcarPacoteAplicado(string $id, array $chaves, string $copia): void
{
    comTrava('pacotes', static function () use ($id, $chaves, $copia): void {
        $d = registroPacotes();
        $r = registroDoPacote($id);
        $todas = array_values(array_unique(array_merge($r['chaves'], $chaves)));
        sort($todas);
        $agora = time();
        $quem = usuarioLogado() ?? '-';
        $hist = $r['historico'];
        $hist[] = ['quando' => $agora, 'por' => $quem, 'chaves' => array_values($chaves), 'copia' => $copia];
        $d[$id] = ['aplicado' => $agora, 'por' => $quem, 'chaves' => $todas, 'dispensado' => $r['dispensado'], 'historico' => array_slice($hist, -50)];
        gravarJsonPrivado('pacotes.php', $d);
    });
}

/**
 * As aplicações deste pacote diante do catálogo de agora (para o quadro "Aplicado em…" da tela).
 * Uma aplicação ainda "vale" quando alguma mudança aplicada nela (e não reaplicada depois) está no
 * catálogo. A cópia que desfaz tudo de uma vez é a de antes da MAIS ANTIGA que ainda vale; quando
 * nenhuma vale mais, a atualização foi desfeita (cópia restaurada ou mudanças no painel).
 * Devolve ['valendo' => [aplicações, da mais antiga à mais nova], 'primeira' => aplicação|null,
 * 'copiaExiste' => bool, 'desfeita' => bool, 'restaurada' => ts|null].
 */
function situacaoDasAplicacoes(array $pac, array $estados, array $reg): array
{
    $out = ['valendo' => [], 'primeira' => null, 'copiaExiste' => false, 'desfeita' => false, 'restaurada' => null];
    if (!$reg['aplicado']) return $out;
    $feita = [];
    foreach ($pac['operacoes'] as $i => $o) $feita[chaveOperacao($o)] = ($estados[$i]['estado'] ?? '') === 'feito';
    // Cada mudança conta só na ÚLTIMA aplicação que a trouxe (aplicada, desfeita e aplicada de novo: vale a nova)
    $dono = [];
    foreach ($reg['historico'] as $k => $h) foreach ((array) ($h['chaves'] ?? []) as $c) if (is_string($c)) $dono[$c] = $k;
    $vale = [];
    foreach ($dono as $c => $k) if (!empty($feita[$c])) $vale[$k] = true;
    foreach ($reg['historico'] as $k => $h) if (isset($vale[$k])) $out['valendo'][] = $h;
    if ($out['valendo']) {
        $out['primeira'] = $out['valendo'][0];
        $nome = basename((string) ($out['primeira']['copia'] ?? ''));
        $out['copiaExiste'] = (bool) preg_match(RE_COPIA, $nome) && @is_file(pastaPrivada() . '/copias/' . $nome);
        return $out;
    }
    $out['desfeita'] = true;
    // Quando: a primeira restauração de cópia feita depois da última aplicação (se houver)
    $desde = (int) $reg['aplicado'];
    foreach (array_reverse(arquivosCopias()) as $arq) {
        $info = infoNomeCopia(basename($arq));
        if ($info['tipo'] === 'restaurar' && $info['criado'] >= $desde) { $out['restaurada'] = $info['criado']; break; }
    }
    return $out;
}

/** "Agora não" (o aviso some da lista de produtos) ou o contrário. Devolve se mudou algo. */
function dispensarPacote(string $id, bool $dispensar): bool
{
    return comTrava('pacotes', static function () use ($id, $dispensar): bool {
        $d = registroPacotes();
        $r = registroDoPacote($id);
        if (($r['dispensado'] !== null) === $dispensar) return false;
        $r['dispensado'] = $dispensar ? time() : null;
        $d[$id] = $r;
        gravarJsonPrivado('pacotes.php', $d);
        return true;
    });
}

/* ------------------------------------------------------------------
   Situação de cada mudança diante do catálogo de agora
   estado: aplicavel (marcada) · opcional (pode aplicar, desmarcada) ·
           feito (já está assim) · impossivel (não dá para aplicar)
   ------------------------------------------------------------------ */
function estadoOperacao(array $pac, int $i, array $produtos, array $reg = []): array
{
    $o = $pac['operacoes'][$i];
    $st = ['estado' => 'aplicavel', 'situacao' => '', 'aviso' => '', 'atual' => null, 'novo' => null, 'aplicada' => null];
    $idx = isset($o['alvo']) ? indicePorId($produtos, $o['alvo']) : null;
    if ($idx !== null) $st['atual'] = $produtos[$idx];
    // O produto novo, se já está no catálogo (para o "Ver no site" só aparecer quando ele está no site)
    $kNovo = isset($o['produto']) ? indicePorId($produtos, $o['produto']['id']) : null;
    if ($kNovo !== null) $st['novo'] = $produtos[$kNovo];
    switch ($o['tipo']) {
        case 'adicionar':
            if (indicePorId($produtos, $o['produto']['id']) !== null) $st = ['estado' => 'feito', 'situacao' => 'Já está no catálogo.'] + $st;
            break;
        case 'substituir':
            if (indicePorId($produtos, $o['produto']['id']) !== null) {
                $st = ['estado' => 'feito', 'situacao' => 'Já está no catálogo.'] + $st;
                if ($idx !== null) $st['aviso'] = 'O modelo antigo (' . nomeCompleto($produtos[$idx]) . ') também continua no catálogo. Se ele saiu de linha, exclua-o na lista de produtos.';
            } elseif ($idx === null) {
                $st = ['estado' => 'opcional', 'situacao' => 'O produto antigo não está mais no catálogo. Marque se quiser cadastrar o novo mesmo assim (ele entra no fim da categoria).'] + $st;
            } else {
                // O novo entra como "Consulte" e sem preço, e o antigo sai do site: quem tem o antigo
                // em estoque (Pronta entrega) decide quando trocar
                $velho = $produtos[$idx];
                $preco = trim((string) ($velho['preco'] ?? ''));
                $pronta = ($velho['estoque'] ?? '') === 'disponivel';
                if ($pronta || $preco !== '') {
                    $hoje = array_merge($pronta ? [ESTOQUES['disponivel']] : [], $preco !== '' ? [$preco] : []);
                    $st['aviso'] = 'Hoje: ' . implode(' · ', $hoje) . '. O modelo novo entra como “Consulte”, sem preço; o antigo sai do site.';
                }
                if ($pronta) {
                    $st = ['estado' => 'opcional', 'situacao' => 'Desmarcada porque o modelo antigo está como Pronta entrega. Se ainda tem unidades dele para vender, deixe para depois; marque quando quiser trocar.'] + $st;
                }
            }
            break;
        case 'foto':
            if ($idx === null) $st = ['estado' => 'impossivel', 'situacao' => 'O produto não está mais no catálogo.'] + $st;
            elseif (($produtos[$idx]['imagem'] ?? '') === $o['imagem']) $st = ['estado' => 'feito', 'situacao' => 'Já está com esta foto.'] + $st;
            elseif ($o['antes'] !== '' && ($produtos[$idx]['imagem'] ?? '') !== $o['antes']) {
                $st = ['estado' => 'opcional', 'situacao' => 'A foto já foi trocada no painel. Marque só se quiser trocar pela foto do pacote.'] + $st;
            }
            break;
        case 'remover':
            if ($idx === null) $st = ['estado' => 'feito', 'situacao' => 'Já não está no catálogo.'] + $st;
            elseif ($o['alvo'] === produtoCapa()) {
                $st = ['estado' => 'opcional', 'situacao' => 'Desmarcada porque é o produto da capa do site.',
                    'aviso' => 'É o produto da capa do site. ' . (function_exists('fraseCapaFora') ? fraseCapaFora() : 'A foto da capa continua lá, mas o link dela vai abrir o catálogo com o aviso “produto fora do catálogo”.')] + $st;
            }
            break;
    }
    // Já aplicada por este pacote antes
    $ap = $reg ? aplicacaoDaOperacao($reg, chaveOperacao($o)) : null;
    if ($ap !== null) {
        $st['aplicada'] = $ap;
        if ($st['estado'] === 'aplicavel' || $st['estado'] === 'opcional') {
            // Foi aplicada e depois desfeita (cópia restaurada, ou mudada à mão no painel)
            $st['estado'] = 'opcional';
            $st['situacao'] = 'Foi aplicada em ' . quando($ap['quando'], false) . ' e depois desfeita (cópia de segurança restaurada ou mudança no painel). Marque para aplicar de novo.';
        }
    }
    // Categoria e subcategoria do produto novo diante do catálogo de AGORA (tela Categorias)
    if (($st['estado'] === 'aplicavel' || $st['estado'] === 'opcional') && isset($o['produto']) && is_array($pac['categorias'] ?? null)) {
        $dest = destinoDoProdutoDoPacote($o, $pac['categorias'], $idx !== null ? $produtos[$idx] : null);
        if ($dest['erro'] !== null) {
            $st['estado'] = 'impossivel';
            $st['situacao'] = $dest['erro'];
        } elseif ($dest['sub'] !== null) {
            $st['subcategoria'] = $dest['sub'];
            $st['aviso'] = trim($st['aviso'] . ' ' . $dest['aviso']);
        }
    }
    // Foto do pacote com o mesmo nome de uma foto DIFERENTE que já está no site: nunca sobrescreve
    if ($st['estado'] === 'aplicavel' || $st['estado'] === 'opcional') {
        foreach (fotosDaOperacao($o) as [$tipo, $arq]) {
            $destino = pastaFotos($tipo) . '/' . $arq;
            $origem = pastaPacotes() . '/' . $pac['id'] . '/' . ($tipo === 'produtos' ? 'fotos' : 'recortes') . '/' . $arq;
            if (@is_file($destino) && !fotosIguais($destino, $origem)) {
                $st['estado'] = 'impossivel';
                $st['situacao'] = 'Já existe no site uma foto diferente com o nome ' . $arq . '. Avise quem cuida do site.';
                break;
            }
        }
    }
    $st['marcado'] = $st['estado'] === 'aplicavel';
    return $st;
}

/**
 * Onde o produto novo de uma mudança entra no catálogo de agora: ['erro' => motivo|null, 'sub' => subcategoria
 * trocada|null, 'aviso' => texto]. Categoria apagada ou subcategoria que não existe mais: não dá para aplicar
 * (com a explicação). Exceção: na troca de modelo, se a subcategoria do pacote não existe mais (foi renomeada),
 * o novo entra na subcategoria do modelo antigo, quando o antigo está na mesma categoria.
 */
function destinoDoProdutoDoPacote(array $o, array $categorias, ?array $antigo): array
{
    $p = $o['produto'];
    $c = categoriaPorId($categorias, (string) ($p['categoria'] ?? ''));
    if (!$c) {
        return ['erro' => 'A categoria deste produto (“' . $p['categoria'] . '”) não existe mais no catálogo (foi apagada em Categorias). Avise quem cuida do site.', 'sub' => null, 'aviso' => ''];
    }
    $sub = (string) ($p['subcategoria'] ?? '');
    if (in_array($sub, $c['subcategorias'], true)) return ['erro' => null, 'sub' => null, 'aviso' => ''];
    $subAntigo = (string) ($antigo['subcategoria'] ?? '');
    if ($o['tipo'] === 'substituir' && $antigo !== null && ($antigo['categoria'] ?? '') === $c['id'] && in_array($subAntigo, $c['subcategorias'], true)) {
        return ['erro' => null, 'sub' => $subAntigo,
            'aviso' => 'Entra em “' . $subAntigo . '”, a subcategoria do modelo antigo (a “' . $sub . '” do pacote não existe mais em ' . $c['nome'] . ').'];
    }
    return ['erro' => 'A subcategoria “' . $sub . '” não existe mais em ' . $c['nome'] . ' (foi renomeada ou apagada em Categorias). Para aplicar esta mudança, crie de novo uma subcategoria com esse nome, ou avise quem cuida do site.',
        'sub' => null, 'aviso' => ''];
}

function fotosIguais(string $a, string $b): bool
{
    $ha = @hash_file('sha256', $a);
    $hb = @hash_file('sha256', $b);
    return is_string($ha) && is_string($hb) && hash_equals($ha, $hb);
}

function estadosDoPacote(array $pac, array $produtos, array $reg = []): array
{
    $out = [];
    foreach (array_keys($pac['operacoes']) as $i) $out[$i] = estadoOperacao($pac, $i, $produtos, $reg);
    return $out;
}

/**
 * Mudanças que ainda podem ser aplicadas e nunca foram (as que fazem o aviso aparecer), por tipo.
 * As que a tela deixa desmarcadas (opcional) só contam enquanto a atualização nunca foi aplicada:
 * depois que o dono passou pela tela e aplicou, deixar uma delas desmarcada foi escolha dele.
 */
function pendentesDoPacote(array $pac, array $estados, array $reg): array
{
    $n = array_fill_keys(TIPOS_OPERACAO, 0);
    foreach ($estados as $i => $st) {
        $o = $pac['operacoes'][$i];
        if (in_array(chaveOperacao($o), $reg['chaves'], true)) continue;
        if ($st['estado'] === 'aplicavel' || ($st['estado'] === 'opcional' && !$reg['aplicado'])) $n[$o['tipo']]++;
    }
    return $n;
}

/** Mudanças que podem ser aplicadas mas ficaram desmarcadas (e não entram no aviso). */
function desmarcadasDoPacote(array $pac, array $estados, array $reg): int
{
    $pend = array_sum(pendentesDoPacote($pac, $estados, $reg));
    $podem = count(array_filter($estados, static fn($st) => $st['estado'] === 'aplicavel' || $st['estado'] === 'opcional'));
    return max(0, $podem - $pend);
}

/** "5 produtos novos, 7 trocados pelo modelo atual, 1 foto nova, 2 para tirar do site" ($como: aviso | feito | dialogo) */
function frasesContagem(array $n, string $como = 'aviso'): array
{
    $out = [];
    $q = static fn(int $x, string $um, string $varios): string => $x . ' ' . ($x === 1 ? $um : $varios);
    if (!empty($n['adicionar'])) $out[] = $q($n['adicionar'], 'produto novo', 'produtos novos');
    if (!empty($n['substituir'])) $out[] = $q($n['substituir'], 'trocado pelo modelo atual', 'trocados pelo modelo atual');
    if (!empty($n['foto'])) $out[] = $q($n['foto'], 'foto nova', 'fotos novas');
    if (!empty($n['remover'])) {
        $out[] = match ($como) {
            'feito'   => $q($n['remover'], 'tirado do site', 'tirados do site'),
            'dialogo' => $q($n['remover'], 'sai do site', 'saem do site'),
            default   => $n['remover'] . ' para tirar do site',
        };
    }
    return $out;
}

/** Avisos para a lista de produtos (só administradores): pacotes com mudanças pendentes e não dispensados. */
function avisosDePacotes(array $catalogo): array
{
    $out = [];
    foreach (idsPacotes() as $id) {
        $reg = registroDoPacote($id);
        if ($reg['dispensado'] !== null) continue;
        $pac = lerPacote($id, $catalogo['categorias']);
        if ($pac['erro'] !== null) { $out[] = ['id' => $id, 'titulo' => $pac['titulo'], 'erro' => $pac['erro'], 'frases' => []]; continue; }
        $n = pendentesDoPacote($pac, estadosDoPacote($pac, $catalogo['produtos'], $reg), $reg);
        if (array_sum($n) > 0) $out[] = ['id' => $id, 'titulo' => $pac['titulo'], 'erro' => null, 'frases' => frasesContagem($n)];
    }
    return $out;
}

/* ------------------------------------------------------------------
   Aplicar
   ------------------------------------------------------------------ */
/**
 * Executa as mudanças $indices (na ordem do pacote) sobre $catalogo e devolve
 * ['produtos' => lista nova, 'feitas' => [índices], 'puladas' => [índice => motivo], 'n' => contagem por tipo].
 * Copia as fotos das mudanças feitas para compartilhado/img/ ($copiarFotos), sem nunca
 * sobrescrever: cada arquivo criado entra em $copiadas ([tipo, arquivo]) para poder ser
 * desfeito se a gravação do catálogo falhar.
 */
function executarOperacoes(array $pac, array $catalogo, array $indices, array $reg, array &$copiadas, bool $copiarFotos = true): array
{
    $prods = array_values($catalogo['produtos']);
    $pac['categorias'] = $catalogo['categorias'];   // as do catálogo travado (podem ter mudado desde a tela)
    $feitas = [];
    $puladas = [];
    $n = array_fill_keys(TIPOS_OPERACAO, 0);
    sort($indices);
    foreach ($indices as $i) {
        // Situação diante do catálogo como está AGORA (as mudanças anteriores deste pedido já valem)
        $st = estadoOperacao($pac, $i, $prods, $reg);
        if ($st['estado'] !== 'aplicavel' && $st['estado'] !== 'opcional') { $puladas[$i] = $st['situacao']; continue; }
        $o = $pac['operacoes'][$i];
        // Subcategoria do pacote que não existe mais: o modelo novo entra na do antigo (destinoDoProdutoDoPacote)
        if (isset($st['subcategoria'], $o['produto'])) $o['produto']['subcategoria'] = $st['subcategoria'];
        if ($copiarFotos) {
            foreach (fotosDaOperacao($o) as [$tipo, $arq]) copiarFotoDoPacote($pac['id'], $tipo, $arq, $copiadas);
        }
        $prods = aplicarOperacao($o, $prods);
        $feitas[] = $i;
        $n[$o['tipo']]++;
    }
    return ['produtos' => $prods, 'feitas' => $feitas, 'puladas' => $puladas, 'n' => $n];
}

/** Aplica UMA mudança (já conferida como possível) à lista de produtos. */
function aplicarOperacao(array $o, array $prods): array
{
    $k = isset($o['alvo']) ? indicePorId($prods, $o['alvo']) : null;
    switch ($o['tipo']) {
        case 'adicionar':
            $p = $o['produto'];
            // Logo depois do primeiro de "depois_de" que está no catálogo (e é da mesma categoria); senão, no fim da categoria
            $pos = null;
            foreach ($o['depois_de'] as $ancora) {
                $d = indicePorId($prods, $ancora);
                if ($d !== null && ($prods[$d]['categoria'] ?? '') === $p['categoria']) { $pos = $d + 1; break; }
            }
            array_splice($prods, $pos ?? posicaoNaCategoria($prods, $p['categoria']), 0, [$p]);
            break;
        case 'substituir':
            $p = $o['produto'];
            if ($k === null) {   // o antigo sumiu: entra no fim da categoria, como um produto novo
                array_splice($prods, posicaoNaCategoria($prods, $p['categoria']), 0, [$p]);
                break;
            }
            // Herda do antigo o que é escolha da loja: destaque, fora do site e a anotação interna
            $antigo = $prods[$k];
            $p['destaque'] = !empty($antigo['destaque']);
            if (($antigo['visivel'] ?? true) === false) $p['visivel'] = false;
            if (is_string($antigo['nota'] ?? null) && $antigo['nota'] !== '') $p['nota'] = $antigo['nota'];
            $p = ordenarCampos($p);
            if (($antigo['categoria'] ?? '') === $p['categoria']) {
                $prods[$k] = $p;   // mesmo lugar
            } else {
                array_splice($prods, $k, 1);
                array_splice($prods, posicaoNaCategoria($prods, $p['categoria']), 0, [$p]);
            }
            break;
        case 'foto':
            $p = $prods[$k];
            $p['imagem'] = $o['imagem'];
            if ($o['recorte'] !== '') $p['recorte'] = $o['recorte'];
            else unset($p['recorte']);   // o recorte antigo era da foto antiga
            $prods[$k] = ordenarCampos($p);
            break;
        case 'remover':
            array_splice($prods, $k, 1);
            break;
    }
    return array_values($prods);
}

/** Copia uma foto do pacote para a pasta do site. Arquivo igual que já está lá: nada a fazer; diferente: erro. */
function copiarFotoDoPacote(string $id, string $tipo, string $arquivo, array &$copiadas): void
{
    if (!preg_match(RE_PACOTE, $id) || !preg_match(RE_FOTO_DO_PAINEL, $arquivo) || !in_array($tipo, ['produtos', 'recortes'], true)) {
        throw new RuntimeException('foto de pacote inválida');
    }
    $origem = pastaPacotes() . '/' . $id . '/' . ($tipo === 'produtos' ? 'fotos' : 'recortes') . '/' . $arquivo;
    $destino = pastaFotos($tipo) . '/' . $arquivo;
    if (@is_file($destino)) {
        if (fotosIguais($destino, $origem)) return;   // já estava (aplicada antes, ou veio com o site)
        throw new ErroUsuario('Já existe no site uma foto diferente com o nome “' . $arquivo . '”. Nada foi alterado. Avise quem cuida do site.');
    }
    $bytes = @file_get_contents($origem);
    if (!is_string($bytes) || $bytes === '') throw new ErroUsuario('Não consegui ler a foto ' . $arquivo . ' do pacote. Nada foi alterado.');
    if (!@is_dir(pastaFotos($tipo))) @mkdir(pastaFotos($tipo), 0755, true);
    gravarAtomico($destino, $bytes, 0644);
    $copiadas[] = [$tipo, $arquivo];
}

/** Apaga as fotos copiadas neste pedido que o catálogo gravado não usa (a gravação falhou). */
function descartarFotosCopiadas(array $copiadas): void
{
    if (!$copiadas) return;
    try { $usadas = fotosUsadas(lerCatalogo()['produtos']); } catch (Throwable $t) { return; }   // sem saber, não apaga
    foreach ($copiadas as [$tipo, $arq]) {
        if (!isset($usadas[$tipo][$arq]) && preg_match(RE_FOTO_DO_PAINEL, $arq)) @unlink(pastaFotos($tipo) . '/' . $arq);
    }
}

/**
 * Aplica no catálogo do site as mudanças $indices do pacote, com UMA cópia de segurança antes.
 * Devolve ['feitas', 'puladas', 'n', 'copia'] ou lança PacoteSemMudancas (nada a fazer: nada foi gravado).
 */
function aplicarPacote(array $pac, array $indices): array
{
    $titulo = $pac['titulo'];
    $copiadas = [];
    $r = null;
    protegerPastasFotos();
    try {
        $novo = alterarCatalogo(['tipo' => 'pacote', 'antes' => rotuloCopiaPacote($titulo), 'feito' => 'Aplicou a atualização “' . $titulo . '”', 'alvo' => $pac['id'], 'alvoNome' => $titulo],
            static function (array $c) use ($pac, $indices, $titulo, &$copiadas, &$r): array {
                $r = executarOperacoes($pac, $c, $indices, registroDoPacote($pac['id']), $copiadas);
                if (!$r['feitas']) throw new PacoteSemMudancas('nada a aplicar');
                $total = count($r['feitas']);
                return ['produtos' => $r['produtos'], 'feito' => 'Aplicou a atualização “' . $titulo . '” (' . $total . ($total === 1 ? ' mudança)' : ' mudanças)')];
            });
    } catch (Throwable $t) {
        descartarFotosCopiadas($copiadas);
        throw $t;
    }
    marcarPacoteAplicado($pac['id'], array_map(static fn($i) => chaveOperacao($pac['operacoes'][$i]), $r['feitas']), (string) $novo['copia']);
    return ['feitas' => $r['feitas'], 'puladas' => $r['puladas'], 'n' => $r['n'], 'copia' => (string) $novo['copia'], 'produtos' => $novo['produtos']];
}

/** Índices marcados no formulário (op[]): números de mudanças do pacote, sem repetição. Lança ErroUsuario se não conferir. */
function indicesDoFormulario($v, int $total): array
{
    if ($v === null) return [];
    if (!is_array($v) || !array_is_list($v) || count($v) > $total) throw new ErroUsuario('A lista de mudanças marcadas não confere. Recarregue a página e marque de novo.');
    $out = [];
    foreach ($v as $x) {
        if (!is_string($x) || !preg_match('/^(0|[1-9][0-9]{0,3})$/', $x) || (int) $x >= $total || isset($out[(int) $x])) {
            throw new ErroUsuario('A lista de mudanças marcadas não confere. Recarregue a página e marque de novo.');
        }
        $out[(int) $x] = true;
    }
    $out = array_keys($out);
    sort($out);
    return $out;
}

/* ------------------------------------------------------------------
   Ações do painel (index.php?a=pacote…) — só administradores (SO_ADMIN)
   ------------------------------------------------------------------ */
/** Id do pacote no pedido (GET ou POST), conferido: precisa estar no servidor. */
function idPacoteDoPedido(string $fonte = 'get'): string
{
    $v = $fonte === 'post' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
    if (!is_string($v) || !preg_match(RE_PACOTE, $v) || !in_array($v, idsPacotes(), true)) {
        throw new ErroUsuario('Essa atualização do catálogo não está no servidor.');
    }
    return $v;
}

function acaoPacote(): void
{
    $cat = lerCatalogo();
    $ids = idsPacotes();
    // Sem id: a lista (ou direto a única atualização que houver)
    if (isset($_GET['lista']) || (!isset($_GET['id']) && count($ids) !== 1)) {
        $lista = [];
        foreach ($ids as $id) {
            $pac = lerPacote($id, $cat['categorias']);
            $reg = registroDoPacote($id);
            $est = $pac['erro'] === null ? estadosDoPacote($pac, $cat['produtos'], $reg) : [];
            $n = $pac['erro'] === null ? pendentesDoPacote($pac, $est, $reg) : [];
            $lista[] = ['pacote' => $pac, 'registro' => $reg, 'pendentes' => $n, 'desmarcadas' => $pac['erro'] === null ? desmarcadasDoPacote($pac, $est, $reg) : 0,
                'situacao' => $pac['erro'] === null ? situacaoDasAplicacoes($pac, $est, $reg) : null];
        }
        telaPacotes($lista);
        return;
    }
    $id = isset($_GET['id']) ? idPacoteDoPedido() : $ids[0];
    $pac = lerPacote($id, $cat['categorias']);
    $reg = registroDoPacote($id);
    $estados = $pac['erro'] === null ? estadosDoPacote($pac, $cat['produtos'], $reg) : [];
    // "Marcar todas" (link que funciona sem JavaScript): a tela volta com tudo o que pode ser aplicado marcado
    if (($_GET['marcar'] ?? '') === 'todas') {
        foreach ($estados as $i => $st) if ($st['estado'] === 'aplicavel' || $st['estado'] === 'opcional') $estados[$i]['marcado'] = true;
    }
    telaPacote($pac, $estados, $reg, produtosAntesDoPacote($reg), $pac['erro'] === null ? situacaoDasAplicacoes($pac, $estados, $reg) : []);
}

function acaoPacoteAplicar(): void
{
    $cat = lerCatalogo();
    $id = idPacoteDoPedido('post');
    $pac = lerPacote($id, $cat['categorias']);
    if ($pac['erro'] !== null) throw new ErroUsuario('Pacote com problema: ' . $pac['erro'] . '. Nada foi alterado.');
    // A tela foi aberta com outra versão do pacote (chegou uma atualização no meio): os números das mudanças podem não ser os mesmos
    if (!is_string($_POST['versao'] ?? null) || !hash_equals($pac['versao'], $_POST['versao'])) {
        throw new ErroUsuario('Esta atualização do catálogo mudou enquanto a tela estava aberta. Nada foi alterado: confira as mudanças de novo e aplique.');
    }
    $indices = indicesDoFormulario($_POST['op'] ?? null, count($pac['operacoes']));
    if (!$indices) throw new ErroUsuario('Nada foi alterado: marque pelo menos uma mudança para aplicar.');
    try {
        $r = aplicarPacote($pac, $indices);
    } catch (PacoteSemMudancas $e) {
        avisar('Nada foi alterado: as mudanças marcadas já estavam no catálogo.', 'info');
        redirecionar(link_('pacote', ['id' => $id]));
    }
    $msg = 'Pronto: a atualização “' . $pac['titulo'] . '” foi aplicada (' . implode(', ', frasesContagem($r['n'], 'feito')) . ').';
    $np = count($r['puladas']);
    if ($np) $msg .= ' ' . ($np === 1 ? '1 mudança marcada já estava no catálogo e ficou como estava.' : $np . ' mudanças marcadas já estavam no catálogo e ficaram como estavam.');
    avisar($msg, 'ok', ['link' => cfg('url_site') . 'produtos.html', 'textoLink' => 'Ver no site', 'externo' => true]);
    // Tirou do site o produto da capa (só quando a pessoa marcou de propósito)
    foreach ($r['feitas'] as $i) {
        $o = $pac['operacoes'][$i];
        if ($o['tipo'] === 'remover' && $o['alvo'] === produtoCapa()) {
            avisar('Atenção: saiu do site o produto da capa. ' . fraseCapaFora(true), 'erro');
        }
    }
    redirecionar(link_('pacote', ['id' => $id]));
}

/** "Agora não": esconde o aviso da lista de produtos sem aplicar (mostrar=1 desfaz). */
function acaoPacoteDispensar(): void
{
    $id = idPacoteDoPedido('post');
    $mostrar = ($_POST['mostrar'] ?? '') === '1';
    $titulo = lerPacote($id, lerCatalogo()['categorias'])['titulo'];
    if (dispensarPacote($id, !$mostrar)) {
        registrar('pacote', $mostrar ? 'Voltou a mostrar o aviso da atualização do catálogo “' . $titulo . '”'
            : 'Deixou para depois a atualização do catálogo “' . $titulo . '” (Agora não)');
    }
    if ($mostrar) {
        avisar('O aviso da atualização “' . $titulo . '” voltou para o topo da lista de produtos.');
        redirecionar(link_('pacote', ['id' => $id]));
    }
    avisar('Tudo bem: a atualização “' . $titulo . '” fica guardada e nada mudou no site. Para ver de novo: Conta → Atualizações do catálogo.', 'info');
    redirecionar('index.php');
}
