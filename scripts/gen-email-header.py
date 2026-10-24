#!/usr/bin/env python3
"""Genera la imatge de capcalera dels correus dels formularis.

Sortida: server/forms/assets/email-header.jpg (1200x420, ~50-70 KB).
Es munta sobre la fotografia hero del web, amb el mateix tractament fosc
que fa la home, perque el correu quedi acord amb el web.
"""
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageOps

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "static/img/hero-bg.png"
OUT = ROOT / "server/forms/assets/email-header.jpg"
W, H = 1200, 420

OUT.parent.mkdir(parents=True, exist_ok=True)

img = Image.open(SRC).convert("RGB")
img = ImageOps.fit(img, (W, H), method=Image.LANCZOS, centering=(0.5, 0.45))

# Velo fosc com el .hero-overlay de la home.
overlay = Image.new("RGBA", (W, H), (17, 17, 16, 150))
img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")

draw = ImageDraw.Draw(img)
title_font = ImageFont.truetype("/System/Library/Fonts/Supplemental/Georgia Bold.ttf", 72)
sub_font = ImageFont.truetype("/System/Library/Fonts/Supplemental/Georgia.ttf", 27)

title = "112 Revelats"
sub = "Projecte de fotollibres col·lectius"

def box(text, font):
    return draw.textbbox((0, 0), text, font=font)

tw, th = box(title, title_font)[2], box(title, title_font)[3]
sw, sh = box(sub, sub_font)[2], box(sub, sub_font)[3]

title_y = (H - (th + 90 + sh)) // 2
title_x = (W - tw) // 2
sub_x = (W - sw) // 2

# Ombra subtil per garantir la lectura sobre qualsevol zona de la foto.
for dx, dy in ((2, 2), (1, 1)):
    draw.text((title_x + dx, title_y + dy), title, font=title_font, fill=(0, 0, 0))
draw.text((title_x, title_y), title, font=title_font, fill=(255, 255, 255))

line_y = title_y + th + 22
draw.rectangle([(W - 120) // 2, line_y, (W + 120) // 2, line_y + 5], fill=(254, 33, 33))

sub_y = line_y + 28
for dx, dy in ((1, 1),):
    draw.text((sub_x + dx, sub_y + dy), sub, font=sub_font, fill=(0, 0, 0))
draw.text((sub_x, sub_y), sub, font=sub_font, fill=(240, 240, 240))

img.save(OUT, "JPEG", quality=74, optimize=True, progressive=True)
print(OUT)
print("size", OUT.stat().st_size, "bytes")
