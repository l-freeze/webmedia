
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path('/mnt/data/webmedia_html_design')
front=root/'front'; cms=root/'cms'; shot=root/'screenshots'
front_pages=['index','article','category-index','category-detail','tag-index','tag-detail','search-results','search-none','contact','contact-error','contact-success','fixed-about','fixed-privacy','error-404','state-loading','state-no-articles','mobile-menu']
cms_pages=['login','dashboard','articles','editor','preview','history','fixed-pages','taxonomy','media','inquiries','settings','trash']
viewports={'pc':(1440,1200),'sp':(390,1200)}
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path="/usr/bin/chromium", args=["--no-sandbox","--disable-dev-shm-usage"])
    for kind, pages, base in [('front',front_pages,front),('cms',cms_pages,cms)]:
        for vp,(w,h) in viewports.items():
            page=browser.new_page(viewport={'width':w,'height':h}, device_scale_factor=1)
            for name in pages:
                url=f'http://127.0.0.1:8765/{kind}/{name}.html'
                page.goto(url, wait_until='networkidle')
                page.screenshot(path=str(shot/f'{kind}_{vp}'/f'{name}_{vp}.png'), full_page=True)
            page.close()
    browser.close()
