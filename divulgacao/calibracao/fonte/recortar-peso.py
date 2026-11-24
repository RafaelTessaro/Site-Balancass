#!/usr/bin/env python3
"""Recorta a foto de um peso-padrão (fundo branco ou já transparente) e grava um PNG com fundo transparente.

Uso:  python3 divulgacao/calibracao/fonte/recortar-peso.py <foto> <kg>
      (ex.: recortar-peso.py ~/Downloads/peso20.jpg 20  →  fonte/img/peso-20kg.png)

Como funciona: o fundo é o branco ligado às bordas da foto (preenchimento a partir das bordas, com
tolerância), então os reflexos claros DENTRO do peso não somem. A borda recebe 1 px de suavização e
a foto é cortada no contorno do peso, com uma margem pequena. Precisa só do Pillow.
Depois de recortar, gere as artes de novo: o gerador usa a foto no lugar do desenho sozinho.
"""
import sys
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageFilter

LIMIAR = 246      # canal mínimo a partir do qual o pixel conta como "branco do fundo"
MARGEM = 0.02     # margem em volta do peso, em fração do maior lado


def recortar(origem: Path, destino: Path) -> tuple[int, int]:
    foto = Image.open(origem)
    if foto.mode in ("RGBA", "LA", "PA") or "transparency" in foto.info:
        rgba = foto.convert("RGBA")
        if rgba.getchannel("A").getextrema()[0] < 8:   # a foto já tem o fundo transparente: só corta
            return cortar(rgba, rgba.getchannel("A"), destino)
    rgb = foto.convert("RGB")
    w, h = rgb.size
    # "Brancura": o menor dos três canais (o fundo branco fica perto de 255 nos três)
    menor = ImageChops.darker(ImageChops.darker(rgb.getchannel("R"), rgb.getchannel("G")), rgb.getchannel("B"))
    claro = menor.point(lambda v: 255 if v >= LIMIAR else 0)
    # Preenche a partir das bordas: só o branco ligado à borda é fundo
    marca = claro.copy()
    for x in range(0, w, max(1, w // 64)):
        for y in (0, h - 1):
            if marca.getpixel((x, y)) == 255:
                ImageDraw.floodfill(marca, (x, y), 128)
    for y in range(0, h, max(1, h // 64)):
        for x in (0, w - 1):
            if marca.getpixel((x, y)) == 255:
                ImageDraw.floodfill(marca, (x, y), 128)
    fundo = marca.point(lambda v: 255 if v == 128 else 0)
    alfa = ImageChops.invert(fundo)
    # Borda: tira 1 px (o halo branco) e suaviza
    alfa = alfa.filter(ImageFilter.MinFilter(3)).filter(ImageFilter.GaussianBlur(0.8))
    rgba = rgb.copy()
    rgba.putalpha(alfa)
    return cortar(rgba, alfa, destino)


def cortar(rgba: Image.Image, alfa: Image.Image, destino: Path) -> tuple[int, int]:
    """Corta no contorno do peso (com uma margem pequena) e grava o PNG."""
    w, h = rgba.size
    caixa = alfa.point(lambda v: 255 if v > 8 else 0).getbbox()
    if not caixa:
        raise SystemExit("Não achei o peso na foto (o fundo é branco ou transparente?)")
    m = int(max(w, h) * MARGEM)
    caixa = (max(0, caixa[0] - m), max(0, caixa[1] - m), min(w, caixa[2] + m), min(h, caixa[3] + m))
    rgba = rgba.crop(caixa)
    destino.parent.mkdir(parents=True, exist_ok=True)
    rgba.save(destino, optimize=True)
    return rgba.size


if __name__ == "__main__":
    if len(sys.argv) != 3 or not sys.argv[2].isdigit():
        raise SystemExit(__doc__)
    saida = Path(__file__).resolve().parent / "img" / f"peso-{sys.argv[2]}kg.png"
    tam = recortar(Path(sys.argv[1]).expanduser(), saida)
    print(f"{saida}  {tam[0]}×{tam[1]} px (proporção {tam[0] / tam[1]:.3f})")
