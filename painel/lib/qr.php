<?php
/* =====================================================================
   Painel Balanças.com — código QR (escrito do zero, sem bibliotecas)
   Só o que a verificação em duas etapas precisa: modo byte, correção M,
   versões 1 a 10 (até 213 bytes) e as 8 máscaras, com a escolha pela
   penalidade da norma (ISO/IEC 18004). Sai em SVG dentro da página (só
   atributos, sem style=: a Content-Security-Policy do painel não deixa).
   Nada vai para serviço de fora: a chave do aplicativo nunca sai daqui.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// Correção M, por versão: [total de palavras, palavras de correção por bloco, número de blocos].
// Os blocos "curtos" vêm primeiro; os "longos" têm uma palavra de dados a mais (v8, v9 e v10).
const QR_TABELA_M = [1 => [26, 10, 1], 2 => [44, 16, 1], 3 => [70, 26, 1], 4 => [100, 18, 2], 5 => [134, 24, 2],
    6 => [172, 16, 4], 7 => [196, 18, 4], 8 => [242, 22, 4], 9 => [292, 22, 5], 10 => [346, 26, 5]];
// Centros dos padrões de alinhamento (todas as combinações, menos as que caem nos localizadores)
const QR_ALINHAMENTO = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
    7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]];
const QR_MAX_BYTES = 213;

/**
 * Matriz do QR (true = módulo escuro), modo byte, correção M, a menor versão de 1 a 10 que caiba.
 * $mascara null = escolhe pela penalidade; 0–7 força (para os testes). $versaoMinima: só para os testes
 * (poucos bytes numa versão grande). Lança InvalidArgumentException acima de 213 bytes. $m[linha][coluna].
 */
