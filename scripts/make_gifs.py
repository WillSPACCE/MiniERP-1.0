#!/usr/bin/env python3
"""
scripts/make_gifs.py

Gera GIFs animados simples a partir das imagens em `mini-erp-web/prints/`.
Requisitos: Pillow (`pip install pillow`).

Uso:
  python scripts/make_gifs.py --output docs/assets --frames "mini-erp-web/prints/dashboard desktop.jpg,mini-erp-web/prints/notebook.jpg"

O script cria `output/<name>.gif` para cada lista de frames informada.
"""
import os
import argparse
from PIL import Image

def make_gif(frames, out_path, duration=800):
    imgs = [Image.open(f).convert('RGBA') for f in frames]
    imgs[0].save(out_path, save_all=True, append_images=imgs[1:], duration=duration, loop=0, disposal=2)

def main():
    p=argparse.ArgumentParser()
    p.add_argument('--output',default='docs/assets',help='pasta de saída')
    p.add_argument('--frames',required=True,help='lista separada por vírgula de caminhos de imagens')
    p.add_argument('--duration',type=int,default=800,help='duração por frame (ms)')
    args=p.parse_args()
    os.makedirs(args.output,exist_ok=True)
    frames=[f.strip() for f in args.frames.split(',') if f.strip()]
    if not frames:
        print('Nenhuma frame encontrada')
        return
    name=os.path.splitext(os.path.basename(frames[0]))[0]
    out=os.path.join(args.output, f"{name}.gif")
    print('Gerando', out)
    make_gif(frames,out,args.duration)
    print('Concluído')

if __name__=='__main__':
    main()
