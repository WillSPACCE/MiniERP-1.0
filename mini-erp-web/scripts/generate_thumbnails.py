#!/usr/bin/env python3
from PIL import Image
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PRINTS_DIR = os.path.join(ROOT, 'prints')
ASSETS_IMAGES = os.path.join(ROOT, 'public', 'assets', 'images')
THUMBS_DIR = os.path.join(ASSETS_IMAGES, 'thumbs')

os.makedirs(THUMBS_DIR, exist_ok=True)

def sanitize_name(name):
    return name.replace(' ', '_')

def make_thumbnail(src_path, dst_path, size=(800,450)):
    try:
        with Image.open(src_path) as im:
            im.thumbnail(size, Image.LANCZOS)
            im.convert('RGB').save(dst_path, 'JPEG', quality=85)
        print(f"Created thumbnail: {dst_path}")
    except Exception as e:
        print(f"Failed to create thumbnail for {src_path}: {e}")

def copy_as(src, dst, convert_gif=False):
    try:
        with Image.open(src) as im:
            if convert_gif:
                im.save(dst, 'GIF')
            else:
                im.save(dst)
        print(f"Copied/converted logo: {dst}")
    except Exception as e:
        # fallback to raw copy
        try:
            import shutil
            shutil.copy2(src, dst)
            print(f"Copied file: {dst}")
        except Exception as e2:
            print(f"Failed to copy {src} -> {dst}: {e} / {e2}")

def main():
    # process prints
    exts = ('.jpg', '.jpeg', '.png', '.webp')
    if os.path.isdir(PRINTS_DIR):
        for fname in os.listdir(PRINTS_DIR):
            if fname.lower().endswith(exts):
                src = os.path.join(PRINTS_DIR, fname)
                base = sanitize_name(os.path.splitext(fname)[0])
                dst = os.path.join(THUMBS_DIR, f"{base}_thumb.jpg")
                make_thumbnail(src, dst)
    else:
        print(f"Prints directory not found: {PRINTS_DIR}")

    # logos
    logos = {
        'logo_blue.png': 'mini-erp-logo.png',
        'logo_white.png': 'LOGO.png',
        'logo.gif': 'gif_logo.png',
    }
    for out_name, src_name in logos.items():
        src = os.path.join(ASSETS_IMAGES, src_name)
        dst = os.path.join(ASSETS_IMAGES, out_name)
        if os.path.exists(src):
            convert_gif = out_name.lower().endswith('.gif')
            copy_as(src, dst, convert_gif=convert_gif)
        else:
            print(f"Logo source not found: {src}")

    print("Done.")

if __name__ == '__main__':
    main()
