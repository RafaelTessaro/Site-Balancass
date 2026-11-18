# Campanha: Calibração de balanças — textos, peças e plano (revisão 3)

Material para divulgar o serviço nas redes sociais, no tráfego pago (Meta Ads, Google Ads e LinkedIn) e no WhatsApp.
A **revisão 3** segue o *Guia de revisão das artes* e a *Reavaliação das artes V2* (18/11/2026). A direção visual, os textos de leitura e a separação por canal foram mantidos. Mudanças desta rodada:
- o peso não fica mais no prato da balança (a foto tem o visor zerado): agora fica **ao lado**, numa composição ilustrativa;
- nenhum texto sobre a faixa verde, e o gerador passou a conferir isso;
- o certificado ilustrativo ganhou a legenda externa "Modelo ilustrativo de certificado";
- o logo do slide verde entrou sobre uma placa preta;
- a ação ficou "Solicite um orçamento" e o pedido inicial ficou simples (foto e cidade);
- o LinkedIn horizontal e a capa ganharam textos maiores.

As artes estão nas pastas ao lado. Para ver todas de uma vez, abra `ver-todas.html` no navegador.
Destino dos anúncios: **https://balancass.com/calibracao.html** ou o WhatsApp **(19) 3023-9050**. Escreva sempre **balancass.com**, com dois "s".

---

## 1. Antes de publicar

1. **Publique a atualização do site** (`atualizacao.zip`, guia de publicação, item 6). A página `calibracao.html`, a prévia de link e o texto novo da ilustração do certificado estão nesse pacote.
2. **Confira a página com um navegador**, no celular e no computador: `balancass.com/calibracao.html`. Toque em **Solicitar orçamento** e veja se abre o WhatsApp (19) 3023-9050 com a mensagem pronta. Nas revisões, as consultas feitas por ferramentas receberam a resposta *406* do servidor. Isso não prova que todo acesso automático é bloqueado nem que a página não existe; a conferência precisa ser feita por uma pessoa.
3. **Teste a prévia do link**: mande o endereço para você mesmo no WhatsApp e confira a imagem e o título. Se aparecer a imagem antiga, o WhatsApp está usando a cópia guardada; teste de novo depois de algumas horas.
4. **Confirme os dados do item 3** antes de ligar os anúncios.
5. Deixe pronta a resposta do WhatsApp (item 7) e um **modelo de certificado com dados fictícios** (o sistema de certificados gera a prévia com a marca "PRÉVIA — SEM VALOR"). A página oferece esse modelo a quem pedir.

---

## 2. Regras de texto (valem para artes, legendas, anúncios e respostas)

| Use | Não use | Por quê |
|---|---|---|
| “calibração de balanças com certificado”, “emissão de certificado” | “agora com certificado”, “a gente emite”, “a gente certifica” | O serviço é a calibração; o certificado é o registro dela. “Certifica” sugere certificação da empresa |
| “Serviço de calibração não acreditado pela Cgcre. Consulte a adequação aos requisitos da sua empresa.” | “atende a maioria (ou diversas) das auditorias ISO 9001”, “vale para a maioria das empresas” | Não há como prometer aceitação: ela depende do cliente, do contrato, do setor e da auditoria |
| ISO 9001 como **contexto do cliente** (“auditoria em preparação?”) | etiqueta “ISO 9001” como se fosse selo da empresa | Pode parecer certificação da Balanças.com |
| “compara as indicações da balança com padrões de referência e registra os resultados e a incerteza” | “mostra quanto a balança erra e com que segurança” | “Segurança” não explica a incerteza de medição |
| “informações de rastreabilidade metrológica”, “padrões calibrados por laboratório acreditado” | “a rastreabilidade que o auditor pede”, “rastreável à RBC”, “RBC”/“acreditado” como selo | A exigência varia. A acreditação do laboratório que calibrou os padrões não se transfere ao prestador do serviço |
| “Realizamos calibração e manutenção. A verificação metrológica é feita pelo IPEM-SP.” | “a gente cuida das duas”, “para a qualidade × para a lei” | Não pode parecer que a Balanças.com faz a verificação oficial |
| “a calibração não substitui a verificação metrológica do IPEM-SP, **quando aplicável**” | “toda balança precisa da verificação” | A verificação vale para os casos previstos (por exemplo, venda por peso) |
| “atendimento no local, mediante avaliação das condições de calibração”, “agendamos conforme a disponibilidade” | “sem tirar a balança do lugar”, “no dia que não atrapalha o seu trabalho” | Promessas absolutas que dependem do equipamento e do local |
| “resultados documentados” | “pronto para mostrar”, “aprovado”, “validade” | Sugere adequação garantida ou aprovação |
| “Modelo ilustrativo de certificado” por fora da imagem e “exemplo ilustrativo — sem valor de certificado” no papel | número, resultados ou assinatura que pareçam reais | Não pode parecer um documento emitido |
| a balança e o peso **separados** (composição ilustrativa) ou foto real do serviço | peso montado no prato de uma foto com o visor zerado; visor editado | Sugere uma medição incoerente ou um resultado que não foi registrado |
| “Solicite um orçamento” nas peças de contratação | “Fale com a Balanças.com” | Diz qual é a ação |
| primeiro contato: “Envie uma foto da identificação da balança e informe a cidade do atendimento.” | pedir “os requisitos da sua empresa” logo de cara | “Requisitos” pode não ser claro para o pequeno comércio; as exigências específicas entram na conversa |
| texto sempre sobre fundo uniforme | texto atravessando a faixa verde | Off-white sobre o verde fica com contraste de cerca de 3:1, e o cinza com cerca de 2,4:1 |

