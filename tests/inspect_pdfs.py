"""Render every output page and check physical A4 dimensions and content bounds."""
from pathlib import Path
import json
import subprocess
import pdfplumber
from PIL import Image, ImageDraw

root = Path(__file__).resolve().parents[1]
source = root / "output/pdf/doi-chieu-18-mau"
out = root / "tmp/photo-form-review"
out.mkdir(parents=True, exist_ok=True)
report = []
for path in sorted(source.glob("*.pdf")):
    doc = pdfplumber.open(path)
    subprocess.run(['pdftoppm','-r','90','-png',str(path),str(out / path.stem)],check=True,capture_output=True)
    pages = []
    for i, page in enumerate(doc.pages):
        w, h = sorted((page.width, page.height))
        assert abs(w - 595.276) < 1 and abs(h - 841.89) < 1, (path.name, i, w, h)
        digits = len(str(len(doc.pages)))
        png = out / f"{path.stem}-{i+1:0{digits}}.png"
        pages.append(png)
        for char in page.chars:
            if not char['text'].strip() or not char.get('upright',True):
                continue
            assert char['x0'] >= -1 and char['top'] >= -1 and char['x1'] <= page.width+1 and char['bottom'] <= page.height+1, (path.name,i+1,char['text'])
    cols = 3
    cell_w, cell_h = 400, 570
    sheet = Image.new("RGB", (cols*cell_w, ((len(pages)+cols-1)//cols)*cell_h), "#ddd")
    draw = ImageDraw.Draw(sheet)
    for i, png in enumerate(pages):
        im = Image.open(png)
        im.thumbnail((390, 540))
        x, y = (i % cols)*cell_w, (i//cols)*cell_h
        sheet.paste(im, (x+(cell_w-im.width)//2, y+25))
        draw.text((x+10,y+7), f"{path.stem} / {i+1}", fill="black")
    sheet.save(out / f"{path.stem}-contact.png")
    report.append({"file":path.name,"pages":len(doc.pages),"A4":True})
    doc.close()
(out / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps(report, ensure_ascii=False, indent=2))
