"""Genera imatges per a campanya Instagram de Retrats Lents (112books)"""

import os
import random
from PIL import Image, ImageDraw, ImageFont, ImageFilter

SRC_DIR = "static/img/retrats-lents"
OUT_DIR = "campanya-instagram"
FONT_BOLD = "/System/Library/Fonts/Helvetica.ttc"
FONT_LIGHT = "/System/Library/Fonts/HelveticaNeue.ttc"
ACCENT = (254, 33, 33)      # 112 Revelats red
INK = (17, 17, 16)          # near-black
WHITE = (255, 255, 255)
WARM = (180, 120, 80)       # warm sepia accent

os.makedirs(OUT_DIR, exist_ok=True)

def load_font(path, size, index=0):
    try:
        return ImageFont.truetype(path, size, index=index)
    except:
        return ImageFont.load_default()

def darken(img, factor=0.45):
    """Overlay semi-transparent dark gradient"""
    overlay = Image.new("RGBA", img.size, (0, 0, 0, int(255 * factor)))
    img = img.convert("RGBA")
    return Image.alpha_composite(img, overlay).convert("RGB")

def add_bottom_gradient(img, height_ratio=0.3):
    """Add dark gradient at bottom for text readability"""
    w, h = img.size
    grad = Image.new("RGBA", (w, int(h * height_ratio)), (0, 0, 0, 0))
    for y in range(grad.height):
        alpha = int(180 * (y / grad.height))
        ImageDraw.Draw(grad).rectangle([(0, y), (w, y)], fill=(0, 0, 0, alpha))
    img = img.convert("RGBA")
    img.paste(grad, (0, h - grad.height), grad)
    return img.convert("RGB")

def draw_text_centered(draw, text, y, font, color=WHITE, max_w=900):
    """Draw text centered horizontally"""
    bbox = draw.textbbox((0, 0), text, font=font)
    tw = bbox[2] - bbox[0]
    x = (1080 - tw) // 2
    draw.text((x, y), text, fill=color, font=font)

def draw_text_left(draw, text, x, y, font, color=WHITE, max_w=900):
    draw.text((x, y), text, fill=color, font=font)

def wrap_text(text, font, max_w, draw):
    """Simple word wrap"""
    words = text.split()
    lines = []
    line = ""
    for w in words:
        test = line + " " + w if line else w
        tw = draw.textbbox((0, 0), test, font=font)[2]
        if tw <= max_w:
            line = test
        else:
            lines.append(line)
            line = w
    if line:
        lines.append(line)
    return lines

def make_cover():
    """Slide 0: Cover slide with title, dates, CTA"""
    img = Image.new("RGB", (1080, 1080), INK)
    draw = ImageDraw.Draw(img)

    f_title = load_font(FONT_BOLD, 72, index=1)
    f_sub = load_font(FONT_LIGHT, 28)
    f_body = load_font(FONT_LIGHT, 22)
    f_small = load_font(FONT_LIGHT, 18)

    # Accent line top
    draw.rectangle([(0, 0), (1080, 6)], fill=ACCENT)

    # Title
    draw_text_centered(draw, "RETRATS", 240, f_title)
    draw_text_centered(draw, "LENTS", 320, f_title)

    # Subtitle
    draw_text_centered(draw, "Segon repte fotogràfic de 112 Revelats", 410, f_sub, WARM)

    # Dates
    draw_text_centered(draw, "21 juny — 6 setembre 2026", 470, f_body, (180, 180, 180))

    # Divider
    draw.rectangle([(440, 520), (640, 522)], fill=ACCENT)

    # Info lines
    info = [
        "Retrat amb càmera estenopeica o llarga exposició",
        "Obert a tothom · Gratuït",
    ]
    for i, line in enumerate(info):
        draw_text_centered(draw, line, 550 + i * 35, f_small, (160, 160, 160))

    # CTA button
    btn_y = 660
    btn_w, btn_h = 320, 56
    btn_x = (1080 - btn_w) // 2
    draw.rounded_rectangle([(btn_x, btn_y), (btn_x + btn_w, btn_y + btn_h)], radius=28, fill=ACCENT)
    draw_text_centered(draw, "PARTICIPA-HI", btn_y + 14, load_font(FONT_BOLD, 22, index=1), WHITE)

    # Logo text
    draw_text_centered(draw, "112books.eu / 112 Revelats", 960, f_small, (100, 100, 100))

    img.save(f"{OUT_DIR}/00-portada.jpg", quality=92)
    print(f"  -> {OUT_DIR}/00-portada.jpg")

def make_photo_card(src_path, idx, total):
    """Photo slide with subtle branding"""
    photo = Image.open(src_path).convert("RGB")
    # Crop to square from center
    w, h = photo.size
    side = min(w, h)
    left = (w - side) // 2
    top = (h - side) // 2
    photo = photo.crop((left, top, left + side, top + side))
    photo = photo.resize((1080, 1080), Image.LANCZOS)

    # Slight darken for text overlay
    photo = darken(photo, 0.15)

    draw = ImageDraw.Draw(photo)
    f_counter = load_font(FONT_LIGHT, 16)
    f_hashtag = load_font(FONT_LIGHT, 14)

    # Counter top-right
    draw_text_left(draw, f"{idx+1}/{total}", 1010, 30, f_counter, (200, 200, 200))

    # Bottom branding
    draw.text((40, 1020), "112 Revelats", fill=(200, 200, 200), font=f_counter)
    draw.text((40, 1045), "#RetratsLents", fill=(150, 150, 150), font=f_hashtag)

    photo.save(f"{OUT_DIR}/{idx+1:02d}-foto.jpg", quality=92)
    print(f"  -> {OUT_DIR}/{idx+1:02d}-foto.jpg")

