from pathlib import Path
from weasyprint import HTML, CSS
import fitz
root=Path('/mnt/data/webmedia_html_design')
shot=root/'screenshots'
for d in [shot/'front_pc',shot/'front_sp',shot/'cms_pc',shot/'cms_sp']:
    d.mkdir(parents=True, exist_ok=True)
front_pages=['index','article','category-index','category-detail','tag-index','tag-detail','search-results','search-none','contact','contact-error','contact-success','fixed-about','fixed-privacy','error-404','state-loading','state-no-articles','mobile-menu']
cms_pages=['login','dashboard','articles','editor','preview','history','fixed-pages','taxonomy','media','inquiries','settings','trash']
viewports={'pc':(1440,1200),'sp':(390,1200)}
for kind,pages in [('front',front_pages),('cms',cms_pages)]:
    base=root/kind
    for vp,(w,h) in viewports.items():
        css=CSS(string=f'@page {{ size: {w}px {h}px; margin: 0; }} html, body {{ width: {w}px; min-height:{h}px; }}')
        for name in pages:
            html_path=base/f'{name}.html'
            pdf_path=shot/f'tmp_{kind}_{vp}_{name}.pdf'
            HTML(filename=str(html_path), base_url=str(base)).write_pdf(str(pdf_path), stylesheets=[css], presentational_hints=True)
            doc=fitz.open(str(pdf_path))
            page=doc.load_page(0)
            pix=page.get_pixmap(matrix=fitz.Matrix(1,1), alpha=False)
            out=shot/f'{kind}_{vp}'/f'{name}_{vp}.png'
            pix.save(str(out))
            doc.close(); pdf_path.unlink(missing_ok=True)
