const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const os = require('node:os');
const fixtureDir = process.env.RESPONSIVE_FIXTURES || path.join(os.tmpdir(), 'smartprobook-responsive');
const publicDir = path.resolve(__dirname, '../../public');
(async () => {
 const browser = await chromium.launch({executablePath:process.env.CHROMIUM_EXECUTABLE || undefined, headless:true});
 const checks=[]; const failures=[];
 for (const [name,url,fixture,blocked] of [['home','/','home'],['about','/about-us','about'],['pricing','/membership-plans','pricing'],['dashboard','/agent/dashboard','dashboard'],['manager','/agent/dashboard','manager'],['dashboard-storage','/agent/dashboard','dashboard',true]]) {
  const context=await browser.newContext({viewport:{width:800,height:1024},hasTouch:true,isMobile:true,reducedMotion:'reduce'});
  await context.route('**/*',async route=>{
   const u=new URL(route.request().url());
   if(u.hostname!=='localhost') return route.abort();
   if(u.pathname===url) return route.fulfill({contentType:'text/html',body:fs.readFileSync(path.join(fixtureDir, `${fixture}.html`))});
   if(u.pathname.startsWith('/assets/')) {
    const file=path.join(publicDir,u.pathname);
    if(fs.existsSync(file)) {
     const ext=path.extname(file); const contentType={'.js':'application/javascript','.css':'text/css','.png':'image/png','.svg':'image/svg+xml','.woff2':'font/woff2','.jpg':'image/jpeg','.woff':'font/woff','.ttf':'font/ttf'}[ext]||'application/octet-stream';
     return route.fulfill({contentType,body:fs.readFileSync(file)});
    }
   }
   return route.fulfill({contentType:'application/json',body:'{}'});
  });
  if(blocked) await context.addInitScript(() => Object.defineProperty(window, 'localStorage', {get(){throw new Error('storage blocked for test')}}));
  const page=await context.newPage(); page.setDefaultTimeout(4000); page.setDefaultNavigationTimeout(30000);
  const errors=[]; page.on('pageerror', e=>errors.push(e.message));
  await page.goto('http://localhost'+url,{waitUntil:'load'});
  for(const width of [320,360,390,600,768,800,820,991,992,1024,1180,1199,1200,1280,1440,1920]) {
   try {
    await page.setViewportSize({width,height:1024});
    await page.waitForTimeout(80); await page.keyboard.press('Escape');
    const geometry=await page.evaluate(()=>({viewport:innerWidth,scroll:document.documentElement.scrollWidth}));
    assert(geometry.scroll <= width+1,`${name} ${width}: horizontal overflow ${geometry.scroll}`);
    if (['dashboard','manager','dashboard-storage'].includes(name)) {
     const content = await page.locator('.page-wrapper').first().boundingBox();
     assert(content.x >= -1 && content.x + content.width <= width + 1, `${name} ${width}: content is outside viewport`);
    }
    if(name!=='pricing') {
     const toggle=page.locator(['dashboard','manager','dashboard-storage'].includes(name)?'#mobile_btn':'[data-public-menu-toggle]');
     if(width<1200) {
      await toggle.tap();
      await page.waitForTimeout(360);
      assert.equal(await toggle.getAttribute('aria-expanded'),'true',`${name} ${width}: tap did not open`);
      const menu=page.locator(['dashboard','manager','dashboard-storage'].includes(name)?'#sidebar, #deploymentSidebar':'#'+await toggle.getAttribute('aria-controls')).first();
      assert(await menu.isVisible(),`${name} ${width}: menu invisible`);
      const rect=await menu.boundingBox();
      assert(rect.x>=-1 && rect.x+rect.width<=width+1,`${name} ${width}: menu offscreen ${JSON.stringify(rect)}`);
      await page.setViewportSize({width,height:900});
      assert.equal(await toggle.getAttribute('aria-expanded'),'true',`${name} ${width}: toolbar resize closed menu`);
      await page.keyboard.press('Escape');
      assert.equal(await toggle.getAttribute('aria-expanded'),'false',`${name} ${width}: Escape failed`);
      await toggle.tap(); await toggle.tap();
      assert.equal(await toggle.getAttribute('aria-expanded'),'false',`${name} ${width}: second tap failed`);
     } else {
      assert(!await toggle.isVisible(),`${name} ${width}: compact toggle visible on desktop`);
      if(['dashboard','manager','dashboard-storage'].includes(name)) {
       const desktop=page.locator('#toggle_btn');
       const before=await page.locator('body').evaluate(el=>el.classList.contains('sidebar-collapsed'));
       await desktop.tap();
       const after=await page.locator('body').evaluate(el=>el.classList.contains('sidebar-collapsed'));
       assert.notEqual(after,before,`${name} ${width}: desktop toggle failed`);
      }
     }
    }
    checks.push(`${name} ${width}`);
   }catch(e){failures.push(e.message);}
  }
  await page.setViewportSize({width:800,height:1024});
  await page.screenshot({path:path.join(fixtureDir, `${name}-tablet.png`),fullPage:false});
  await page.setViewportSize({width:390,height:844});
  await page.screenshot({path:path.join(fixtureDir, `${name}-phone.png`),fullPage:false});
  if(name==='home'||['dashboard','manager','dashboard-storage'].includes(name)) {
   // Rotation across the breakpoint must release the scroll lock.
   await page.goto('http://localhost'+url,{waitUntil:'load'});
   const toggle=page.locator(['dashboard','manager','dashboard-storage'].includes(name)?'#mobile_btn':'[data-public-menu-toggle]');
   await toggle.tap(); await page.setViewportSize({width:1280,height:800});
   assert.equal(await toggle.getAttribute('aria-expanded'),'false');
   assert(!await page.locator('body').evaluate(el=>el.classList.contains('landing-menu-open')||el.classList.contains('sidebar-mobile-open')));
  }
  if (errors.length) failures.push(`${name}: JavaScript errors: ${[...new Set(errors)].join('; ')}`);
  console.log(name, JSON.stringify({passed:checks.filter(s=>s.startsWith(name+' ')).length,errors:[...new Set(errors)].slice(0,5)}));
  await context.close();
 }
 await browser.close();
 console.log(JSON.stringify({passed:checks.length,failures},null,2));
 if(failures.length) process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