def make_cta():
    """Final slide: call to action"""
    img = Image.new("RGB", (1080, 1080), INK)
    draw = ImageDraw.Draw(img)

    f_title = load_font(FONT_BOLD, 48, index=1)
    f_body = load_font(FONT_LIGHT, 24)
    f_small = load_font(FONT_LIGHT, 18)

    draw.rectangle([(0, 0), (1080, 6)], fill=ACCENT)

    draw_text_centered(draw, "TENS UNA CÀMERA", 280, f_title)
    draw_text_centered(draw, "ESTENOPEICA?", 350, f_title)
    draw_text_centered(draw, "O simplement curiositat?", 400, f_body, (180, 180, 180))

    draw.rectangle([(440, 460), (640, 462)], fill=ACCENT)

    lines = [
        "Participa al repte Retrats Lents",
        "Fotollibre col·lectiu · Publicació digital",
        "Inscripció gratuïta fins al 6 de setembre",
    ]
    for i, line in enumerate(lines):
        draw_text_centered(draw, line, 500 + i * 38, f_small, (160, 160, 160))

    # CTA button
    btn_y = 660
    btn_w, btn_h = 320, 56
    btn_x = (1080 - btn_w) // 2
    draw.rounded_rectangle([(btn_x, btn_y), (btn_x + btn_w, btn_y + btn_h)], radius=28, fill=ACCENT)
    draw_text_centered(draw, "112REVELATS.112BOOKS.EU", btn_y + 14, load_font(FONT_BOLD, 18, index=1), WHITE)

    draw_text_centered(draw, "Enllaç al perfil", 790, f_small, (100, 100, 100))

    img.save(f"{OUT_DIR}/cta-final.jpg", quality=92)
    print(f"  -> {OUT_DIR}/cta-final.jpg")

def make_story(src_path, idx):
    """Story format 1080x1920 full-bleed photo with text"""
    photo = Image.open(src_path).convert("RGB")
    # Crop to 9:16 from center
    w, h = photo.size
    target_ratio = 9 / 16
    current_ratio = w / h
    if current_ratio > target_ratio:
        new_w = int(h * target_ratio)
        left = (w - new_w) // 2
        photo = photo.crop((left, 0, left + new_w, h))
    else:
        new_h = int(w / target_ratio)
        top = (h - new_h) // 2
        photo = photo.crop((0, top, w, top + new_h))
    photo = photo.resize((1080, 1920), Image.LANCZOS)

    # Darken for readability
    photo = darken(photo, 0.35)

    # Bottom gradient
    photo = add_bottom_gradient(photo, 0.25)
    # Top gradient
    photo = add_top_gradient(photo, 0.15)

    draw = ImageDraw.Draw(photo)
    f_title = load_font(FONT_BOLD, 42, index=1)
    f_sub = load_font(FONT_LIGHT, 24)
    f_small = load_font(FONT_LIGHT, 16)

    # Top text
    draw_text_centered(draw, "RETRATS LENTS", 80, f_title)
    draw_text_centered(draw, "112 Revelats", 130, f_small, (180, 180, 180))

    # Bottom info
    draw_text_centered(draw, "21 juny — 6 setembre 2026", 1720, f_sub, (220, 220, 220))
    draw_text_centered(draw, "Fotollibre col·lectiu #RetratsLents", 1770, f_small, (160, 160, 160))

    photo.save(f"{OUT_DIR}/story-{idx+1}.jpg", quality=92)
    print(f"  -> {OUT_DIR}/story-{idx+1}.jpg")

def add_top_gradient(img, height_ratio=0.15):
    w, h = img.size
    grad = Image.new("RGBA", (w, int(h * height_ratio)), (0, 0, 0, 0))
    for y in range(grad.height):
        alpha = int(120 * (1 - y / grad.height))
        ImageDraw.Draw(grad).rectangle([(0, y), (w, y)], fill=(0, 0, 0, alpha))
    img = img.convert("RGBA")
    img.paste(grad, (0, 0), grad)
    return img.convert("RGB")

def main():
    print("Generant campanya Instagram per Retrats Lents...\n")

    # Get all images
    images = sorted([f for f in os.listdir(SRC_DIR) if f.endswith(".jpg") and f != "FONTS.md"])
    random.shuffle(images)

    if not images:
        print("ERROR: No s'han trobat imatges a", SRC_DIR)
        return

    # Carousel: cover + up to 8 photos + CTA = max 10 slides
    n_photos = min(8, len(images))
    total_slides = 1 + n_photos + 1  # cover + photos + cta

    print(f"Carrousel ({total_slides} diapositives):")
    make_cover()
    for i in range(n_photos):
        make_photo_card(f"{SRC_DIR}/{images[i]}", i, n_photos)
    make_cta()

    # Stories: 3 story versions
    print(f"\nStories (3):")
    story_images = random.sample(images, min(3, len(images)))
    for i, img_name in enumerate(story_images):
        make_story(f"{SRC_DIR}/{img_name}", i)

    print(f"\nFet! {total_slides + 3} imatges generades a {OUT_DIR}/")

if __name__ == "__main__":
    main()