function qrMatriz(string $dados, ?int $mascara = null, int $versaoMinima = 1): array
{
    if ($mascara !== null && ($mascara < 0 || $mascara > 7)) throw new InvalidArgumentException('máscara inválida (0 a 7)');
    $n = strlen($dados);
    $versao = null;
    foreach (QR_TABELA_M as $v => [$total, $ec, $blocos]) {
        if ($v >= $versaoMinima && 4 + ($v <= 9 ? 8 : 16) + 8 * $n <= ($total - $ec * $blocos) * 8) { $versao = $v; break; }
    }
    if ($versao === null) throw new InvalidArgumentException('dados longos demais para o código QR (máximo ' . QR_MAX_BYTES . ' bytes)');
    [$total, $ec, $blocos] = QR_TABELA_M[$versao];
    $capacidade = ($total - $ec * $blocos) * 8;

    // 1. Bits: modo byte (0100), contagem, os bytes, terminador, completa o byte, enche com EC/11
    $bits = [];
    $poe = static function (int $valor, int $qtd) use (&$bits): void {
        for ($i = $qtd - 1; $i >= 0; $i--) $bits[] = ($valor >> $i) & 1;
    };
    $poe(0b0100, 4);
    $poe($n, $versao <= 9 ? 8 : 16);
    for ($i = 0; $i < $n; $i++) $poe(ord($dados[$i]), 8);
    $poe(0, min(4, $capacidade - count($bits)));
    $poe(0, (8 - count($bits) % 8) % 8);
    for ($p = 0xEC; count($bits) < $capacidade; $p ^= 0xEC ^ 0x11) $poe($p, 8);
    $palavras = [];
    foreach (array_chunk($bits, 8) as $oito) {
        $b = 0;
        foreach ($oito as $x) $b = ($b << 1) | $x;
        $palavras[] = $b;
    }

    // 2. Blocos e correção Reed–Solomon; 3. intercala (dados coluna a coluna, depois a correção)
    $curtos = $blocos - $total % $blocos;
    $dadosCurto = intdiv($total, $blocos) - $ec;
    $divisor = qrDivisorRS($ec);
    $dadosBl = [];
    $ecBl = [];
    for ($i = 0, $k = 0; $i < $blocos; $i++) {
        $len = $dadosCurto + ($i < $curtos ? 0 : 1);
        $dadosBl[] = $d = array_slice($palavras, $k, $len);
        $ecBl[] = qrRestoRS($d, $divisor);
        $k += $len;
    }
    $final = [];
    for ($i = 0; $i <= $dadosCurto; $i++) {
        foreach ($dadosBl as $d) if ($i < count($d)) $final[] = $d[$i];
    }
    for ($i = 0; $i < $ec; $i++) {
        foreach ($ecBl as $e) $final[] = $e[$i];
    }

    // 4. Padrões fixos (localizadores, tempo, alinhamento, formato, versão, módulo escuro)
    $tam = 17 + 4 * $versao;
    $m = array_fill(0, $tam, array_fill(0, $tam, false));
    $fixo = $m;
    $fixar = static function (int $l, int $c, bool $escuro) use (&$m, &$fixo): void {
        $m[$l][$c] = $escuro;
        $fixo[$l][$c] = true;
    };
    for ($i = 0; $i < $tam; $i++) {
        $fixar(6, $i, $i % 2 === 0);
        $fixar($i, 6, $i % 2 === 0);
    }
    foreach ([[3, 3], [3, $tam - 4], [$tam - 4, 3]] as [$cl, $cc]) {
        for ($dl = -4; $dl <= 4; $dl++) {
            for ($dc = -4; $dc <= 4; $dc++) {
                $l = $cl + $dl;
                $c = $cc + $dc;
                if ($l < 0 || $l >= $tam || $c < 0 || $c >= $tam) continue;
                $dist = max(abs($dl), abs($dc));
                $fixar($l, $c, $dist !== 2 && $dist !== 4);   // localizador 7×7 + separador claro
            }
        }
    }
    $pos = QR_ALINHAMENTO[$versao];
    $ult = count($pos) - 1;
    foreach ($pos as $i => $pl) {
        foreach ($pos as $j => $pc) {
            if (($i === 0 && $j === 0) || ($i === 0 && $j === $ult) || ($i === $ult && $j === 0)) continue;
            for ($dl = -2; $dl <= 2; $dl++) {
                for ($dc = -2; $dc <= 2; $dc++) $fixar($pl + $dl, $pc + $dc, max(abs($dl), abs($dc)) !== 1);
            }
        }
    }
    // Formato: correção M (00) + máscara, BCH(15,5) gerador 0x537, XOR 0x5412; duas cópias
    $formato = static function (int $mask) use ($fixar, $tam): void {
        $dados = (0b00 << 3) | $mask;
        $resto = $dados;
        for ($i = 0; $i < 10; $i++) $resto = ($resto << 1) ^ (($resto >> 9) * 0x537);
        $b = (($dados << 10) | $resto) ^ 0x5412;
        $bit = static fn(int $i): bool => (($b >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) $fixar($i, 8, $bit($i));
        $fixar(7, 8, $bit(6));
        $fixar(8, 8, $bit(7));
        $fixar(8, 7, $bit(8));
        for ($i = 9; $i < 15; $i++) $fixar(8, 14 - $i, $bit($i));
        for ($i = 0; $i < 8; $i++) $fixar(8, $tam - 1 - $i, $bit($i));
        for ($i = 8; $i < 15; $i++) $fixar($tam - 15 + $i, 8, $bit($i));
        $fixar($tam - 8, 8, true);   // módulo escuro fixo (linha 4v+9, coluna 8)
    };
    $formato(0);   // reserva o lugar; o de verdade vem depois da máscara
    if ($versao >= 7) {
        // Versão: BCH(18,6) gerador 0x1F25, nas duas cópias perto dos localizadores de cima à direita e de baixo à esquerda
        $resto = $versao;
        for ($i = 0; $i < 12; $i++) $resto = ($resto << 1) ^ (($resto >> 11) * 0x1F25);
        $b = ($versao << 12) | $resto;
        for ($i = 0; $i < 18; $i++) {
            $escuro = (($b >> $i) & 1) === 1;
            $a = $tam - 11 + $i % 3;
            $c = intdiv($i, 3);
            $fixar($c, $a, $escuro);
            $fixar($a, $c, $escuro);
        }
    }

    // 5. Dados em zigue-zague, de baixo para cima, em pares de colunas da direita para a esquerda (pula a coluna 6)
    $totalBits = count($final) * 8;
    $i = 0;
    for ($dir = $tam - 1; $dir >= 1; $dir -= 2) {
        if ($dir === 6) $dir = 5;
        $subindo = (($dir + 1) & 2) === 0;
        for ($v = 0; $v < $tam; $v++) {
            $l = $subindo ? $tam - 1 - $v : $v;
            for ($j = 0; $j < 2; $j++) {
                $c = $dir - $j;
                if ($fixo[$l][$c] || $i >= $totalBits) continue;
                $m[$l][$c] = (($final[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                $i++;
            }
        }
    }

    // 6. Máscara: a de menor penalidade (ou a pedida)
    $aplicar = static function (int $mask) use (&$m, $fixo, $tam): void {
        for ($l = 0; $l < $tam; $l++) {
            for ($c = 0; $c < $tam; $c++) {
                if (!$fixo[$l][$c] && qrCondicaoMascara($mask, $l, $c)) $m[$l][$c] = !$m[$l][$c];
            }
        }
    };
    if ($mascara === null) {
        $mascara = 0;
        $menor = PHP_INT_MAX;
        for ($k = 0; $k < 8; $k++) {
            $aplicar($k);
            $formato($k);
            $p = qrPenalidade($m);
            if ($p < $menor) { $menor = $p; $mascara = $k; }
            $aplicar($k);   // desfaz (XOR)
        }
    }
    $aplicar($mascara);
    $formato($mascara);
    return $m;
}

/** SVG inline: viewBox com 4 módulos de margem branca, um <rect> branco e um <path> preto (sem style=). */
function qrSvg(string $dados, string $rotulo): string
{
    $m = qrMatriz($dados);
    $n = count($m);
    $lado = $n + 8;
    $d = '';
    foreach ($m as $l => $linha) {
        for ($c = 0; $c < $n; $c++) {
            if (!$linha[$c]) continue;
            $k = 1;
            while ($c + $k < $n && $linha[$c + $k]) $k++;
            $d .= 'M' . ($c + 4) . ' ' . ($l + 4) . 'h' . $k . 'v1h-' . $k . 'z';
            $c += $k - 1;
        }
    }
    return '<svg class="qr__svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $lado . ' ' . $lado . '" role="img" aria-label="' . e($rotulo)
        . '" shape-rendering="crispEdges"><rect width="' . $lado . '" height="' . $lado . '" fill="#fff"/><path fill="#000" d="' . $d . '"/></svg>';
}

/* ------------------------------------------------------------------
   Ajudantes (campo de Galois GF(256), polinômio 0x11D)
   ------------------------------------------------------------------ */
function qrMultiplicar(int $x, int $y): int
{
    $z = 0;
    for ($i = 7; $i >= 0; $i--) {
        $z = ($z << 1) ^ (($z >> 7) * 0x11D);
        $z ^= (($y >> $i) & 1) * $x;
    }
    return $z;
}

/** Gerador ∏(x − αⁱ), i = 0..grau−1 (sem o coeficiente líder, que é 1). */
function qrDivisorRS(int $grau): array
{
    $r = array_fill(0, $grau, 0);
    $r[$grau - 1] = 1;
    $raiz = 1;
    for ($i = 0; $i < $grau; $i++) {
        for ($j = 0; $j < $grau; $j++) {
            $r[$j] = qrMultiplicar($r[$j], $raiz);
            if ($j + 1 < $grau) $r[$j] ^= $r[$j + 1];
        }
        $raiz = qrMultiplicar($raiz, 0x02);
    }
    return $r;
}

/** Palavras de correção de um bloco (resto da divisão polinomial). */
function qrRestoRS(array $dados, array $divisor): array
{
    $r = array_fill(0, count($divisor), 0);
    foreach ($dados as $b) {
        $fator = $b ^ array_shift($r);
        $r[] = 0;
        foreach ($divisor as $i => $coef) $r[$i] ^= qrMultiplicar($coef, $fator);
    }
    return $r;
}

/** As 8 máscaras da norma ($l = linha, $c = coluna): true = inverte o módulo. */
function qrCondicaoMascara(int $mask, int $l, int $c): bool
{
    return match ($mask) {
        0 => ($l + $c) % 2 === 0,
        1 => $l % 2 === 0,
        2 => $c % 3 === 0,
        3 => ($l + $c) % 3 === 0,
        4 => (intdiv($l, 2) + intdiv($c, 3)) % 2 === 0,
        5 => ($l * $c) % 2 + ($l * $c) % 3 === 0,
        6 => (($l * $c) % 2 + ($l * $c) % 3) % 2 === 0,
        7 => (($l + $c) % 2 + ($l * $c) % 3) % 2 === 0,
    };
}

/** Penalidade (N1 = 3 sequências de 5+, N2 = 3 blocos 2×2, N3 = 40 padrão 1:1:3:1:1, N4 = 10 por 5% fora de 50% escuros). */
function qrPenalidade(array $m): int
{
    $tam = count($m);
    $pontos = 0;
    for ($eixo = 0; $eixo < 2; $eixo++) {
        for ($a = 0; $a < $tam; $a++) {
            $cor = false;
            $corrida = 0;
            $hist = [0, 0, 0, 0, 0, 0, 0];
            for ($b = 0; $b < $tam; $b++) {
                $x = $eixo === 0 ? $m[$a][$b] : $m[$b][$a];
                if ($x === $cor) {
                    $corrida++;
                    if ($corrida === 5) $pontos += 3;
                    elseif ($corrida > 5) $pontos++;
                } else {
                    qrHistorico($corrida, $hist, $tam);
                    if (!$cor) $pontos += qrPadroesLocalizador($hist) * 40;
                    $cor = $x;
                    $corrida = 1;
                }
            }
            if ($cor) { qrHistorico($corrida, $hist, $tam); $corrida = 0; }
            qrHistorico($corrida + $tam, $hist, $tam);   // a margem clara depois da última sequência
            $pontos += qrPadroesLocalizador($hist) * 40;
        }
    }
    $escuros = 0;
    for ($l = 0; $l < $tam; $l++) {
        for ($c = 0; $c < $tam; $c++) {
            if ($m[$l][$c]) $escuros++;
            if ($l < $tam - 1 && $c < $tam - 1 && $m[$l][$c] === $m[$l][$c + 1] && $m[$l][$c] === $m[$l + 1][$c] && $m[$l][$c] === $m[$l + 1][$c + 1]) $pontos += 3;
        }
    }
    $total = $tam * $tam;
    return $pontos + ((int) ceil(abs($escuros * 20 - $total * 10) / $total) - 1) * 10;
}

function qrHistorico(int $corrida, array &$hist, int $tam): void
{
    if ($hist[0] === 0) $corrida += $tam;   // a margem clara antes da primeira sequência
    array_pop($hist);
    array_unshift($hist, $corrida);
}

function qrPadroesLocalizador(array $h): int
{
    $n = $h[1];
    $miolo = $n > 0 && $h[2] === $n && $h[3] === $n * 3 && $h[4] === $n && $h[5] === $n;
    return ($miolo && $h[0] >= $n * 4 && $h[6] >= $n ? 1 : 0) + ($miolo && $h[6] >= $n * 4 && $h[0] >= $n ? 1 : 0);
}
