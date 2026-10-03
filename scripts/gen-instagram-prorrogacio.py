"""Genera el post/story d'Instagram de la prorroga (Retrats Lents)."""
import os
from PIL import Image, ImageDraw, ImageFont, ImageOps

SRC = "static/img/retrats-lents/11.jpg"
OUT = "campanya-instagram"
FONT_BOLD = "/System/Library/Fonts/Helvetica.ttc"
FONT_LIGHT = "/System/Library/Fonts/HelveticaNeue.ttc"
ACCENT = (254, 33, 33)
WHITE = (255, 255, 255)

os.makedirs(OUT, exist_ok=True)

def font(path, size, index=0):
    try:
        return ImageFont.truetype(path, size, index=index)
    except Exception:
        return ImageFont.load_default()

def cover(img, w, h):
    return ImageOps.fit(img.convert("RGB"), (w, h), Image.LANCZOS)

def bottom_gradient(img, ratio, max_alpha=230):
    w, h = img.size
    gh = int(h * ratio)
    grad = Image.new("L", (1, gh))
    for y in range(gh):
        grad.putpixel((0, y), int(max_alpha * (y / gh)))
    grad = grad.resize((w, gh))
    black = Image.new("RGB", (w, gh), (0, 0, 0))
    img.paste(black, (0, h - gh), grad)
    return img

def center(draw, text, y, f, color=WHITE, w=1080):
    bbox = draw.textbbox((0, 0), text, font=f)
    x = (w - (bbox[2] - bbox[0])) // 2
    draw.text((x, y), text, fill=color, font=f)

def make(w, h, out_name, story=False):
    img = cover(Image.open(SRC), w, h)
    img = bottom_gradient(img, 0.62 if story else 0.52)
    d = ImageDraw.Draw(img)
    f_kick = font(FONT_BOLD, 40)
    f_title = font(FONT_BOLD, 170 if story else 150)
    f_sub = font(FONT_LIGHT, 56 if story else 46)
    f_big = font(FONT_BOLD, 84 if story else 66)
    f_small = font(FONT_LIGHT, 44 if story else 34)

    center(d, "112 REVELATS", int(h * 0.06), f_kick, (235, 235, 235))
    if story:
        center(d, "PRÒRROGA", int(h * 0.50), f_title, ACCENT)
        center(d, "Nou termini", int(h * 0.63), f_sub, WHITE)
        center(d, "1 de novembre de 2026", int(h * 0.67), f_big, WHITE)
        center(d, "Formulari arreglat · més marge per participar", int(h * 0.75), f_small, (235, 235, 235))
        center(d, "Participa · enllaç al perfil", int(h * 0.85), f_sub, WHITE)
    else:
        center(d, "PRÒRROGA", int(h * 0.60), f_title, ACCENT)
        center(d, "Nou termini", int(h * 0.78), f_sub, WHITE)
        center(d, "1 de novembre de 2026", int(h * 0.825), f_big, WHITE)
        center(d, "Formulari arreglat · més marge per participar", int(h * 0.925), f_small, (235, 235, 235))
    img.save(os.path.join(OUT, out_name), "JPEG", quality=90)
    print("fet:", out_name, img.size)

make(1080, 1080, "post-14-prorrogacio.jpg", story=False)
make(1080, 1920, "story-prorrogacio.jpg", story=True)