Tom: técnico, acessível e direto (“envie”, “solicite”, “consulte”, “atendimento”). No WhatsApp e nos posts do perfil, o tom pode ser mais próximo, sem transformar incerteza, rastreabilidade ou acreditação em slogan.

---

## 3. Afirmações, de onde vêm e o que falta confirmar

“Sistema” é o sistema de certificados da empresa (repositório `sistema_certificadodecalibracao`). O desenvolvedor consultou o código e a documentação dele. **Essas evidências não vão no pacote das artes**: quem revisar o pacote deve pedi-las ou conferir num certificado real emitido.

| Afirmação usada | Onde aparece | De onde vem | Situação |
|---|---|---|---|
| Serviço **não acreditado** pela Cgcre (fora da RBC), informado no certificado | Site, artes, textos | Sistema: `README.md` (“laboratório não acreditado, fora da RBC”; o certificado traz a “declaração de laboratório não acreditado”) | Conferir num PDF real |
| Ensaios de erro de indicação, repetibilidade e excentricidade; incerteza com fator k | Site, LinkedIn, carrossel 3 | Sistema: `README.md` (etapas 4 a 6 da calibração; “incerteza (k e νeff)”) | Conferir num PDF real |
| Incerteza pelo guia **EURAMET cg-18** | Site, LinkedIn | Sistema: `README.md` (“feito ao vivo pelo método EURAMET cg-18”) | Conferir se o POP vigente cita o mesmo guia |
| Padrões calibrados por **laboratório acreditado**, com os certificados enviados junto | Site | Sistema: `README.md` (“todo padrão precisa da acreditação Cgcre do laboratório que o calibrou”; envio por e-mail “junto com os certificados dos padrões usados”) | Conferir num e-mail de envio real |
| **PDF assinado digitalmente** | Site (o que vem no certificado e passo 4) | Informado pelo dono (18/11/2026). Sistema: `server/certificados.ts` (com o certificado digital da empresa instalado, nenhum certificado sai sem assinatura; sem ele, sai sem assinatura) | **Conferir num PDF real** (abrir no leitor de PDF e ver o painel de assinaturas). Se não estiver assinado, troque no site por “Certificado em PDF”. Nas artes, a ilustração já diz só “Certificado em PDF” |
| Condições do ensaio (temperatura, umidade e pressão) | Site | Sistema: `README.md` (pressão obrigatória pelo POP) | Conferir num PDF real |
| Lembrete da próxima calibração; data no certificado só se o cliente pedir | Site | Sistema: `README.md` (“lembretes de calibração”; “a data da próxima calibração só sai no certificado se o cliente pediu”) | — |
| **Atendimento no local** em Rio Claro e região | Site, artes, textos | Operação da empresa | **A confirmar:** cidades e condições (acesso, espaço para os pesos, ambiente) |
| **Balanças e capacidades** atendidas; rodoviárias e ferroviárias fora do serviço | Site (perguntas) | Rodoviárias: sistema (`README.md`, fora do cadastro novo). O resto: operação | **A confirmar:** faixa de capacidade e classes cobertas pelos padrões |
| Modelo de certificado com dados fictícios para quem pedir | Site | Operação | **A confirmar:** gerar uma prévia no sistema |
| “Os resultados apresentados correspondem aos ensaios realizados” | Carrossel 3 | Sistema: o certificado traz os ensaios feitos | Conferir num PDF real |
| Prazo de entrega do certificado | — | — | Não usado em nenhuma peça (não há prazo confirmado) |
| Nota 5,0 no Google (56 avaliações) | Site, título do Google | Dados do site (`compartilhado/dados/empresa.js`) | Conferir antes de usar em anúncio |

