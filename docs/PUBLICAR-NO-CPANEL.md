# Como o site vai ficar no ar (HostGator + cPanel)

O site novo é **estático**: só arquivos HTML, CSS, JavaScript e imagens. Não usa banco de dados nem WordPress. Por isso é **rápido, barato, seguro** e roda em qualquer plano da HostGator.

## Publicação (feita uma vez, depois de escolhido o design)

1. **cPanel → Gerenciador de Arquivos → `public_html`**.
   Faça um backup do site antigo: selecione tudo → **Compactar** → baixe o `.zip`.
2. Apague os arquivos antigos de `public_html` (menos a pasta `.well-known`, se existir).
3. **Carregar** o arquivo `site.zip` (eu entrego pronto) → clique nele → **Extrair** em `/public_html` → apague o `.zip`.
   O `index.html` precisa ficar direto em `public_html/` (não dentro de outra pasta).
4. **Configurações** (canto superior direito) → marque **"Mostrar arquivos ocultos"**, para enxergar o `.htaccess`.
5. **cPanel → Status do SSL/TLS → Executar AutoSSL** (certificado grátis; pode levar algumas horas).
6. Depois que o cadeado (https) estiver funcionando, o `.htaccess` (pasta `hospedagem/` deste projeto) força o https e deixa o site mais rápido.
7. Teste: `balancass.com`, `www.balancass.com`, o catálogo, e o botão do WhatsApp **num celular que não tenha o número salvo**.

## Checklist antes de publicar

- [x] Endereço confirmado: *Rua 13, 650 – entre as Avenidas 9 e 11 – Boa Morte*. Vale atualizar também o Google Meu Negócio e a página do BC Fichas, que ainda mostram endereços antigos/diferentes.
- [ ] Confirmar que o **(19) 3023-9050 está no WhatsApp Business** (o link `wa.me` só funciona com número registrado no WhatsApp).
- [x] IPEM-SP: oficina autorizada (o site diz "Autorizada pelo IPEM-SP", sem número).
- [ ] Instagram da empresa (se houver).
- [ ] Fotos reais: fachada, balcão, bancada técnica, equipe. Valem muito mais que fotos de banco de imagens.
- [ ] Revisar a lista de produtos em `compartilhado/dados/produtos.js` (todos começam como "Consulte disponibilidade").

## Manutenção do dia a dia

Veja **COMO-ADICIONAR-PRODUTOS.md**: adicionar produto, mudar estoque, trocar telefone ou horário, tudo pelo Gerenciador de Arquivos do cPanel.
