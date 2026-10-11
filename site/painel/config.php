<?php
/* =====================================================================
   Painel Balanças.com — CONFIGURAÇÃO (o único lugar com caminhos)
   ---------------------------------------------------------------------
   Os caminhos partem desta pasta (painel/). No site publicado fica assim:
     public_html/painel/            ← este painel
     public_html/compartilhado/     ← catálogo (dados/produtos.js) e fotos
   Se um dia as pastas mudarem de lugar, ajuste só aqui.
   ===================================================================== */
if (!defined('PAINEL')) { http_response_code(404); exit; }

return [
    // Pasta "compartilhado" do site, no servidor (tem dados/produtos.js e img/produtos/)
    'compartilhado'      => __DIR__ . '/../compartilhado',
    // A mesma pasta vista pelo navegador, a partir do endereço do painel (/painel/)
    'url_compartilhado'  => '../compartilhado/',
    // Endereço do site a partir do painel (para os links "Ver no site")
    'url_site'           => '../',

    // Dados privados (senha, cópias de segurança, lixeira, sessões, registro).
    // null = automático: uma pasta FORA do public_html (na pasta inicial da conta),
    // e, se não der para criar lá, a pasta painel/privado/ (protegida).
    // Para escolher outro lugar, troque null por um caminho absoluto entre aspas.
    'pasta_privada'      => null,
    'nome_pasta_privada' => 'painel-balancas-privado',

    'fuso'               => 'America/Sao_Paulo',
    'inatividade'        => 2 * 60 * 60,   // sai sozinho após 2 h sem uso
    'tentativas_max'     => 5,             // erros de senha seguidos por endereço de internet (IP)…
    'bloqueio'           => 15 * 60,       // …bloqueiam esse IP por 15 min
    'codigo_validade'    => 24 * 60 * 60,  // código de primeiro acesso vale 24 h
    'senha_minimo'       => 10,            // tamanho mínimo da senha
    // Cópias de segurança: as 30 mais recentes ficam sempre; além delas, tudo dos últimos 14 dias
    // (até 200 cópias); e as de "excluir" e "restaurar" ficam 30 dias, mesmo além do teto.
    // Ações seguidas do mesmo tipo (ordem, estoque, destaque, no site) em menos de 10 min viram uma cópia só.
    'copias_max'         => 30,
    'copias_dias'        => 14,
    'copias_teto'        => 200,
    'copias_protegidas_dias' => 30,
    'copias_juntar_min'  => 10,
    // Produto da capa do site (a balança grande do topo da página inicial). O painel lê o
    // data-hero-id do index.html do site; este valor só vale se não achar lá.
    'produto_capa'       => 'toledo-prix-7e',
    'foto_max_bytes'     => 8 * 1024 * 1024,   // 8 MB
    'foto_min_lado'      => 300,           // foto precisa ter pelo menos 300 px de largura e de altura
    'foto_max_pixels'    => 20000000,      // até 20 megapixels (ex.: 5472 × 3648); mais que isso pede memória demais
    'foto_max_lado'      => 900,           // fotos salvas com no máximo 900 px no lado maior
    'foto_qualidade'     => 84,            // qualidade WebP/JPEG (0–100)

    // Logos de clientes (área Clientes): PNG, JPG ou WebP (SVG não), salvos em WebP
    // em compartilhado/img/clientes/, com a transparência preservada
    'logo_max_bytes'     => 5 * 1024 * 1024,   // 5 MB
    'logo_min_largura'   => 80,            // pelo menos 80 px de largura (sem contar as margens vazias)
    'logo_max_largura'   => 600,           // logos salvos com no máximo 600 × 300 px
    'logo_max_altura'    => 300,
    'logo_qualidade'     => 90,            // qualidade WebP dos logos (0–100; letras pedem mais que fotos)
];