As artes mostram uma **balança de bancada de 15 kg e, ao lado, um peso de 10 kg**, e um peso de 20 kg sozinho. É uma composição ilustrativa (foto de catálogo + desenho), não uma foto do serviço. Se a empresa não calibra esse tipo de balança ou não tem esses padrões, troque a imagem. Assim que houver **fotos reais e autorizadas** da calibração, elas devem substituir a composição, a começar pelas imagens responsivas do Google.

---

## 4. As peças

| Peça original (revisão 1) | Arquivo atual | Revisão 3 |
|---|---|---|
| `feed-4x5-A-lancamento` | `instagram-facebook/feed-4x5-A-principal.png` | Peso ao lado da balança; apoio novo |
| `feed-4x5-B-auditoria` | `instagram-facebook/feed-4x5-B-auditoria.png` | “Calibração de balanças com emissão de certificado” antes da ressalva; ressalva fora da faixa; “Solicite um orçamento” |
| `feed-4x5-C-o-que-vem` | `instagram-facebook/feed-4x5-C-o-que-voce-recebe.png` | Legenda externa “Modelo ilustrativo de certificado”; ilustração só com “Certificado em PDF” |
| `quadrado-1x1-A-lancamento` | `instagram-facebook/quadrado-1x1-A-principal.png` | Peso ao lado; “Rio Claro e região” |
| `quadrado-1x1-B-auditoria` | `instagram-facebook/quadrado-1x1-B-sua-empresa.png` | Pedido: foto e cidade; “Solicite um orçamento” |
| `stories-9x16-A-lancamento` | `instagram-facebook/9x16/stories-9x16-anuncio-principal.png` | Peso ao lado; apoio novo |
| ↳ nova na revisão 2 | `instagram-facebook/9x16/stories-9x16-organico-link.png` | Peso ao lado; “Toque no link e solicite um orçamento” |
| ↳ nova na revisão 2 | `instagram-facebook/9x16/status-whatsapp-9x16.png` | Peso ao lado |
| `stories-9x16-B-auditoria` | `instagram-facebook/9x16/stories-9x16-anuncio-auditoria.png` | Linha do serviço antes da ressalva; “Solicite um orçamento” |
| `carrossel-1-capa` | `instagram-facebook/carrossel/carrossel-1-capa.png` | Peso ao lado; número e “Arraste” fora da faixa |
| `carrossel-2-o-que-e` | `…/carrossel-2-o-que-e.png` | Número e “Arraste” fora da faixa |
| `carrossel-3-o-que-vem` | `…/carrossel-3-o-que-informa.png` | Legenda externa; “Os resultados apresentados correspondem aos ensaios realizados” |
| `carrossel-4-nao-e-afericao` | `…/carrossel-4-finalidades.png` | Logo sobre placa preta; verificação: “Avaliação dos requisitos legais aplicáveis ao instrumento” |
| `carrossel-5-orcamento` | `…/carrossel-5-orcamento.png` | Textos fora da faixa; pedido: foto e cidade; contato logo abaixo do pedido |
| `capa-facebook-1640x624` | `instagram-facebook/capa-facebook-1640x624.png` | Peso ao lado; apoio de 36 para 44 px |
| `linkedin-1200x627` | `linkedin/linkedin-1200x627.png` | Título curto (2 linhas); benefício, ação e condição de 38 a 42 px; peso ao lado |
| `linkedin-1200x1200` | `linkedin/linkedin-1200x1200.png` | Título em 3 linhas; textos de 38 a 44 px; peso ao lado |
| `og-calibracao.jpg` | `site/og-calibracao.jpg` | Peso ao lado (vai no `atualizacao.zip`) |
| `display-300x250` … `display-320x50` (7) | `google-display/display-*.jpg` | 300×600: peso ao lado; 728×90: faixa na ponta, longe do título |
| `responsivo-paisagem` e `responsivo-quadrado` | `google-display/responsivo/*` | Peso ao lado da balança, sem texto. Continua sendo composição: trocar por foto real |
| `logo-quadrado`, `logo-paisagem` | `google-display/responsivo/logo-*.png` | Mantidos |

