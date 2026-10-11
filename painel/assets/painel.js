/* =====================================================================
   Painel Balanças.com — comportamento (melhoria progressiva)
   Sem este arquivo o painel continua funcionando (formulários normais).
   Com ele: ações rápidas sem recarregar, busca instantânea, confirmação
   em janela, prévia e redução da foto no próprio celular antes de enviar.
   ===================================================================== */
(function () {
  "use strict";
  document.documentElement.classList.add("js");

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ------------------------------------------------------------------
     Aviso rápido (toast)
     ------------------------------------------------------------------ */
  var toastEl = $("[data-toast]"), toastT = 0;
  function svgIcone(nome) {
    var ic = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    ic.setAttribute("class", "i"); ic.setAttribute("aria-hidden", "true");
    var use = document.createElementNS("http://www.w3.org/2000/svg", "use");
    use.setAttribute("href", "#i-" + nome);
    ic.appendChild(use);
    return ic;
  }
  // opts: { link: <a> a copiar, tempo: ms (0 = até fechar) }
  function toast(msg, erro, opts) {
    if (!toastEl) return;
    opts = opts || {};
    toastEl.className = "toast" + (erro ? " toast--erro" : "") + (opts.link ? " toast--acao" : "");
    toastEl.textContent = "";
    var p = document.createElement("span");
    p.textContent = msg;
    if (opts.link) { p.appendChild(document.createTextNode(" ")); p.appendChild(opts.link); }
    var x = document.createElement("button");
    x.type = "button"; x.className = "toast__x"; x.setAttribute("aria-label", "Fechar aviso");
    x.appendChild(svgIcone("x"));
    x.addEventListener("click", function () { toastEl.hidden = true; clearTimeout(toastT); });
    toastEl.appendChild(svgIcone(erro ? "alert" : "check")); toastEl.appendChild(p); toastEl.appendChild(x);
    toastEl.hidden = false;
    clearTimeout(toastT);
    var tempo = opts.tempo != null ? opts.tempo : (erro ? 9000 : (msg.length > 90 ? 8000 : 4500));
    if (tempo) toastT = setTimeout(function () { toastEl.hidden = true; }, tempo);
  }

  /* ------------------------------------------------------------------
     Confirmação (excluir, restaurar) em janela própria
     ------------------------------------------------------------------ */
  var dlg = $("[data-dialogo]");
  function confirmar(texto, ok, titulo) {
    return new Promise(function (resolve) {
      if (!dlg || typeof dlg.showModal !== "function") { resolve(window.confirm(texto)); return; }
      $("[data-dialogo-txt]", dlg).textContent = texto;
      $("[data-dialogo-tit]", dlg).textContent = titulo || "Tem certeza?";
      $("[data-dialogo-ok]", dlg).textContent = ok || "Confirmar";
      dlg.returnValue = "";
      dlg.addEventListener("close", function fim() {
        dlg.removeEventListener("close", fim);
        resolve(dlg.returnValue === "sim");
      });
      dlg.showModal();
      $("[data-dialogo-ok]", dlg).focus();
    });
  }
  // data-confirmar-se="0": só pede confirmação quando o valor enviado é esse (ex.: esconder, não mostrar)
  function precisaConfirmar(f) {
    if (!f.matches || !f.matches("[data-confirmar]") || f.dataset.confirmado === "1") return false;
    if (f.dataset.confirmarSe != null) { var v = $("input[name=valor]", f); return !!v && v.value === f.dataset.confirmarSe; }
    return true;
  }
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (!precisaConfirmar(f)) return;
    e.preventDefault();
    confirmar(f.dataset.confirmar, f.dataset.confirmarOk, f.dataset.confirmarTit).then(function (sim) {
      if (!sim) return;
      if (f.matches("[data-rapido]") && f.requestSubmit) {
        f.dataset.confirmado = "1";
        f.requestSubmit();
        f.dataset.confirmado = "";
        return;
      }
      var h = document.createElement("input");
      h.type = "hidden"; h.name = "confirmado"; h.value = "1";
      f.appendChild(h);
      f.dataset.confirmado = "1";
      var b = $("button[type=submit]", f);
      if (b) b.setAttribute("aria-busy", "true");
      f.submit();
    });
  });

  /* ------------------------------------------------------------------
     Sessão vencida: pede a senha numa janela e continua de onde parou
     ------------------------------------------------------------------ */
  var relogin = $("[data-relogin]");
  function atualizarCsrf(tk) {
    if (!tk) return;
    $$("input[name=csrf]").forEach(function (i) { i.value = tk; });
  }
  var loginAberto = null;
  // motivo: por que a sessão terminou (senha redefinida, acesso desativado…), vindo do servidor
  function pedirLogin(csrf, motivo) {
    if (!relogin || typeof relogin.showModal !== "function") {
      window.location.href = "index.php?a=entrar";
      return Promise.resolve(false);
    }
    if (loginAberto) return loginAberto;
    loginAberto = new Promise(function (resolve) {
      var form = $("[data-relogin-form]", relogin), erro = $("[data-relogin-erro]", relogin);
      var senha = $("#rl-senha", relogin), usuario = $("#rl-usuario", relogin), ok = $("[data-relogin-ok]", relogin);
      var token = csrf || "";
      var txt = $("[data-relogin-motivo]", relogin);
      if (txt) {
        if (!txt.dataset.padrao) txt.dataset.padrao = txt.textContent;
        txt.textContent = typeof motivo === "string" && motivo ? motivo + " Nada do que está nesta tela se perde." : txt.dataset.padrao;
      }
      erro.hidden = true; senha.value = "";
      var fim = function (r) {
        form.removeEventListener("submit", enviarLogin);
        relogin.removeEventListener("cancel", cancelar);
        $("[data-relogin-cancelar]", relogin).removeEventListener("click", cancelar);
        if (relogin.open) relogin.close();
        loginAberto = null;
        resolve(r);
      };
      var cancelar = function (e) { if (e) e.preventDefault(); fim(false); };
      var enviarLogin = function (e) {
        e.preventDefault();
        if (!usuario.value.trim() || !senha.value) { erro.hidden = false; $("p", erro).textContent = "Digite o usuário e a senha."; return; }
        var fd = new FormData();
        fd.append("csrf", token); fd.append("usuario", usuario.value.trim()); fd.append("senha", senha.value);
        ok.setAttribute("aria-busy", "true");
        fetch("index.php?a=entrar", { method: "POST", body: fd, headers: { "X-Requested-With": "fetch", "Accept": "application/json" }, credentials: "same-origin", cache: "no-store" })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            ok.removeAttribute("aria-busy");
            if (d && d.ok) { atualizarCsrf(d.csrf); toast("Pronto, você entrou de novo."); fim(true); return; }
            if (d && d.csrf) token = d.csrf;
            erro.hidden = false; $("p", erro).textContent = (d && d.msg) || "Não foi possível entrar. Tente de novo.";
            senha.value = ""; senha.focus();
          }, function () {
            ok.removeAttribute("aria-busy");
            erro.hidden = false; $("p", erro).textContent = "Sem conexão com a internet. Tente de novo.";
          });
      };
      form.addEventListener("submit", enviarLogin);
      relogin.addEventListener("cancel", cancelar);
      $("[data-relogin-cancelar]", relogin).addEventListener("click", cancelar);
      relogin.showModal();
      (usuario.value ? senha : usuario).focus();
    });
    return loginAberto;
  }
  // Confere a sessão antes de um envio grande (formulário do produto). true = pode enviar.
  function sessaoOk() {
    return fetch("index.php?a=sessao", { headers: { "X-Requested-With": "fetch", "Accept": "application/json" }, credentials: "same-origin", cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.logado) { atualizarCsrf(d.csrf); return true; }
        return pedirLogin(d && d.csrf, d && d.motivo);
      }, function () { return true; });   // sem resposta: o envio normal segue (o servidor confere)
  }

  /* ------------------------------------------------------------------
     Envio pelo fetch (ações rápidas e ordem) — resposta em JSON
     ------------------------------------------------------------------ */
  function enviar(form, segunda) {
    return fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { "X-Requested-With": "fetch", "Accept": "application/json" },
      credentials: "same-origin",
      cache: "no-store"
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, msg: "Resposta inesperada do servidor. Recarregue a página." }; })
        .then(function (d) {
          if (d && d.login) {
            if (segunda) throw new Error("login");
            return pedirLogin(d.csrf, d.motivo).then(function (entrou) {
              if (!entrou) throw new Error("login");
              return enviar(form, true);
            });
          }
          if (!r.ok || !d || !d.ok) throw new Error((d && d.msg) || "Não foi possível salvar. Tente de novo.");
          return d;
        });
    }, function () { throw new Error("Sem conexão com a internet. Tente de novo."); });
  }

  // Selos "Destaque · página inicial / fora da página inicial" (o servidor manda a lista atual)
  function aplicarHome(ids) {
    if (!ids) return;
    var dica = $(".dica[data-capa]"), capa = dica ? dica.getAttribute("data-capa") : "";
    $$(".item").forEach(function (li) {
      var s = $("[data-selo-destaque]", li);
      if (!s || li.dataset.id === capa) return;
      var dentro = ids.indexOf(li.dataset.id) >= 0;
      s.textContent = dentro ? "Destaque · página inicial" : "Destaque · fora da página inicial";
      s.classList.toggle("is-fora", !dentro);
    });
    $$("[data-n=home]").forEach(function (b) { b.textContent = ids.length; });
  }

  function atualizarContadores() {
    var itens = $$(".item");
    var n = { total: itens.length, site: 0, destaque: 0 };
    itens.forEach(function (li) {
      if (li.dataset.visivel === "1") { n.site++; if (li.dataset.destaque === "1") n.destaque++; }
    });
    $$("[data-n]").forEach(function (b) { if (n[b.dataset.n] != null) b.textContent = n[b.dataset.n]; });
  }

  function aplicarRapido(li, campo, valor) {
    if (campo === "estoque") {
      var lab = $("[data-estoque]", li);
      if (lab) lab.className = "estoque estoque--" + valor;
      return;
    }
    var on = valor === "1";
    var form = $(".rapido--" + campo, li);
    var bt = form && $(".tg", form);
    if (bt) bt.setAttribute("aria-pressed", on ? "true" : "false");
    var inp = form && $("input[name=valor]", form);
    if (inp) inp.value = on ? "0" : "1";
    if (campo === "destaque") {
      li.dataset.destaque = on ? "1" : "0";
      $("[data-selo-destaque]", li).hidden = !on;
    } else {
      li.dataset.visivel = on ? "1" : "0";
      li.classList.toggle("is-oculto", !on);
      $("[data-selo-oculto]", li).hidden = on;
    }
    atualizarContadores();
  }

  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (!f.matches || !f.matches("[data-rapido]")) return;
    e.preventDefault();
    if (precisaConfirmar(f)) return;   // a janela de confirmação manda de novo
    var li = f.closest(".item");
    var alvo = $(".tg", f) || $("[data-estoque]", f);
    if (alvo) alvo.setAttribute("aria-busy", "true");
    enviar(f).then(function (d) {
      aplicarRapido(li, d.campo, d.valor);
      aplicarHome(d.home);
      var sel = $("select", f);
      if (sel) sel.dataset.valor = d.valor;
      toast(d.msg);
    }).catch(function (err) {
      var sel = $("select", f);
      if (sel && sel.dataset.valor) sel.value = sel.dataset.valor;
      if (err.message !== "login") toast(err.message, true);
    }).then(function () {
      if (alvo) alvo.removeAttribute("aria-busy");
    });
  });

  // Estoque: troca na hora, sem botão
  $$("select[data-auto]").forEach(function (sel) {
    sel.dataset.valor = sel.value;
    sel.addEventListener("change", function () {
      var f = sel.form;
      if (f.requestSubmit) f.requestSubmit(); else f.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
    });
  });

  // Ordem: troca de lugar com o vizinho da mesma categoria
  function acertarSetas(lista) {
    var itens = $$(".item", lista);
    itens.forEach(function (li, i) {
      var c = $("[data-mover-cima]", li), b = $("[data-mover-baixo]", li);
      if (c) c.disabled = i === 0;
      if (b) b.disabled = i === itens.length - 1;
    });
  }
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (!f.matches || !f.matches("[data-mover]")) return;
    e.preventDefault();
    var bt = $("button", f), li = f.closest(".item");
    bt.setAttribute("aria-busy", "true");
    enviar(f).then(function (d) {
      var viz = document.getElementById("p-" + d.com);
      if (viz && viz.parentNode === li.parentNode) {
        if (d.dir === "cima") li.parentNode.insertBefore(li, viz);
        else li.parentNode.insertBefore(viz, li);
      }
      acertarSetas(li.parentNode);
      li.classList.add("is-movendo");
      setTimeout(function () { li.classList.remove("is-movendo"); }, 900);
      if (!bt.disabled) bt.focus({ preventScroll: true });
      else { var outro = $(d.dir === "cima" ? "[data-mover-baixo]" : "[data-mover-cima]", li); if (outro && !outro.disabled) outro.focus({ preventScroll: true }); }
      if (li.scrollIntoView) li.scrollIntoView({ block: "nearest", behavior: "smooth" });
      aplicarHome(d.home);
      toast(d.msg);
    }).catch(function (err) {
      if (err.message !== "login") toast(err.message, true);
    }).then(function () { bt.removeAttribute("aria-busy"); });
  });

  /* ------------------------------------------------------------------
     Busca e filtros instantâneos na lista
     ------------------------------------------------------------------ */
  var filtros = $("[data-filtros]");
  if (filtros) {
    var busca = $("[data-busca]", filtros), ver = $("[data-ver]", filtros);
    var catAtual = (function () { var h = $("[data-cat-atual]", filtros); return h ? h.value : ""; })();
    var semAcento = function (s) {
      return String(s || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase()
        .replace(/&/g, " e ").replace(/\+/g, " plus ");
    };
    var filtrar = function () {
      var termos = semAcento(busca.value).split(/\s+/).filter(Boolean);
      var modo = ver.value, total = 0;
      $$(".grupo").forEach(function (g) {
        var n = 0;
        $$(".item", g).forEach(function (li) {
          var ok = (!catAtual || li.dataset.cat === catAtual) &&
            (modo !== "site" || li.dataset.visivel === "1") &&
            (modo !== "ocultos" || li.dataset.visivel === "0") &&
            (modo !== "destaques" || li.dataset.destaque === "1") &&
            termos.every(function (t) { return li.dataset.busca.indexOf(t) !== -1; });
          li.hidden = !ok;
          if (ok) n++;
        });
        g.hidden = n === 0;
        total += n;
      });
      var todos = $$(".item").length;
      $("[data-contagem]").textContent = total === todos ? "Mostrando todos os " + todos + " produtos." :
        total + (total === 1 ? " produto encontrado." : " produtos encontrados.");
      $("[data-vazio]").hidden = total > 0;
      try {
        var u = new URL(window.location.href);
        ["q", "cat", "ver"].forEach(function (k) { u.searchParams.delete(k); });
        if (busca.value.trim()) u.searchParams.set("q", busca.value.trim());
        if (catAtual) u.searchParams.set("cat", catAtual);
        if (modo) u.searchParams.set("ver", modo);
        history.replaceState(null, "", u.pathname + u.search + u.hash);
      } catch (err) { /* navegador antigo: tudo bem */ }
    };
    busca.addEventListener("input", filtrar);
    ver.addEventListener("change", filtrar);
    $$("[data-chip]", filtros).forEach(function (b) {
      b.addEventListener("click", function (e) {
        e.preventDefault();
        catAtual = b.dataset.chip;
        $$("[data-chip]", filtros).forEach(function (x) { x.setAttribute("aria-pressed", x === b ? "true" : "false"); });
        filtrar();
      });
    });
    filtros.addEventListener("submit", function (e) { e.preventDefault(); filtrar(); busca.blur(); });
  }

  /* ------------------------------------------------------------------
     Formulário do produto
     ------------------------------------------------------------------ */
  var fp = $("[data-form-produto]");
  if (fp) {
    var sujo = false, enviando = false, fotoPronta = null;
    fp.addEventListener("input", function () { sujo = true; });
    fp.addEventListener("change", function () { sujo = true; });
    window.addEventListener("beforeunload", function (e) {
      if (sujo && !enviando) { e.preventDefault(); e.returnValue = ""; }
    });

    // Categoria → só as subcategorias dela (o iPhone ignora opções escondidas, então recria a lista)
    var selCat = $("[data-categoria]", fp), selSub = $("[data-subcategoria]", fp);
    var mapa = {};
    $$("optgroup", selSub).forEach(function (g) {
      mapa[g.dataset.cat] = $$("option", g).map(function (o) { return o.value; });
    });
    var montarSub = function () {
      var atual = selSub.value, lista = mapa[selCat.value] || [];
      selSub.textContent = "";
      var vazio = document.createElement("option");
      vazio.value = ""; vazio.textContent = selCat.value ? "Escolha…" : "Escolha a categoria antes";
      selSub.appendChild(vazio);
      lista.forEach(function (v) {
        var o = document.createElement("option");
        o.value = v; o.textContent = v;
        if (v === atual) o.selected = true;
        selSub.appendChild(o);
      });
      if (lista.length === 1) selSub.value = lista[0];
    };
    montarSub();
    selCat.addEventListener("change", montarSub);

    // Código (id) criado sozinho a partir de marca + nome
    var slugEl = $("[data-slug]", fp);
    var slug = function (s) {
      return String(s).normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase()
        .replace(/[ºª°]/g, "o").replace(/&/g, " e ").replace(/\+/g, " plus ")
        .replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 80).replace(/-+$/, "");
    };
    var slugAuto = $("[data-slug-auto-campo]", fp);
    var marcarAuto = function (v) { slugEl.dataset.slugAuto = v; if (slugAuto) slugAuto.value = v; };
    if (slugEl) {
      slugEl.addEventListener("input", function () { marcarAuto(slugEl.value ? "0" : "1"); });
      $$("[data-slug-origem]", fp).forEach(function (el) {
        el.addEventListener("input", function () {
          if (slugEl.dataset.slugAuto !== "1") return;
          slugEl.value = slug(($("#f-marca").value || "") + " " + ($("#f-nome").value || ""));
        });
      });
    }

    // Contador de caracteres
    $$("[data-contador]", fp).forEach(function (t) {
      var max = +t.getAttribute("maxlength"), p = document.createElement("p");
      p.className = "contador";
      p.setAttribute("aria-hidden", "true");
      t.insertAdjacentElement("afterend", p);
      var at = function () {
        p.textContent = t.value.length + "/" + max;
        p.classList.toggle("is-perto", t.value.length > max * 0.9);
      };
      t.addEventListener("input", at);
      at();
    });

    // Ficha técnica: adicionar/remover linhas
    var specs = $("[data-specs]", fp), modelo = $("[data-spec-modelo]", fp), add = $("[data-spec-adicionar]", fp);
    var renumerar = function () {
      $$(".spec", specs).forEach(function (li, i) {
        var n = i + 1, ins = $$("input", li), rm = $("[data-spec-remover]", li);
        ins[0].setAttribute("aria-label", "Característica " + n);
        ins[1].setAttribute("aria-label", "Valor " + n);
        rm.setAttribute("aria-label", "Remover linha " + n);
        rm.hidden = false;
      });
      add.hidden = $$(".spec", specs).length >= 24;
    };
    if (specs && modelo && add) {
      add.hidden = false;
      add.addEventListener("click", function () {
        var novo = modelo.content.firstElementChild.cloneNode(true);
        specs.appendChild(novo);
        renumerar();
        $("input", novo).focus();
        sujo = true;
      });
      specs.addEventListener("click", function (e) {
        var rm = e.target.closest("[data-spec-remover]");
        if (!rm) return;
        var li = rm.closest(".spec");
        if ($$(".spec", specs).length > 1) {
          var prox = li.nextElementSibling || li.previousElementSibling;
          li.remove();
          renumerar();
          if (prox) $("input", prox).focus(); else add.focus();
        } else {
          $$("input", li).forEach(function (i) { i.value = ""; });
          $("input", li).focus();
        }
        sujo = true;
      });
      renumerar();
    }

    // Foto: prévia no formato do cartão + redução no próprio aparelho (fotos de celular têm 3–12 MB)
    var fInput = $("[data-foto-input]", fp), fBox = $("[data-foto]", fp), fImg = $("[data-foto-previa]", fp);
    var fVazio = $("[data-foto-vazio]", fp), fRot = $("[data-foto-rotulo]", fp), fInfo = $("[data-foto-info]", fp);
    var MAX = +fInput.getAttribute("data-max-bytes") || 8 * 1024 * 1024, MIN = +fInput.getAttribute("data-min") || 300, LADO = 1800;
    var estado = document.createElement("p");
    estado.className = "foto-estado"; estado.setAttribute("aria-live", "polite"); estado.hidden = true;
    fInfo.insertAdjacentElement("beforebegin", estado);
    var dica = document.createElement("p");
    dica.className = "foto-dica"; dica.hidden = true;
    estado.insertAdjacentElement("afterend", dica);
    var kb = function (b) { return b > 1048576 ? (b / 1048576).toFixed(1).replace(".", ",") + " MB" : Math.round(b / 1024) + " KB"; };
    var mostrarEstado = function (txt, erro) { estado.hidden = !txt; estado.textContent = txt || ""; estado.classList.toggle("is-erro", !!erro); };
    var mostrarDica = function (txt) { dica.hidden = !txt; dica.textContent = txt || ""; };

    var formatoRuim = function (file) {
      var nome = (file.name || "").toLowerCase(), tipo = (file.type || "").toLowerCase();
      if (/hei[cf]/.test(tipo) || /\.hei[cf]$/.test(nome)) return "Seu celular salvou a foto em HEIC, formato que o site não usa. Na câmera, desligue a opção “alta eficiência” (HEIF) ou compartilhe a foto como JPG e escolha de novo.";
      if (tipo === "application/pdf" || /\.pdf$/.test(nome)) return "Esse arquivo é um PDF, não uma foto. Salve a imagem do produto como JPG (ou tire um print) e escolha de novo.";
      if (!/^image\/(jpeg|png|webp)$/.test(tipo)) return "Formato não aceito (" + (file.type || nome.split(".").pop() || "desconhecido") + "). Use foto JPG, PNG ou WebP: uma foto da câmera ou salva do site do fabricante.";
      return "";
    };
    var carregar = function (file) {
      return new Promise(function (ok, falha) {
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () { ok({ img: img, url: url }); };
        img.onerror = function () { URL.revokeObjectURL(url); falha(new Error("Não consegui abrir essa foto. Tente outra ou salve como JPG.")); };
        img.src = url;
      });
    };
    var transparente = function (ctx, w, h) {
      try {
        var d = ctx.getImageData(0, 0, w, h).data, passo = Math.max(4, Math.floor(d.length / 4 / 60000)) * 4;
        for (var i = 3; i < d.length; i += passo) if (d[i] < 250) return true;
      } catch (e) { /* sem acesso aos pixels */ }
      return false;
    };
    // Fundo: a borda da foto é branca (ou transparente)? O servidor só recorta borda branca.
    var fundoBranco = function (img, w, h) {
      try {
        var f = Math.min(1, 160 / Math.max(w, h)), cw = Math.max(8, Math.round(w * f)), ch = Math.max(8, Math.round(h * f));
        var c = document.createElement("canvas"); c.width = cw; c.height = ch;
        var x = c.getContext("2d"); x.drawImage(img, 0, 0, cw, ch);
        var d = x.getImageData(0, 0, cw, ch).data, m = Math.max(1, Math.round(Math.min(cw, ch) * 0.05)), borda = 0, bom = 0;
        for (var y = 0; y < ch; y++) for (var xx = 0; xx < cw; xx++) {
          if (y >= m && y < ch - m && xx >= m && xx < cw - m) continue;
          var i = (y * cw + xx) * 4;
          borda++;
          if (d[i + 3] < 200 || Math.min(d[i], d[i + 1], d[i + 2]) >= 226) bom++;
        }
        return bom / borda > 0.7;
      } catch (e) { return true; }
    };
    var paraBlob = function (canvas, tipo, q) {
      return new Promise(function (ok) { canvas.toBlob(ok, tipo, q); });
    };
    var preparar = function (file) {
      var ruim = formatoRuim(file);
      if (ruim) return Promise.reject(new Error(ruim));
      if (file.size > 40 * 1024 * 1024) return Promise.reject(new Error("Foto grande demais (" + kb(file.size) + "). Use uma foto menor."));
      return carregar(file).then(function (r) {
        var w = r.img.naturalWidth, h = r.img.naturalHeight;
        if (w < MIN || h < MIN) {
          URL.revokeObjectURL(r.url);
          throw new Error("A foto é pequena demais (" + w + " × " + h + " px). Use uma com pelo menos " + MIN + " px de largura e de altura.");
        }
        var info = { emPe: h > w * 1.08, branco: fundoBranco(r.img, w, h) };
        var precisa = Math.max(w, h) > LADO || file.size > MAX || (file.type === "image/jpeg" && file.size > 2.5 * 1048576);
        if (!precisa) return { file: file, url: r.url, w: w, h: h, info: info };
        var f = Math.min(1, LADO / Math.max(w, h)), nw = Math.round(w * f), nh = Math.round(h * f);
        var c = document.createElement("canvas");
        c.width = nw; c.height = nh;
        var ctx = c.getContext("2d");
        ctx.imageSmoothingQuality = "high";
        ctx.drawImage(r.img, 0, 0, nw, nh);
        var alfa = file.type !== "image/jpeg" && transparente(ctx, nw, nh);
        var tipo = alfa ? "image/png" : "image/jpeg";
        return paraBlob(c, tipo, 0.9).then(function (blob) {
          if (blob && blob.size > MAX && tipo === "image/jpeg") return paraBlob(c, tipo, 0.75);
          return blob;
        }).then(function (blob) {
          // Reduzir não diminuiu o arquivo (PNG): manda o original, que já cabe (o servidor reduz)
          if ((!blob || blob.size >= file.size) && file.size <= MAX) return { file: file, url: r.url, w: w, h: h, info: info };
          URL.revokeObjectURL(r.url);
          if (!blob || blob.size > MAX) throw new Error("Não consegui reduzir a foto. Tente outra menor que 8 MB.");
          var nome = "foto." + (tipo === "image/png" ? "png" : "jpg");
          var novo = new File([blob], nome, { type: tipo, lastModified: Date.now() });
          return { file: novo, url: URL.createObjectURL(novo), w: w, h: h, nw: nw, nh: nh, antes: file.size, info: info };
        });
      });
    };
    var trocarArquivo = function (file) {
      try {
        var dt = new DataTransfer();
        dt.items.add(file);
        fInput.files = dt.files;
        return true;
      } catch (e) { return false; }
    };
    var aoEscolher = function (file) {
      if (!file) return;
      mostrarEstado("Preparando a foto…");
      mostrarDica("");
      fBox.setAttribute("aria-busy", "true");
      fotoPronta = preparar(file).then(function (r) {
        if (fImg.src && fImg.src.indexOf("blob:") === 0) URL.revokeObjectURL(fImg.src);
        fImg.src = r.url; fImg.alt = "Prévia da foto nova"; fImg.hidden = false;
        if (fVazio) fVazio.hidden = true;
        fBox.classList.remove("foto--vazia");
        fRot.textContent = "Trocar foto";
        var trocou = r.file !== file && trocarArquivo(r.file);
        if (r.file !== file && !trocou && file.size > MAX) throw new Error("Este navegador não conseguiu reduzir a foto. Use uma foto menor que 8 MB.");
        mostrarEstado(trocou ? "Foto nova pronta: " + (r.file.size < r.antes ? "reduzida" : "ajustada") + " de " + r.w + "×" + r.h + " (" + kb(r.antes) + ") para " + r.nw + "×" + r.nh + " (" + kb(r.file.size) + "). Salve para publicar."
                             : "Foto nova pronta (" + r.w + "×" + r.h + ", " + kb(file.size) + "). Salve para publicar.");
        var motivos = [];
        if (r.info.emPe) motivos.push("está em pé");
        if (!r.info.branco) motivos.push("o fundo não é branco (ele aparece no site)");
        mostrarDica(motivos.length ? "Atenção: no site ela vai aparecer pequena, porque " + motivos.join(" e ") + ". Para ficar como as outras: celular deitado, produto ocupando quase toda a foto, fundo branco ou liso. Pode salvar assim mesmo." : "");
        fBox.classList.toggle("is-pequena", motivos.length > 0);
        fInput.removeAttribute("aria-invalid");
      }).catch(function (err) {
        fInput.value = "";
        mostrarEstado(err.message, true);
      }).then(function () {
        fBox.removeAttribute("aria-busy");
        fotoPronta = null;
      });
    };
    fInput.addEventListener("change", function () { aoEscolher(fInput.files && fInput.files[0]); });
    // Arrastar e soltar (computador)
    ["dragenter", "dragover"].forEach(function (t) {
      fBox.addEventListener(t, function (e) { e.preventDefault(); fBox.classList.add("is-arrastando"); });
    });
    ["dragleave", "drop"].forEach(function (t) {
      fBox.addEventListener(t, function () { fBox.classList.remove("is-arrastando"); });
    });
    fBox.addEventListener("drop", function (e) {
      e.preventDefault();
      var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (file && trocarArquivo(file)) { sujo = true; aoEscolher(file); }
    });

    // Produto da capa: esconder ou mudar o código pede confirmação
    var confirmarCapa = function () {
      var capa = fp.getAttribute("data-capa");
      if (!capa) return Promise.resolve(true);
      var vis = $("input[name=visivel]", fp), idEl = $("[data-slug]", fp);
      var motivos = [];
      if (vis && !vis.checked) motivos.push("esconder");
      if (idEl && idEl.value.trim() !== capa) motivos.push("mudar o código");
      if (!motivos.length) return Promise.resolve(true);
      return confirmar("Este é o produto da capa do site. Se " + motivos.join(" e ") + ", a foto da capa continua lá, mas o link dela vai abrir o catálogo com o aviso “produto fora do catálogo”.", "Salvar assim", "Produto da capa");
    };
    fp.addEventListener("submit", function (e) {
      var bt = e.submitter;
      if (fotoPronta) {
        e.preventDefault();
        fotoPronta.then(function () { if (fp.requestSubmit) fp.requestSubmit(bt || undefined); else { enviando = true; fp.submit(); } });
        return;
      }
      if (fp.dataset.liberado !== "1") {
        // Antes de enviar: confirma o produto da capa e confere se a sessão ainda vale
        e.preventDefault();
        confirmarCapa().then(function (sim) { return sim ? sessaoOk() : false; }).then(function (ok) {
          if (!ok) return;
          fp.dataset.liberado = "1";
          if (fp.requestSubmit) fp.requestSubmit(bt || undefined); else { enviando = true; salvarRascunho(); fp.submit(); }
          fp.dataset.liberado = "";
        });
        return;
      }
      enviando = true;
      salvarRascunho();
    });

    /* Rascunho: o que é digitado fica guardado neste aparelho até o produto ser salvo
       (sessão vencida, aba fechada, bateria…). A foto não entra (o navegador não deixa). */
    var CHAVE = fp.getAttribute("data-rascunho") ? "bc-painel-rascunho:" + fp.getAttribute("data-rascunho") : "";
    var NAO = { csrf: 1, MAX_FILE_SIZE: 1, foto: 1 };
    function lerCampos() {
      var out = {};
      $$("input, select, textarea", fp).forEach(function (el) {
        if (!el.name || NAO[el.name] || el.type === "file" || (el.type === "hidden" && el.name !== "id_auto")) return;
        if (el.type === "radio") { if (el.checked) out[el.name] = [el.value]; return; }
        (out[el.name] = out[el.name] || []).push(el.type === "checkbox" ? (el.checked ? el.value : "") : el.value);
      });
      return out;
    }
    function normal(c) {
      var n = JSON.parse(JSON.stringify(c || {})), a = n.spec_nome || [], b = n.spec_valor || [], na = [], nb = [];
      for (var i = 0; i < Math.max(a.length, b.length); i++) if ((a[i] || "").trim() || (b[i] || "").trim()) { na.push(a[i] || ""); nb.push(b[i] || ""); }
      n.spec_nome = na; n.spec_valor = nb;
      return JSON.stringify(Object.keys(n).sort().map(function (k) { return [k, n[k]]; }));
    }
    function lerRascunho() {
      try { var d = JSON.parse(localStorage.getItem(CHAVE) || "null"); return d && d.campos ? d : null; } catch (e) { return null; }
    }
    var rascT = 0;
    function salvarRascunho() {
      if (!CHAVE || !sujo) return;
      clearTimeout(rascT);
      try { localStorage.setItem(CHAVE, JSON.stringify({ t: Date.now(), campos: lerCampos() })); } catch (e) { /* sem espaço ou bloqueado: tudo bem */ }
    }
    function apagarRascunho() { try { localStorage.removeItem(CHAVE); } catch (e) { /* ok */ } }
    function aplicarRascunho(c) {
      var sp = $("[data-specs]", fp), mod = $("[data-spec-modelo]", fp);
      var nSpecs = Math.max((c.spec_nome || []).length, 1);
      if (sp && mod) {
        while ($$(".spec", sp).length < nSpecs) sp.appendChild(mod.content.firstElementChild.cloneNode(true));
        $$(".spec", sp).forEach(function (li, i) { if (i >= nSpecs) li.remove(); });
        if (typeof renumerar === "function" && add) renumerar();
      }
      if (c.categoria) { selCat.value = c.categoria[0]; montarSub(); }
      Object.keys(c).forEach(function (nome) {
        var els = $$("[name=\"" + nome.replace(/"/g, "") + "\"]", fp), vals = c[nome];
        els.forEach(function (el, i) {
          if (el.type === "radio") el.checked = vals.indexOf(el.value) >= 0;
          else if (el.type === "checkbox") el.checked = !!vals[i];
          else if (i < vals.length && el.type !== "file") el.value = vals[i];
        });
      });
      if (slugEl && c.id_auto) marcarAuto(c.id_auto[0] === "1" ? "1" : "0");
      $$("[data-contador]", fp).forEach(function (x) { x.dispatchEvent(new Event("input")); });
    }
    if (CHAVE) {
      fp.addEventListener("input", function () { clearTimeout(rascT); rascT = setTimeout(salvarRascunho, 400); });
      fp.addEventListener("change", function () { clearTimeout(rascT); rascT = setTimeout(salvarRascunho, 400); });
      $$(".barra-salvar a", fp).forEach(function (a) { a.addEventListener("click", apagarRascunho); });
      var r = lerRascunho();
      if (r && Date.now() - r.t > 14 * 864e5) { apagarRascunho(); r = null; }
      if (r && normal(r.campos) !== normal(lerCampos())) {
        var bar = document.createElement("div"), d = new Date(r.t);
        bar.className = "aviso aviso--info rascunho"; bar.setAttribute("role", "status");
        var txt = document.createElement("p");
        txt.textContent = "Há um rascunho deste produto, de " + ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) +
          " às " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ", que não foi salvo.";
        var b1 = document.createElement("button"), b2 = document.createElement("button");
        b1.type = b2.type = "button";
        b1.className = "btn btn--verde btn--p"; b1.textContent = "Recuperar";
        b2.className = "btn btn--linha btn--p"; b2.textContent = "Descartar";
        var bts = document.createElement("div"); bts.className = "rascunho__bts";
        bts.appendChild(b1); bts.appendChild(b2);
        bar.appendChild(svgIcone("restore")); bar.appendChild(txt); bar.appendChild(bts);
        fp.parentNode.insertBefore(bar, fp);
        b1.addEventListener("click", function () {
          aplicarRascunho(r.campos);
          sujo = true;
          bar.remove();
          toast("Rascunho recuperado. Confira" + (fImg.hidden ? ", escolha a foto" : "") + " e salve.", false, { tempo: 7000 });
          var n = $("#f-nome"); if (n) n.focus();
        });
        b2.addEventListener("click", function () { apagarRascunho(); bar.remove(); });
      }
    }

    var erros = $("[data-erros]");
    if (erros) { erros.focus(); }
  }

  /* ------------------------------------------------------------------
     Clientes: ordem (troca com o vizinho) e formulário com prévia do logo
     ------------------------------------------------------------------ */
  // Depois de mudar a ordem: posição, campos n= dos formulários, links e setas
  function numerarClientes(lista) {
    var itens = Array.prototype.filter.call(lista.children, function (x) { return x.matches(".cli"); });
    itens.forEach(function (li, i) {
      var n = String(i + 1);
      li.id = "c-" + n;
      var pos = $("[data-pos]", li);
      if (pos) pos.textContent = n + "º no site";
      $$("input[name=n]", li).forEach(function (inp) { inp.value = n; });
      $$("a[href*='n=']", li).forEach(function (a) { a.setAttribute("href", a.getAttribute("href").replace(/([?&]n=)\d+/, "$1" + n)); });
      var c = $("[data-mover-cima]", li), b = $("[data-mover-baixo]", li);
      if (c) c.disabled = i === 0;
      if (b) b.disabled = i === itens.length - 1;
    });
  }
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (!f.matches || !f.matches("[data-mover-cliente]")) return;
    e.preventDefault();
    var bt = $("button", f), li = f.closest(".cli"), lista = li && li.parentNode;
    if (!li) return;
    bt.setAttribute("aria-busy", "true");
    enviar(f).then(function (d) {
      var viz = d.dir === "cima" ? li.previousElementSibling : li.nextElementSibling;
      if (viz) {
        if (d.dir === "cima") lista.insertBefore(li, viz);
        else lista.insertBefore(viz, li);
      }
      numerarClientes(lista);
      li.classList.add("is-movendo");
      setTimeout(function () { li.classList.remove("is-movendo"); }, 900);
      if (!bt.disabled) bt.focus({ preventScroll: true });
      else { var outro = $(d.dir === "cima" ? "[data-mover-baixo]" : "[data-mover-cima]", li); if (outro && !outro.disabled) outro.focus({ preventScroll: true }); }
      if (li.scrollIntoView) li.scrollIntoView({ block: "nearest", behavior: "smooth" });
      toast(d.msg);
    }).catch(function (err) {
      if (err.message !== "login") toast(err.message, true);
    }).then(function () { bt.removeAttribute("aria-busy"); });
  });

  var fc = $("[data-form-cliente]");
  if (fc) {
    var cSujo = false, cEnviando = false, logoPronto = null;
    fc.addEventListener("input", function () { cSujo = true; });
    fc.addEventListener("change", function () { cSujo = true; });
    window.addEventListener("beforeunload", function (e) {
      if (cSujo && !cEnviando) { e.preventDefault(); e.returnValue = ""; }
    });
    var lInput = $("[data-logo-input]", fc), palco = $("[data-palco]", fc), chapa = $("[data-chapa]", fc);
    var lImg = $("[data-chapa-img]", fc), lVazio = $("[data-chapa-vazio]", fc), lRot = $("[data-logo-rotulo]", fc), lInfo = $("[data-logo-info]", fc);
    var LMAX = +lInput.getAttribute("data-max-bytes") || 5 * 1048576, LMIN = +lInput.getAttribute("data-min") || 80;
    var lEstado = document.createElement("p");
    lEstado.className = "foto-estado"; lEstado.setAttribute("aria-live", "polite"); lEstado.hidden = true;
    lInfo.insertAdjacentElement("beforebegin", lEstado);
    var lDica = document.createElement("p");
    lDica.className = "foto-dica"; lDica.hidden = true;
    lEstado.insertAdjacentElement("afterend", lDica);
    var tamanho = function (b) { return b > 1048576 ? (b / 1048576).toFixed(1).replace(".", ",") + " MB" : Math.max(1, Math.round(b / 1024)) + " KB"; };
    var estadoLogo = function (txt, erro) { lEstado.hidden = !txt; lEstado.textContent = txt || ""; lEstado.classList.toggle("is-erro", !!erro); };
    var dicaLogo = function (txt) { lDica.hidden = !txt; lDica.textContent = txt || ""; chapa.classList.toggle("is-pequena", !!txt); };

    var formatoLogo = function (file) {
      var nome = (file.name || "").toLowerCase(), tipo = (file.type || "").toLowerCase();
      if (tipo === "image/svg+xml" || /\.svgz?$/.test(nome)) return "Logo em SVG não é aceito (por segurança). Peça ao cliente o logo em PNG, de preferência com fundo transparente, ou abra o SVG no navegador, tire um print e use o print.";
      if (/hei[cf]/.test(tipo) || /\.hei[cf]$/.test(nome)) return "Esse arquivo está em HEIC, formato que o site não usa. Salve o logo como PNG ou JPG e escolha de novo.";
      if (tipo === "application/pdf" || /\.pdf$/.test(nome)) return "Esse arquivo é um PDF. Salve o logo como PNG (ou tire um print dele) e escolha de novo.";
      if (!/^image\/(jpeg|png|webp)$/.test(tipo)) return "Formato não aceito (" + (file.type || nome.split(".").pop() || "desconhecido") + "). Use o logo em PNG, JPG ou WebP.";
      if (file.size > LMAX) return "O logo tem " + tamanho(file.size) + "; o máximo é " + tamanho(LMAX) + ". Use um arquivo menor (logo não precisa ser grande).";
      return "";
    };
    // Mesma conta do servidor (lib/imagens.php → analisarFoto): corta as sobras transparentes
    // (ou brancas, se não houver transparência) com 3% de folga, e reduz para até 600 × 300.
    var prepararLogo = function (file) {
      var ruim = formatoLogo(file);
      if (ruim) return Promise.reject(new Error(ruim));
      return new Promise(function (ok, falha) {
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () { ok({ img: img, url: url }); };
        img.onerror = function () { URL.revokeObjectURL(url); falha(new Error("Não consegui abrir esse arquivo. Salve o logo de novo como PNG e tente outra vez.")); };
        img.src = url;
      }).then(function (r) {
        var w = r.img.naturalWidth, h = r.img.naturalHeight;
        if (w < LMIN || h < 20) { URL.revokeObjectURL(r.url); throw new Error("O logo é pequeno demais (" + w + " × " + h + " px). Use um com pelo menos " + LMIN + " px de largura (o ideal é 300 px ou mais)."); }
        var res = { url: r.url, w: w, h: h, cw: w, ch: h, transp: false, fundo: true, claro: false };
        try {
          var f = Math.min(1, 320 / Math.max(w, h)), tw = Math.max(1, Math.round(w * f)), th = Math.max(1, Math.round(h * f));
          var cv = document.createElement("canvas"); cv.width = tw; cv.height = th;
          var cx = cv.getContext("2d"); cx.drawImage(r.img, 0, 0, tw, th);
          var d = cx.getImageData(0, 0, tw, th).data, transp = 0, a = [tw, th, -1, -1], b = [tw, th, -1, -1], borda = 0, bom = 0, somaLum = 0, nOp = 0;
          var m = Math.max(1, Math.round(Math.min(tw, th) * 0.05));
          for (var y = 0; y < th; y++) for (var x = 0; x < tw; x++) {
            var i = (y * tw + x) * 4, al = d[i + 3], op = al / 255;
            if (al < 62) transp++;
            if (al > 62) { if (x < a[0]) a[0] = x; if (y < a[1]) a[1] = y; if (x > a[2]) a[2] = x; if (y > a[3]) a[3] = y; somaLum += (d[i] * .3 + d[i + 1] * .59 + d[i + 2] * .11); nOp++; }
            var mn = Math.min(d[i], d[i + 1], d[i + 2]) * op + 255 * (1 - op);
            if (mn < 238) { if (x < b[0]) b[0] = x; if (y < b[1]) b[1] = y; if (x > b[2]) b[2] = x; if (y > b[3]) b[3] = y; }
            if (y < m || y >= th - m || x < m || x >= tw - m) { borda++; if (al < 200 || Math.min(d[i], d[i + 1], d[i + 2]) >= 226) bom++; }
          }
          res.transp = transp / (tw * th) > 0.01;
          res.fundo = bom / borda > 0.7;
          // Logo branco sobre fundo transparente some na chapa branca (com fundo branco, o desenho já é o que não é branco)
          res.claro = res.transp && nOp > 0 && somaLum / nOp > 232;
          var c = res.transp ? a : b;
          if (c[2] >= 0) {
            var sx = w / tw, sy = h / th;
            var x0 = Math.floor(c[0] * sx), y0 = Math.floor(c[1] * sy), x1 = Math.ceil((c[2] + 1) * sx), y1 = Math.ceil((c[3] + 1) * sy);
            var mg = Math.round(0.03 * Math.max(x1 - x0, y1 - y0));
            x0 = Math.max(0, x0 - mg); y0 = Math.max(0, y0 - mg); x1 = Math.min(w, x1 + mg); y1 = Math.min(h, y1 + mg);
            if (!((x1 - x0) > w * 0.98 && (y1 - y0) > h * 0.98)) {
              res.cw = x1 - x0; res.ch = y1 - y0;
              var k = Math.min(1, 600 / res.cw, 300 / res.ch), ow = Math.max(1, Math.round(res.cw * k)), oh = Math.max(1, Math.round(res.ch * k));
              var out = document.createElement("canvas"); out.width = ow; out.height = oh;
              var ox = out.getContext("2d"); ox.imageSmoothingQuality = "high";
              ox.drawImage(r.img, x0, y0, res.cw, res.ch, 0, 0, ow, oh);
              res.previa = out.toDataURL("image/png");
            }
          } else if (res.transp) {
            URL.revokeObjectURL(r.url);
            throw new Error("O logo parece estar vazio (todo transparente). Confira o arquivo e escolha de novo.");
          }
        } catch (err) {
          if (/vazio/.test(err.message)) throw err;   // sem acesso aos pixels: a prévia usa a imagem inteira
        }
        if (res.cw < LMIN * 0.75) { URL.revokeObjectURL(r.url); throw new Error("Sem as margens vazias, o logo fica com só " + res.cw + " × " + res.ch + " px. Use um logo maior (pelo menos " + LMIN + " px de largura de desenho)."); }
        return res;
      });
    };
    var trocarLogo = function (file) {
      if (!file) return;
      estadoLogo("Preparando a prévia…");
      dicaLogo("");
      chapa.setAttribute("aria-busy", "true");
      logoPronto = prepararLogo(file).then(function (r) {
        if (lImg.src && lImg.src.indexOf("blob:") === 0) URL.revokeObjectURL(lImg.src);
        lImg.src = r.previa || r.url; lImg.alt = "Prévia do logo novo"; lImg.hidden = false;
        if (r.previa) URL.revokeObjectURL(r.url);
        lVazio.hidden = true;
        chapa.classList.remove("chapa--vazia");
        chapa.classList.toggle("chapa--sq", r.cw / r.ch < 1.35);   // mesma regra do site (js/core.js)
        lRot.textContent = "Trocar logo";
        estadoLogo("Logo novo pronto (" + r.w + " × " + r.h + " px" + (r.cw !== r.w || r.ch !== r.h ? ", sobras cortadas" : "") + "). Salve para publicar.");
        var avisos = [];
        if (r.claro) avisos.push("o logo é quase todo branco e fica apagado na chapa branca do site. Prefira a versão colorida ou escura do logo");
        else if (!r.transp && !r.fundo) avisos.push("o fundo deste logo não é branco nem transparente, então ele aparece como um retângulo colorido dentro da chapa branca. Se o cliente tiver o logo em PNG com fundo transparente, fica melhor");
        if (r.cw < 200) avisos.push("o logo é pequeno (" + r.cw + " px de largura) e pode ficar sem nitidez. Se tiver uma versão maior, prefira");
        dicaLogo(avisos.length ? "Atenção: " + avisos.join("; e ") + ". Pode salvar assim mesmo." : "");
        lInput.removeAttribute("aria-invalid");
      }).catch(function (err) {
        lInput.value = "";
        estadoLogo(err.message, true);
      }).then(function () {
        chapa.removeAttribute("aria-busy");
        logoPronto = null;
      });
    };
    lInput.addEventListener("change", function () { trocarLogo(lInput.files && lInput.files[0]); });
    ["dragenter", "dragover"].forEach(function (t) { palco.addEventListener(t, function (e) { e.preventDefault(); palco.classList.add("is-arrastando"); }); });
    ["dragleave", "drop"].forEach(function (t) { palco.addEventListener(t, function () { palco.classList.remove("is-arrastando"); }); });
    palco.addEventListener("drop", function (e) {
      e.preventDefault();
      var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (!file) return;
      try { var dt = new DataTransfer(); dt.items.add(file); lInput.files = dt.files; } catch (err) { return; }
      cSujo = true;
      trocarLogo(file);
    });
    // Antes de enviar: espera a prévia e confere se a sessão ainda vale (senão pede a senha numa janela)
    fc.addEventListener("submit", function (e) {
      var bt = e.submitter;
      if (logoPronto) {
        e.preventDefault();
        logoPronto.then(function () { if (fc.requestSubmit) fc.requestSubmit(bt || undefined); else { cEnviando = true; fc.submit(); } });
        return;
      }
      if (fc.dataset.liberado !== "1") {
        e.preventDefault();
        sessaoOk().then(function (ok) {
          if (!ok) return;
          fc.dataset.liberado = "1";
          if (fc.requestSubmit) fc.requestSubmit(bt || undefined); else { cEnviando = true; fc.submit(); }
          fc.dataset.liberado = "";
        });
        return;
      }
      cEnviando = true;
    });
    var errosC = $("[data-erros]");
    if (errosC) errosC.focus();
  }

  /* ------------------------------------------------------------------
     Formulários comuns: impede envio duplo
     ------------------------------------------------------------------ */
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (e.defaultPrevented || !f.matches || !f.matches("[data-form-enviar]")) return;
    var b = $("button[type=submit]", f) || $("[data-salvar]", f);
    if (!b) return;
    if (f.dataset.enviado === "1") { e.preventDefault(); return; }
    f.dataset.enviado = "1";
    b.setAttribute("aria-busy", "true");
    var sp = $("span", b);
    if (sp && b.matches("[data-salvar]")) sp.textContent = "Salvando…";
    // Se o navegador voltar à página (botão Voltar), libera de novo
    window.addEventListener("pageshow", function () { f.dataset.enviado = ""; b.removeAttribute("aria-busy"); }, { once: true });
  });

  /* ------------------------------------------------------------------
     Usuários: copiar a senha temporária, sugerir o login pelo nome
     ------------------------------------------------------------------ */
  // Copiar: área de transferência; sem ela (navegador antigo, http), seleciona o texto para copiar à mão
  function selecionar(el) {
    try {
      var r = document.createRange(); r.selectNodeContents(el);
      var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(r);
      return true;
    } catch (e) { return false; }
  }
  $$("[data-copiar]").forEach(function (b) {
    var alvo = document.getElementById(b.getAttribute("data-copiar"));
    if (!alvo) return;
    b.hidden = false;
    var rot = $("span", b), original = rot ? rot.textContent : "";
    b.addEventListener("click", function () {
      var txt = alvo.textContent;
      var feito = function () {
        if (rot) { rot.textContent = "Copiado!"; setTimeout(function () { rot.textContent = original; }, 2500); }
        toast(b.getAttribute("data-copiar") === "senha-temp" ? "Senha copiada. Agora cole na conversa com a pessoa." : "Mensagem copiada. Agora cole na conversa com a pessoa.");
      };
      var manual = function () {
        var ok = false;
        if (selecionar(alvo)) { try { ok = document.execCommand("copy"); } catch (e) { ok = false; } }
        if (ok) feito(); else toast("Não deu para copiar sozinho: o texto ficou selecionado. Toque e segure nele para copiar.", true);
      };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(txt).then(feito, manual);
      else manual();
    });
  });

  // Novo usuário: sugere o login a partir do nome ("Lizi Ellen" → lizi), até a pessoa mexer no campo
  var nomeU = $("[data-sugerir-login]"), loginU = $("[data-login]");
  if (nomeU && loginU) {
    var ocupados = (loginU.getAttribute("data-ocupados") || "").split(" ").filter(Boolean);
    var loginAuto = loginU.value === "";
    var limpa = function (s) { return String(s).normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[^a-z0-9]/g, ""); };
    var sugerir = function () {
      var p = nomeU.value.trim().split(/\s+/).map(limpa).filter(Boolean);
      if (!p.length) return "";
      var opcoes = [p[0]];
      if (p.length > 1) opcoes.push(p[0] + "." + p[p.length - 1]);
      for (var i = 2; i < 10; i++) opcoes.push(p[0] + i);
      for (var j = 0; j < opcoes.length; j++) {
        var o = opcoes[j].slice(0, 40);
        if (o.length >= 3 && ocupados.indexOf(o) === -1) return o;
      }
      return "";
    };
    loginU.addEventListener("input", function () { loginAuto = loginU.value === ""; });
    nomeU.addEventListener("input", function () { if (loginAuto) loginU.value = sugerir(); });
  }

  // Filtro de pessoa (Atividades): troca na hora, sem botão
  $$("select[data-auto-enviar]").forEach(function (sel) {
    sel.addEventListener("change", function () { if (sel.form) sel.form.submit(); });
  });

  /* ------------------------------------------------------------------
     Mostrar/ocultar senha
     ------------------------------------------------------------------ */
  $$("[data-ver-senha]").forEach(function (b) {
    var inp = b.parentNode.querySelector("input");
    b.hidden = false;
    b.addEventListener("click", function () {
      var ver = inp.type === "password";
      inp.type = ver ? "text" : "password";
      b.setAttribute("aria-pressed", ver ? "true" : "false");
      b.setAttribute("aria-label", ver ? "Esconder senha" : "Mostrar senha");
    });
  });

  // Código de primeiro acesso: maiúsculas e hífens automáticos
  var cod = $("#f-codigo");
  if (cod) {
    cod.addEventListener("input", function () {
      var v = cod.value.toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 12);
      cod.value = v.replace(/(.{4})(?=.)/g, "$1-");
    });
  }

  // Avisos: todos têm botão de fechar; os simples de sucesso somem sozinhos. Os de erro e os que
  // têm ação (Ver no site, Desfazer) ficam até o dono fechar.
  $$("[data-avisos] .aviso").forEach(function (a) {
    var x = $("[data-aviso-fechar]", a);
    if (x) { x.hidden = false; x.addEventListener("click", function () { a.hidden = true; }); }
    if (a.hasAttribute("data-aviso-acao") || a.classList.contains("aviso--erro")) return;
    setTimeout(function () { a.hidden = true; }, 12000);
  });
  // Produto salvo: o rascunho dele já não serve
  $$("[data-rascunho-limpar]").forEach(function (a) {
    try { localStorage.removeItem("bc-painel-rascunho:" + a.getAttribute("data-rascunho-limpar")); } catch (e) { /* ok */ }
  });
  // Depois de salvar, a lista abre no produto (#p-ID), longe do aviso do topo: o aviso aparece
  // também fixo na tela (com o link "Ver no site", por 30 s) e o cartão fica marcado
  var alvoHash = /^#(p-[a-z0-9-]+|c-\d+)$/.test(location.hash) ? document.getElementById(location.hash.slice(1)) : null;
  var primeiro = $("[data-avisos] .aviso");
  if (alvoHash && primeiro) {
    alvoHash.classList.add("is-novo");
    setTimeout(function () { alvoHash.classList.remove("is-novo"); }, 8000);
    var pTxt = $("p", primeiro), link = pTxt && $("a", pTxt);
    var msg = pTxt ? (pTxt.firstChild && pTxt.firstChild.nodeType === 3 ? pTxt.firstChild.nodeValue : pTxt.textContent).trim() : "";
    if (msg) toast(msg, primeiro.classList.contains("aviso--erro"), { link: link ? link.cloneNode(true) : null, tempo: link ? 30000 : 10000 });
  }

  /* ------------------------------------------------------------------
     Área Empresa (ajustes do site): usa $, $$, toast, confirmar e
     sessaoOk daqui de cima. Sem isto a tela funciona igual (linhas vazias para acrescentar,
     botões que mostram a tela de novo); com isto: prévias ao vivo,
     acrescentar/tirar/mover linhas na hora, aviso de parte não salva e
     conferência da sessão antes de enviar.
     ------------------------------------------------------------------ */
  (function empresa() {
    var forms = $$("[data-emp-form]");
    if (!forms.length) return;
    var enviando = false, novaChave = 1000;

    function titulo(f) { var h = $(".emp-sec__tit", f.closest(".emp-sec")); return h ? h.textContent.trim() : ""; }
    // Estado do formulário (sem o csrf e sem campos vazios: linha nova em branco não conta como mudança)
    function estado(f) {
      var out = [];
      new FormData(f).forEach(function (v, k) { if (k !== "csrf" && v !== "") out.push(k + "=" + v); });
      return out.join("&");
    }
    function sujo(f) { return f.dataset.empVoltou != null || estado(f) !== f.empInicial; }
    function marcarSujo(f) {
      var s = sujo(f), sec = f.closest(".emp-sec"), selo = sec && $("[data-emp-sujo]", sec);
      if (selo) selo.hidden = !s;
      var chip = $('[data-emp-indice="' + f.getAttribute("data-emp-form") + '"]');
      if (chip) chip.classList.toggle("is-sujo", s);
    }

    /* Contador de caracteres */
    $$("[data-emp-contador]").forEach(function (t) {
      var max = +t.getAttribute("maxlength"), p = document.createElement("p");
      p.className = "contador"; p.setAttribute("aria-hidden", "true");
      t.insertAdjacentElement("afterend", p);
      var at = function () { p.textContent = t.value.length + "/" + max; p.classList.toggle("is-perto", t.value.length > max * 0.9); };
      t.addEventListener("input", at); at();
    });

    /* Caixas de texto crescem com o texto (onde o CSS field-sizing ainda não existe) */
    if (!(window.CSS && CSS.supports && CSS.supports("field-sizing", "content"))) {
      var crescer = function (t) { t.style.height = "auto"; t.style.height = Math.min(t.scrollHeight + 4, window.innerHeight * 0.6) + "px"; };
      document.addEventListener("input", function (e) { if (e.target.matches && e.target.matches(".emp-form textarea")) crescer(e.target); });
      $$(".emp-form textarea").forEach(crescer);
    }

    /* ---------- Horário: Fechado, Repetir e prévia (mesma regra de lib/ajustes.php) ---------- */
    var ORDEM = [1, 2, 3, 4, 5, 6, 0], NOMES = ["domingo", "segunda", "terça", "quarta", "quinta", "sexta", "sábado"];
    function minutos(t, fim) {
      var m = /^(\d{1,2})(?:[:h](\d{2})?)?$/.exec(String(t).toLowerCase().replace(/ /g, ""));
      if (!m) return null;
      var h = +m[1], mi = +(m[2] || 0);
      if (mi > 59 || h > 24 || (h === 24 && (mi > 0 || !fim))) return null;
      return h * 60 + mi;
    }
    function horaTxt(m) { var h = Math.floor(m / 60), mi = m % 60; return h + "h" + (mi ? (mi < 10 ? "0" : "") + mi : ""); }
    function horasTxt(iv) { return iv.length ? iv.map(function (x) { return horaTxt(x[0]) + " às " + horaTxt(x[1]); }).join(" · ") : "Fechado"; }
    function nomeGrupo(pos, feriados) {
      var itens = [], n = pos.length, i = 0, j, k;
      while (i < n) {
        j = i; while (j + 1 < n && pos[j + 1] === pos[j] + 1) j++;
        if (j - i >= 2) itens.push(NOMES[ORDEM[pos[i]]] + " a " + NOMES[ORDEM[pos[j]]]);
        else for (k = i; k <= j; k++) itens.push(NOMES[ORDEM[pos[k]]]);
        i = j + 1;
      }
      if (feriados) itens.push("feriados");
      var ult = itens.pop(), t = itens.length ? itens.join(", ") + " e " + ult : ult;
      return t.charAt(0).toUpperCase() + t.slice(1);
    }
    function linhasHorario(grade) {
      var grupos = [], idx = {}, linhas = [], fechados = null;
      ORDEM.forEach(function (d, p) {
        var k = JSON.stringify(grade[d]);
        if (!(k in idx)) { idx[k] = grupos.length; grupos.push({ iv: grade[d], pos: [] }); }
        grupos[idx[k]].pos.push(p);
      });
      grupos.forEach(function (g) {
        if (!g.iv.length && g.pos.indexOf(6) >= 0) { fechados = g.pos; return; }
        linhas.push([nomeGrupo(g.pos), horasTxt(g.iv)]);
      });
      linhas.push(fechados ? [nomeGrupo(fechados, true), "Fechado"] : ["Feriados", "Fechado"]);
      return linhas;
    }
    // Grade a partir da tela; null se algum dia estiver incompleto (a prévia espera a correção)
    function gradeDaTela(fh) {
      var grade = {}, algum = false, ok = true;
      $$("[data-emp-dia]", fh).forEach(function (dia) {
        var d = +dia.getAttribute("data-emp-dia"), iv = [];
        if (!$("[data-emp-fechado]", dia).checked) {
          $$(".emp-iv", dia).forEach(function (p) {
            var ins = $$("input", p), a = ins[0].value.trim(), b = ins[1].value.trim();
            if (!a && !b) return;
            var ma = minutos(a, false), mb = minutos(b, true);
            if (ma == null || mb == null || ma >= mb) { ok = false; return; }
            iv.push([ma, mb]);
          });
          iv.sort(function (x, y) { return x[0] - y[0]; });
          for (var i = 1; i < iv.length; i++) if (iv[i][0] < iv[i - 1][1]) ok = false;
          if (!iv.length) ok = false;
        }
        if (iv.length) algum = true;
        grade[d] = iv;
      });
      return ok && algum ? grade : null;
    }
    var fh = $('[data-emp-form="horario"]');
    if (fh) {
      var lista = $("[data-emp-previa-horas]", fh), erroPrevia = $("[data-emp-previa-erro]", fh);
      var previaHoras = function () {
        var g = gradeDaTela(fh);
        erroPrevia.hidden = !!g;
        lista.hidden = !g;
        if (!g) return;
        lista.textContent = "";
        linhasHorario(g).forEach(function (l) {
          var li = document.createElement("li"), a = document.createElement("span"), b = document.createElement("span");
          a.textContent = l[0]; b.textContent = l[1];
          li.appendChild(a); li.appendChild(b); lista.appendChild(li);
        });
      };
      var acertarFechado = function (dia) {
        var f = $("[data-emp-fechado]", dia).checked;
        dia.classList.toggle("is-fechado", f);
        $("[data-emp-int]", dia).hidden = f;
      };
      // Relógio de 12 h (AM/PM, mais largo): o "às [ ]" desce para a linha de baixo; aí os rótulos
      // ficam da mesma largura, para os campos de hora alinharem um embaixo do outro
      var acertarQuebras = function () {
        $$(".emp-iv", fh).forEach(function (p) {
          var par = $$(".emp-iv__par", p);
          p.classList.remove("is-quebrado");
          if (par.length === 2 && par[1].offsetTop > par[0].offsetTop + 4) p.classList.add("is-quebrado");
        });
      };
      $$("[data-emp-dia]", fh).forEach(acertarFechado);
      acertarQuebras();
      window.addEventListener("resize", acertarQuebras);
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(acertarQuebras);
      fh.addEventListener("change", function (e) { var dia = e.target.closest("[data-emp-dia]"); if (dia && e.target.matches("[data-emp-fechado]")) { acertarFechado(dia); acertarQuebras(); } previaHoras(); });
      fh.addEventListener("input", previaHoras);
      var rep = $("[data-emp-repetir]", fh);
      if (rep) rep.addEventListener("click", function (e) {
        e.preventDefault();
        var seg = $('[data-emp-dia="1"]', fh), vals = $$(".emp-iv input", seg).map(function (i) { return i.value; });
        var fechado = $("[data-emp-fechado]", seg).checked;
        [2, 3, 4, 5].forEach(function (d) {
          var dia = $('[data-emp-dia="' + d + '"]', fh);
          $("[data-emp-fechado]", dia).checked = fechado;
          $$(".emp-iv input", dia).forEach(function (inp, i) { inp.value = vals[i] || ""; });
          acertarFechado(dia);
        });
        acertarQuebras();
        previaHoras();
        fh.dispatchEvent(new Event("input", { bubbles: true }));
        toast("Horário de segunda copiado para terça, quarta, quinta e sexta. Toque em Salvar horário para gravar.");
      });
    }

    /* ---------- Listas: acrescentar, tirar e mover linhas (equipe, depoimentos, feriados) ---------- */
    function prepararLinha(li) {
      $$("[data-emp-rm-sem-js]", li).forEach(function (l) { l.hidden = true; var c = $("input", l); if (c) c.checked = false; });
      $$("[data-emp-rm]", li).forEach(function (b) { b.hidden = false; });
    }
    function renumerar(ol) {
      var rot = ol.getAttribute("data-emp-rotulo") || "Item", itens = $$("[data-emp-item]:not([data-emp-extra])", ol);
      itens.forEach(function (li, i) {
        var n = $("[data-emp-n]", li), nome = rot + " " + (i + 1);
        if (n) n.textContent = nome;
        var c = $('[data-emp-mover="cima"]', li), b = $('[data-emp-mover="baixo"]', li), r = $("[data-emp-rm]", li);
        if (c) { c.disabled = i === 0; c.setAttribute("aria-label", "Subir " + nome); }
        if (b) { b.disabled = i === itens.length - 1; b.setAttribute("aria-label", "Descer " + nome); }
        if (r) r.setAttribute("aria-label", "Remover " + nome);
      });
      var mais = ol.parentNode.querySelector(".emp-mais");
      var max = +ol.getAttribute("data-emp-max") || 0;
      if (mais) { mais.hidden = false; $("button", mais).disabled = !!max && itens.length >= max; }
    }
    function novaLinha(ol) {
      var modelo = ol.parentNode.querySelector("template[data-emp-modelo]");
      if (!modelo) return null;
      var k = "n" + (novaChave++), box = document.createElement("div");
      box.innerHTML = modelo.innerHTML.replace(/__K__/g, k);
      var li = box.firstElementChild;
      prepararLinha(li);
      ol.appendChild(li);
      renumerar(ol);
      return li;
    }
    $$("[data-emp-lista]").forEach(function (ol) {
      // Linhas vazias que só servem sem JavaScript saem; o botão "Acrescentar" toma o lugar delas
      $$("[data-emp-extra]", ol).forEach(function (li) {
        if ($$("input, textarea", li).every(function (i) { return i.type === "checkbox" || !i.value.trim(); })) li.remove();
      });
      $$("[data-emp-item]", ol).forEach(prepararLinha);
      renumerar(ol);
    });
    document.addEventListener("click", function (e) {
      var add = e.target.closest("[data-emp-adicionar]");
      if (add) {
        var ol = $('[data-emp-lista="' + add.getAttribute("data-emp-adicionar") + '"]');
        var li = ol && novaLinha(ol);
        if (li) { var p = $("input, textarea", li); if (p) p.focus(); li.closest("form").dispatchEvent(new Event("input", { bubbles: true })); }
        return;
      }
      var rm = e.target.closest("[data-emp-rm]");
      if (rm) {
        var item = rm.closest("[data-emp-item]"), lista = item.parentNode, f = item.closest("form");
        var prox = item.nextElementSibling || item.previousElementSibling;
        item.remove();
        renumerar(lista);
        marcarSujo(f);
        atualizarSugestoes();
        var foco = prox && $("input, textarea", prox);
        if (foco) foco.focus(); else { var b = lista.parentNode.querySelector(".emp-mais button"); if (b) b.focus(); }
        toast("Linha removida. Toque em " + ($("[data-salvar] span", f) || {}).textContent + " para gravar.");
        return;
      }
      var mv = e.target.closest("[data-emp-mover]");
      if (mv) {
        e.preventDefault();
        var li2 = mv.closest("[data-emp-item]"), ol2 = li2.parentNode, cima = mv.getAttribute("data-emp-mover") === "cima";
        var vizinho = cima ? li2.previousElementSibling : li2.nextElementSibling;
        if (!vizinho || vizinho.hasAttribute("data-emp-extra")) return;
        if (cima) ol2.insertBefore(li2, vizinho); else ol2.insertBefore(vizinho, li2);
        renumerar(ol2);
        li2.classList.add("is-movendo");
        setTimeout(function () { li2.classList.remove("is-movendo"); }, 700);
        if (mv.disabled) { var outro = $('[data-emp-mover="' + (cima ? "baixo" : "cima") + '"]', li2); if (outro) outro.focus(); } else mv.focus();
        marcarSujo(li2.closest("form"));
      }
    });

    /* ---------- Feriados: sugestões para Rio Claro ---------- */
    var ff = $('[data-emp-form="feriados"]');
    function normalDiaMes(t) { var m = /^(\d{1,2})[\/.\-](\d{1,2})$/.exec(String(t).trim()); return m ? ("0" + m[1]).slice(-2) + "/" + ("0" + m[2]).slice(-2) : ""; }
    function atualizarSugestoes() {
      if (!ff) return;
      $$("[data-emp-sugerir]", ff).forEach(function (b) {
        var ja;
        if (b.hasAttribute("data-movel")) { var c = $('[data-emp-movel="' + b.getAttribute("data-movel") + '"]', ff); ja = !!(c && c.checked); }
        else ja = $$('[data-emp-lista="mun"] input[name$="[data]"]', ff).some(function (i) { return normalDiaMes(i.value) === b.getAttribute("data-data"); });
        b.disabled = ja;
        $("span", b).textContent = ja ? "Já está na lista" : "Colocar na lista";
        var ic = $("use", b); if (ic) ic.setAttribute("href", ja ? "#i-check" : "#i-plus");
      });
    }
    if (ff) {
      ff.addEventListener("input", atualizarSugestoes);
      ff.addEventListener("change", atualizarSugestoes);
      $$("[data-emp-sugerir]", ff).forEach(function (b) {
        b.addEventListener("click", function (e) {
          e.preventDefault();
          if (b.hasAttribute("data-movel")) {
            var c = $('[data-emp-movel="' + b.getAttribute("data-movel") + '"]', ff);
            if (c) { c.checked = true; c.focus(); }
          } else {
            var li = novaLinha($('[data-emp-lista="mun"]', ff));
            if (li) {
              $('input[name$="[data]"]', li).value = b.getAttribute("data-data");
              $('input[name$="[nome]"]', li).value = b.getAttribute("data-nome");
              $('input[name$="[nome]"]', li).focus();
            }
          }
          atualizarSugestoes();
          marcarSujo(ff);
          toast("Colocado na lista, mas ainda não salvo. Confira e toque em Salvar feriados.");
        });
      });
    }

    /* ---------- Aviso: prévia no estilo do site e quando ele aparece ---------- */
    var fa = $('[data-emp-form="aviso"]');
    function hojeSP() {
      try { return new Intl.DateTimeFormat("en-CA", { timeZone: "America/Sao_Paulo", year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date()); }
      catch (e) { var d = new Date(); return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2); }
    }
    function dataBr(iso) { return iso.slice(8, 10) + "/" + iso.slice(5, 7) + "/" + iso.slice(0, 4); }
    if (fa) {
      var previaAviso = function () {
        var ativo = $("[data-emp-aviso-ativo]", fa).checked, txt = $("textarea[name=texto]", fa).value.trim();
        var tom = ($("input[name=tom]:checked", fa) || {}).value, ini = $("input[name=inicio]", fa).value, fim = $("input[name=fim]", fa).value;
        var faixa = $("[data-emp-previa-aviso]", fa), est = $("[data-emp-aviso-estado]", fa), hoje = hojeSP(), ok = /^\d{4}-\d{2}-\d{2}$/;
        $("[data-emp-previa-texto]", fa).textContent = txt || "Escreva o texto do aviso.";
        faixa.classList.toggle("emp-faixa--alerta", tom === "alerta");
        faixa.classList.toggle("is-desligado", !ativo);
        var t;
        if (!ativo) t = "Desligado: não aparece no site.";
        else if (ok.test(fim) && fim < hoje) t = "Já terminou em " + dataBr(fim) + ": não aparece mais no site.";
        else if (ok.test(ini) && ini > hoje) t = "Aparece no site de " + dataBr(ini) + (ok.test(fim) ? " a " + dataBr(fim) : " em diante") + ".";
        else t = "Aparece no site agora" + (ok.test(fim) ? ", até " + dataBr(fim) : "") + ".";
        est.textContent = t;
        est.classList.toggle("is-off", !ativo);
      };
      fa.addEventListener("input", previaAviso);
      fa.addEventListener("change", previaAviso);
      previaAviso();
    }

    /* ---------- Marcas: contagem; WhatsApp: link de teste ---------- */
    var fmar = $('[data-emp-form="marcas"]');
    if (fmar) fmar.addEventListener("input", function () {
      var n = $("textarea", fmar).value.split(/[\r\n,;]+/).filter(function (x) { return x.trim(); }).length, b = $("[data-emp-marcas-n]", fmar);
      if (b) { b.textContent = n; b.nextSibling.nodeValue = n === 1 ? " marca agora." : " marcas agora."; }
    });
    var fc2 = $('[data-emp-form="contato"]');
    if (fc2) fc2.addEventListener("input", function () {
      var a = $("[data-emp-testar-wa]", fc2), d = $("input[name=whatsapp]", fc2).value.replace(/\D+/g, "").replace(/^0+/, "");
      if (/^55\d{10,11}$/.test(d)) d = d.slice(2);
      if (a) { a.hidden = !/^\d{10,11}$/.test(d); a.href = "https://wa.me/55" + d; }
    });

    /* ---------- Parte não salva, sessão e envio ---------- */
    forms.forEach(function (f) {
      f.empInicial = estado(f);
      marcarSujo(f);
      f.addEventListener("input", function () { marcarSujo(f); });
      f.addEventListener("change", function () { marcarSujo(f); });
      f.addEventListener("submit", function (e) {
        e.preventDefault();
        if (f.dataset.enviado === "1") return;
        var outros = forms.filter(function (x) { return x !== f && sujo(x); }).map(function (x) { return "“" + titulo(x) + "”"; });
        var pergunta = outros.length
          ? confirmar("Você também mudou " + (outros.length > 1 ? outros.slice(0, -1).join(", ") + " e " + outros[outros.length - 1] : outros[0]) +
            " e ainda não salvou. Se salvar só “" + titulo(f) + "” agora, essas outras mudanças se perdem.", "Salvar só esta parte", "Mudanças não salvas")
          : Promise.resolve(true);
        pergunta.then(function (sim) { return sim ? sessaoOk() : false; }).then(function (ok) {
          if (!ok) return;
          enviando = true;
          f.dataset.enviado = "1";
          var b = $("[data-salvar]", f), sp = b && $("span", b);
          if (b) b.setAttribute("aria-busy", "true");
          if (sp) sp.textContent = "Salvando…";
          HTMLFormElement.prototype.submit.call(f);
        });
      });
    });
    window.addEventListener("pageshow", function (e) {
      if (!e.persisted) return;
      enviando = false;
      forms.forEach(function (f) { f.dataset.enviado = ""; var b = $("[data-salvar]", f); if (b) b.removeAttribute("aria-busy"); });
    });
    window.addEventListener("beforeunload", function (e) {
      if (enviando || !forms.some(sujo)) return;
      e.preventDefault();
      e.returnValue = "";
    });

    /* ---------- Índice: parte visível marcada; depois de salvar, aviso preso na tela ---------- */
    var chips = $$("[data-emp-indice]");
    if ("IntersectionObserver" in window && chips.length) {
      var visiveis = {};
      var io = new IntersectionObserver(function (ents) {
        ents.forEach(function (en) { visiveis[en.target.id.slice(2)] = en.isIntersecting ? en.intersectionRect.top : null; });
        var atual = null;
        chips.forEach(function (c) { var s = c.getAttribute("data-emp-indice"); if (atual === null && visiveis[s] != null) atual = s; });
        if (!atual) return;
        chips.forEach(function (c) {
          var on = c.getAttribute("data-emp-indice") === atual;
          c.classList.toggle("is-atual", on);
          if (on) c.setAttribute("aria-current", "true"); else c.removeAttribute("aria-current");
          if (on) { var ol = c.closest("ol"); if (ol && ol.scrollWidth > ol.clientWidth) ol.scrollLeft = c.offsetLeft - ol.clientWidth / 2 + c.offsetWidth / 2; }
        });
      }, { rootMargin: "-130px 0px -55% 0px" });
      $$(".emp-sec").forEach(function (s) { io.observe(s); });
    }
    var m = /^#e-([a-z]+)$/.exec(location.hash), sec = m && document.getElementById("e-" + m[1]);
    var erros = $(".emp-sec [data-emp-erros]");
    // Voltou com erro (ou nota): foco no resumo. Depois do "load": o navegador, ao rolar até a
    // âncora #e-…, tira o foco do que não é a própria âncora
    if (erros) window.addEventListener("load", function () { setTimeout(function () { erros.focus(); }, 60); });
    else if (sec) {
      var av = $("[data-avisos] .aviso");
      sec.classList.add("is-novo");
      setTimeout(function () { sec.classList.remove("is-novo"); }, 8000);
      if (av) {
        var pTxt = $("p", av), link = pTxt && $("a", pTxt);
        var msg = pTxt ? (pTxt.firstChild && pTxt.firstChild.nodeType === 3 ? pTxt.firstChild.nodeValue : pTxt.textContent).trim() : "";
        if (msg) toast(msg, av.classList.contains("aviso--erro"), { link: link ? link.cloneNode(true) : null, tempo: link ? 30000 : 10000 });
      }
    }
  })();
})();