São 29 arquivos: as 27 peças originais revisadas e as duas versões verticais acrescentadas na revisão 2.

**Imagens estáticas não são links.** O que parece botão na arte é só a orientação: o link é o botão do anúncio, a figurinha de link do story ou a resposta no WhatsApp. Os PNG verticais não são vídeos de Reels.

---

## 5. Textos dos anúncios — Meta Ads (Instagram e Facebook)

Botão: **Enviar mensagem** (abre o WhatsApp) ou **Saiba mais** (leva à página).

### A · Principal (`feed-4x5-A-principal`, `quadrado-1x1-A-principal`, `stories-9x16-anuncio-principal`)
- **Texto principal:**
  > A Balanças.com oferece calibração de balanças com emissão de certificado, para o controle de qualidade da sua empresa.
  >
  > Para o orçamento, envie uma foto da identificação da balança e informe a cidade do atendimento. Se houver exigências do cliente, do contrato ou da auditoria, conte na conversa.
  >
  > Serviço de calibração não acreditado pela Cgcre. A calibração não substitui a verificação metrológica do IPEM-SP, quando aplicável.
- **Título:** Calibração de balanças com certificado
- **Descrição:** Rio Claro e região

### B · Auditoria (`feed-4x5-B-auditoria`, `stories-9x16-anuncio-auditoria`)
- **Texto principal:**
  > Auditoria em preparação? Confira os requisitos de calibração das suas balanças.
  >
  > A Balanças.com faz calibração de balanças com emissão de certificado. Envie uma foto da identificação da balança, a cidade e as exigências da auditoria: avaliamos o atendimento e preparamos o orçamento.
  >
  > Serviço de calibração não acreditado pela Cgcre. Consulte a adequação aos requisitos da sua empresa.
- **Título:** Calibração de balanças com certificado
- **Descrição:** Consulte a adequação aos seus requisitos

### Sua empresa (`quadrado-1x1-B-sua-empresa`)
- **Texto principal:**
  > Sua empresa precisa calibrar as balanças? Envie uma foto da identificação da balança e informe a cidade do atendimento. Preparamos o orçamento.
  >
  > Serviço de calibração não acreditado pela Cgcre.
- **Título:** Solicite um orçamento
- **Descrição:** Rio Claro e região

### C · O que você recebe (`feed-4x5-C-o-que-voce-recebe`)
- **Texto principal:**
  > O que você recebe na calibração: resultados documentados, informações de incerteza e rastreabilidade e o certificado em PDF. Os resultados apresentados correspondem aos ensaios realizados.
  >
  > Envie uma foto da identificação da balança e solicite um orçamento.
  >
  > Serviço de calibração não acreditado pela Cgcre.
- **Título:** O que você recebe na calibração
- **Descrição:** Certificado em PDF

### Formatos verticais
- **Anúncio (Stories/Reels):** use `stories-9x16-anuncio-*`. O texto principal pode ser o de A ou B. A seta aponta para o botão da plataforma: confira nas prévias do Gerenciador se o botão não cobre o texto.
- **Story do perfil:** use `stories-9x16-organico-link`. Ponha a **figurinha de link** logo abaixo de “Toque no link e solicite um orçamento”, com o link do item 9.
- **Status do WhatsApp:** use `status-whatsapp-9x16`. Quem vê pode responder o status; o telefone está escrito na arte.

---

## 6. Posts do perfil

### Carrossel (5 imagens, na ordem)
> A Balanças.com oferece calibração de balanças com emissão de certificado, para o controle de qualidade da sua empresa. Arraste para entender o serviço.
>
> Para o orçamento, envie uma foto da identificação da balança e informe a cidade do atendimento. Se houver exigências do cliente, do contrato ou da auditoria, conte na conversa.
>
> WhatsApp (19) 3023-9050.
>
> Serviço de calibração não acreditado pela Cgcre. A calibração não substitui a verificação metrológica do IPEM-SP, quando aplicável.
>
> #calibração #balanças #controledequalidade #rioclaro

### Post do feed (A, orgânico)
> Novo serviço na Balanças.com: calibração de balanças com emissão de certificado.
>
> Comparamos as indicações da balança com padrões de referência e registramos os resultados e a incerteza de medição no certificado, em PDF.
>
> Envie uma foto da identificação da balança pelo WhatsApp (19) 3023-9050 e solicite um orçamento.
>
> Serviço de calibração não acreditado pela Cgcre.

### Capa do Facebook
Ao trocar a capa, o Facebook publica um post. Legenda: **“Assistência técnica, calibração de balanças e automação comercial em Rio Claro e região. Conheça a calibração:”** + o link da capa (item 9). Confira no celular e no computador se a foto do perfil e o corte não cobrem o texto.

---

## 7. WhatsApp

### Lista de transmissão (só para clientes que aceitaram receber mensagens, LGPD)
> Olá! Aqui é da Balanças.com.
>
> Agora oferecemos calibração de balanças com emissão de certificado, para o controle de qualidade da sua empresa.
>
> Se tiver interesse, responda com uma foto da identificação da balança e a cidade do atendimento. Se houver alguma exigência do seu cliente, do contrato ou da auditoria, conte também.
>
> Serviço de calibração não acreditado pela Cgcre.

### Resposta para quem chegar pelo anúncio
> Olá! Para preparar o orçamento da calibração, envie uma foto da identificação da balança ou informe marca, modelo e capacidade. Precisamos também da quantidade de equipamentos e da cidade do atendimento. Sua empresa possui alguma exigência específica, como calibração acreditada? Com essas informações, avaliamos o atendimento.

A arte pede só a foto e a cidade; a resposta pronta coleta o resto. Se a resposta for “sim, precisa de calibração acreditada (RBC)”, diga com clareza que o serviço da Balanças.com não é acreditado e que essa exigência pede um laboratório acreditado.

---

## 8. LinkedIn

Para o **feed**, prefira a arte quadrada (`linkedin-1200x1200`); a horizontal serve para o anúncio com link.

- **Texto do post ou anúncio:**
  > A Balanças.com oferece calibração de balanças com emissão de certificado, para o controle de qualidade de empresas de Rio Claro e região.
  >
  > O certificado traz os resultados dos ensaios (erro de indicação, repetibilidade e excentricidade), a incerteza de medição, calculada pelo guia EURAMET cg-18, e as informações de rastreabilidade dos padrões utilizados.
  >
  > Serviço de calibração não acreditado pela Cgcre. Se o seu cliente, contrato ou setor exige calibração acreditada, ela deve ser feita por um laboratório acreditado. Envie os requisitos da sua empresa e avaliamos a adequação.
  >
  > Solicite um orçamento: (link do item 9)
- **Título do anúncio:** Calibração de balanças para o controle de qualidade
- **Botão:** Solicitar orçamento *(ou “Saiba mais”)*
- **Público sugerido (conferir no Gerenciador):** localização Rio Claro e região; cargos ligados à qualidade, à produção e a compras; setores industriais e de logística.

---

## 9. Links finais (prontos para colar)

| Publicação | Link |
|---|---|
| Instagram · feed A (anúncio) | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=pago&utm_campaign=calibracao&utm_content=feed-a-principal` |
| Facebook · feed A (anúncio) | `https://balancass.com/calibracao.html?utm_source=facebook&utm_medium=pago&utm_campaign=calibracao&utm_content=feed-a-principal` |
| Instagram · feed B (anúncio) | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=pago&utm_campaign=calibracao&utm_content=feed-b-auditoria` |
| Facebook · feed B (anúncio) | `https://balancass.com/calibracao.html?utm_source=facebook&utm_medium=pago&utm_campaign=calibracao&utm_content=feed-b-auditoria` |
| Instagram · feed C (anúncio) | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=pago&utm_campaign=calibracao&utm_content=feed-c-o-que-voce-recebe` |
| Facebook · quadrado A (anúncio) | `https://balancass.com/calibracao.html?utm_source=facebook&utm_medium=pago&utm_campaign=calibracao&utm_content=quadrado-a-principal` |
| Facebook · quadrado B (anúncio) | `https://balancass.com/calibracao.html?utm_source=facebook&utm_medium=pago&utm_campaign=calibracao&utm_content=quadrado-b-sua-empresa` |
| Instagram · Stories anúncio principal | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=pago&utm_campaign=calibracao&utm_content=stories-anuncio-principal` |
| Instagram · Stories anúncio auditoria | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=pago&utm_campaign=calibracao&utm_content=stories-anuncio-auditoria` |
| Instagram · carrossel e post (link da bio) | `https://balancass.com/calibracao.html?utm_source=instagram&utm_medium=organico&utm_campaign=calibracao&utm_content=bio` |
| Facebook · capa (post da capa) | `https://balancass.com/calibracao.html?utm_source=facebook&utm_medium=organico&utm_campaign=calibracao&utm_content=capa` |
| LinkedIn · anúncio horizontal | `https://balancass.com/calibracao.html?utm_source=linkedin&utm_medium=pago&utm_campaign=calibracao&utm_content=linkedin-1200x627` |
| LinkedIn · post (quadrado) | `https://balancass.com/calibracao.html?utm_source=linkedin&utm_medium=organico&utm_campaign=calibracao&utm_content=linkedin-1200x1200` |
| Google · Pesquisa | `https://balancass.com/calibracao.html?utm_source=google&utm_medium=cpc&utm_campaign=calibracao&utm_content=pesquisa` |
| Google · Display fixo | `https://balancass.com/calibracao.html?utm_source=google&utm_medium=display&utm_campaign=calibracao&utm_content=display-fixo` |
| Google · Display responsivo | `https://balancass.com/calibracao.html?utm_source=google&utm_medium=display&utm_campaign=calibracao&utm_content=display-responsivo` |
| Anúncio de mensagem (Meta → WhatsApp) | `https://wa.me/551930239050?text=Ol%C3%A1%21%20Vi%20o%20an%C3%BAncio%20da%20calibra%C3%A7%C3%A3o%20de%20balan%C3%A7as%20e%20quero%20um%20or%C3%A7amento.` |
| Story do perfil · figurinha de link | `https://wa.me/551930239050?text=Ol%C3%A1%21%20Vi%20o%20story%20da%20calibra%C3%A7%C3%A3o%20de%20balan%C3%A7as%20e%20quero%20um%20or%C3%A7amento.` |
| Status do WhatsApp | Sem link: quem vê responde o status |

**O que cada medição mostra:**
- As plataformas identificam os próprios anúncios e cliques. Os parâmetros UTM identificam origem e campanha para as ferramentas que os capturem e processem. **O site não captura UTM** (não tem Google Analytics nem Pixel, por decisão de privacidade).
- **Painel do site → Mais procurados:** o código do site conta, sem cookies, as visitas por página e os cliques no WhatsApp e em “Ligar”, por página e pelo lugar do botão. A página nova aparece como **“Calibração (certificado)”**. Confira depois da publicação se os números aparecem.
- Nada disso mede **conversas qualificadas, propostas ou contratos**, e um clique não é uma venda. Anote no WhatsApp, por exemplo com uma etiqueta “Calibração – anúncio”, quantas conversas viraram proposta e quantas viraram serviço.
- Sem Pixel, não há remarketing de quem visitou o site; o remarketing possível é de quem interagiu com o perfil ou com os anúncios.

---

## 10. Google Ads

### Pesquisa
- **Palavras-chave para testar** (correspondência de frase): “calibração de balança”, “calibração de balanças”, “certificado de calibração de balança”, “empresa de calibração de balança”, “calibração de balança rio claro”.
- **Palavras negativas:** comece com as óbvias (curso, apostila, vaga, emprego, como calibrar, modelo de certificado, rodoviária, caminhão). Para **rbc, acreditado e aferição**, olhe primeiro o **relatório de termos de pesquisa**: quem procura “calibração RBC” precisa de serviço acreditado (exclua se aparecer); “aferição” às vezes é usada sem precisão. Um grupo dedicado a “aferição” deve levar à página de assistência e explicar a diferença; **nunca** anuncie a verificação oficial como se fosse feita pela Balanças.com.
- **Títulos** (até 30 caracteres): Calibração de Balanças · Certificado de Calibração · Rio Claro e Região · Solicite um Orçamento · Balanças.com · Calibração com Certificado · Certificado em PDF · Atendimento em Rio Claro · Envie os Dados da Balança · Orçamento pelo WhatsApp · Desde 2010 em Rio Claro
- **Descrições** (até 90 caracteres):
  1. Calibração de balanças com certificado. Consulte o atendimento em Rio Claro e região.
  2. Envie modelo, capacidade e cidade. Solicite um orçamento para a sua empresa.
  3. Serviço não acreditado pela Cgcre. Consulte a adequação aos requisitos da sua empresa.
  4. Resultados documentados, incerteza de medição e rastreabilidade, em certificado PDF.
- Confira as combinações na pré-visualização da plataforma. “Atendimento em Rio Claro” só vale se o atendimento no local for confirmado (item 3).

### Display (tamanhos fixos)
`display-300x250`, `336x280`, `728x90`, `300x600`, `160x600`, `320x100` e `320x50`, todos em JPG abaixo de 150 KB (tamanho certo não garante aprovação nem desempenho). Texto: “Calibração de balanças · Com certificado · Rio Claro e região · Solicite orçamento”.

### Display responsivo
- **Imagens:** `responsivo-paisagem-1200x628.jpg` e `responsivo-quadrado-1200x1200.jpg`, sem texto. O Google **recomenda** fotos em contexto e evitar texto e logos sobrepostos (boa prática, não regra de reprovação automática). As duas são composições ilustrativas: troque por fotos reais do atendimento assim que houver. Confira o tipo de campanha disponível na conta.
- **Logos:** `logo-quadrado-1200x1200.png` e `logo-paisagem-1200x300.png`.
- **Título curto:** Calibração de Balanças · **Título longo:** Calibração de balanças com certificado, em Rio Claro e região
- **Descrições:** as da pesquisa.

---

## 11. Plano de teste (hipóteses, não promessas)

- **A × B são conceitos diferentes** (mensagem, cor, visual e público mudam juntos). Dá para comparar qual conceito traz mais conversas, mas não qual elemento causou a diferença. Para testar um elemento (só o título, por exemplo), mude só ele.
- **Não desligue uma versão cedo** só porque ela tem poucos resultados nos primeiros dias. Decida pelo conjunto: custo por conversa, conversas qualificadas, propostas enviadas e serviços fechados.
- **Canal:** comece pelo canal que combina com a demanda e com a capacidade de atendimento (por exemplo, Pesquisa do Google para quem já procura o serviço, ou Meta com público local) e evite dividir uma verba pequena em muitas campanhas.
- **Verba:** qualquer valor é um ponto de partida, não uma previsão de resultado.
- **Públicos e área:** cidades, raio e interesses precisam ser conferidos no Gerenciador (nem todo interesse existe, nem toda cidade cabe no raio escolhido) e na operação (onde o atendimento no local é viável).

---

## 12. Refazer ou mudar uma arte

Requisitos: Node 18 ou mais novo e o Playwright com o Chromium. Na pasta `fonte/`:

```bash
npm install                 # instala o Playwright da versão do package.json
npx playwright install chromium
node gerar.mjs              # todas (ou: node gerar.mjs feed-4x5-B-auditoria)
```

Tudo o que as artes usam está em `fonte/`: as fontes do site (`fonte/fontes`, licença OFL), o símbolo oficial e a foto da balança (`fonte/img`). A última geração completa fica registrada em `fonte/ultima-geracao.txt`, com as versões de Node, Playwright e Chromium e o resultado de cada arte.

O gerador **para com erro** quando:
- um texto sai da arte;
- um texto encosta em outro texto ou no visual;
- um texto cai sobre a faixa verde (com folga; não vale para o que tem fundo próprio, como rodapé, botões e etiquetas);
- um texto essencial fica abaixo do tamanho mínimo da peça (36 px nas artes de 1080 px);
- uma fonte ou imagem não carrega;
- um banner passa de 150 KB.

Ele **não** mede composição, recortes nem cores, e os textos de dentro do certificado ilustrativo não têm tamanho mínimo; por isso a legenda externa. Olhe as artes também reduzidas a 360 px de largura antes de publicar.

Textos e posições: `fonte/artes.mjs`. Peças (logo, balança e peso, certificado de exemplo, legenda, rodapé, ressalva): `fonte/pecas.mjs`. Estilo: `fonte/banners.css`. Cada arte também vira um HTML em `fonte/html/`, que abre no navegador.
