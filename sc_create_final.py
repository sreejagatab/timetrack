"""
Final agent: Create remaining 7 apps
Uses direct toolbar clicking + waits for new frames to appear
"""
import asyncio
from playwright.async_api import async_playwright

SC_URL = "http://127.0.0.1:8093/scriptcase/devel/iface/login.php"

CHART_SQLS = {
    'chart_hours_by_project': "SELECT p.project_name, COALESCE(SUM(tl.hours), 0) as total_hours FROM projects p LEFT JOIN timesheet_lines tl ON tl.project_id = p.project_id GROUP BY p.project_name ORDER BY total_hours DESC",
    'chart_hours_by_dept': "SELECT d.department_name, COALESCE(SUM(tl.hours), 0) as total_hours FROM departments d LEFT JOIN employees e ON e.department_id = d.department_id LEFT JOIN timesheets t ON t.employee_id = e.employee_id LEFT JOIN timesheet_lines tl ON tl.timesheet_id = t.timesheet_id GROUP BY d.department_name",
    'chart_overtime_trend': "SELECT tp.period_start::text, COALESCE(SUM(t.total_overtime_hours), 0) as overtime FROM timesheet_periods tp LEFT JOIN timesheets t ON t.period_id = tp.period_id GROUP BY tp.period_start ORDER BY tp.period_start",
    'chart_leave_by_type': "SELECT lt.type_name, COUNT(lr.request_id) as count FROM leave_types lt LEFT JOIN leave_requests lr ON lr.leave_type_id = lt.leave_type_id AND lr.status = 'approved' GROUP BY lt.type_name",
}


async def login_and_open(page):
    print("[1] Login + open project...")
    await page.goto(SC_URL)
    await page.wait_for_load_state("networkidle")
    await asyncio.sleep(2)
    await page.fill("#id_field_user", "admin")
    await page.fill("#id_field_pass", "admin")
    await asyncio.sleep(1)

    # Click the LOG IN button properly (not form.submit which skips JS password hashing)
    try:
        await page.click("#btn_login", timeout=3000)
    except:
        try:
            await page.click("text=LOG IN", timeout=3000)
        except:
            try:
                await page.click("button:has-text('LOG IN')", timeout=3000)
            except:
                # Last resort: trigger the login JS function
                await page.evaluate("""() => {
                    var btn = document.getElementById('btn_login');
                    if (btn) btn.click();
                }""")

    # Wait for navigation after login
    try:
        await page.wait_for_url("**/index.php**", timeout=15000)
    except:
        pass
    await asyncio.sleep(8)

    title = await page.title()
    print(f"  Title: {title}")
    if 'Login' in title:
        print("  ERROR: Login failed!")
        return
    for f in page.frames:
        try:
            el = f.locator("div[onclick*=\"nm_open_project('project\"]").first
            if await el.count() > 0:
                await el.click()
                break
        except:
            pass
    await asyncio.sleep(8)
    # Dismiss chat widget everywhere
    for f in page.frames:
        try:
            await f.evaluate("() => { document.querySelectorAll('[id*=zsiq], .zsiq_floatmain, .siqaio, .zsiq_theme1').forEach(e => e.remove()); }")
        except:
            pass
    print("  OK")


async def get_frame_urls(page):
    """Get set of current non-blank frame URLs"""
    urls = set()
    for f in page.frames:
        if f.url and f.url != 'about:blank':
            urls.add(f.url)
    return urls


async def open_new_app_wizard(page):
    """Click New Application toolbar button and wait for wizard frame"""
    # Record current frames
    before_urls = await get_frame_urls(page)

    # Click via toolbar - find the actual toolbar link
    scase = page.frame('nmFrmScase')
    if not scase:
        print("  ERROR: nmFrmScase not found")
        return None

    # Try clicking "New application" text in the toolbar
    clicked = False
    try:
        el = scase.locator("a:has-text('New application')").first
        if await el.count() > 0:
            await el.click()
            clicked = True
    except:
        pass

    if not clicked:
        # Try via JS
        try:
            await scase.evaluate("() => { nm_exec_menu('app_new'); }")
            clicked = True
        except:
            pass

    if not clicked:
        print("  ERROR: Can't open wizard")
        return None

    # Wait for new frame to appear
    for attempt in range(15):
        await asyncio.sleep(1)
        after_urls = await get_frame_urls(page)
        new_urls = after_urls - before_urls
        if new_urls:
            # Find the new frame
            for f in page.frames:
                if f.url in new_urls and 'wizard' in f.url:
                    return f
            # If no wizard URL, check content
            for f in page.frames:
                if f.url in new_urls:
                    try:
                        text = await f.locator("body").text_content()
                        if text and ('Chart' in text or 'Blank' in text or 'Calendar' in text or 'NEW APPLICATION' in text):
                            return f
                    except:
                        pass

    # Last resort: check ALL frames for wizard content
    for f in page.frames:
        if f.url and f.url != 'about:blank':
            try:
                text = await f.locator("body").text_content()
                if text and 'NEW APPLICATION' in text and 'Chart' in text and 'Blank' in text:
                    return f
            except:
                pass

    print("  WARN: No wizard frame detected")
    return None


async def click_app_type(wf, type_name):
    """Click an app type card in the wizard using JS"""
    result = await wf.evaluate("""(typeName) => {
        // Strategy 1: Find card by header text
        var headers = document.querySelectorAll('.header, h3, h4, div');
        for (var h of headers) {
            if (h.textContent.trim() === typeName) {
                // Click the parent card/content div
                var parent = h.closest('.content') || h.closest('.card') || h.parentElement;
                if (parent) { parent.click(); return 'parent'; }
                h.click();
                return 'header';
            }
        }
        // Strategy 2: Partial match for truncated names like "Bla..."
        for (var h of headers) {
            var t = h.textContent.trim();
            if (t.startsWith(typeName.substring(0, 3)) && t.length < typeName.length + 5) {
                var parent = h.closest('.content') || h.closest('.card') || h.parentElement;
                if (parent) { parent.click(); return 'partial-parent'; }
                h.click();
                return 'partial';
            }
        }
        return null;
    }""", type_name)
    return result is not None


async def fill_name_and_create(page, name, sql=None):
    """Fill app name, optionally SQL, and click Create"""
    await asyncio.sleep(2)

    filled_name = False
    filled_sql = False

    for f in page.frames:
        if f.url == 'about:blank':
            continue
        try:
            inp = f.locator("input[name='cod_apl']").first
            if await inp.count() > 0:
                await inp.fill(name)
                filled_name = True

                if sql:
                    ta = f.locator("textarea").first
                    if await ta.count() > 0:
                        await ta.fill(sql)
                        filled_sql = True

                # Click Create via JS in this frame
                await asyncio.sleep(1)
                clicked = await f.evaluate("""() => {
                    // Find Create button
                    var btns = document.querySelectorAll('button, a, span, input');
                    for (var b of btns) {
                        var txt = (b.textContent || b.value || '').trim();
                        if (txt === 'Create') {
                            b.click();
                            return true;
                        }
                    }
                    return false;
                }""")

                if clicked:
                    await asyncio.sleep(4)
                    return True
                break
        except:
            pass

    if not filled_name:
        return False

    # If JS click didn't work, try Playwright click
    for f in page.frames:
        if f.url == 'about:blank':
            continue
        try:
            for sel in ["button:has-text('Create')", "a:has-text('Create')", "span:has-text('Create')", "input[value='Create']"]:
                btn = f.locator(sel).first
                if await btn.count() > 0:
                    await btn.click()
                    await asyncio.sleep(4)
                    return True
        except:
            pass

    return False


async def create_app(page, type_name, app_name, sql=None):
    """Create a single application"""
    print(f"\n  >> {app_name} ({type_name})...", end=" ", flush=True)

    wf = await open_new_app_wizard(page)
    if not wf:
        # Take screenshot for debugging
        await page.screenshot(path=f"H:/scriptcase/screenshots/final_{app_name}_err.png")
        print("SKIP: no wizard")
        return False

    await page.screenshot(path=f"H:/scriptcase/screenshots/final_{app_name}_01.png")

    # Click the app type
    result = await click_app_type(wf, type_name)
    if not result:
        print(f"SKIP: can't click {type_name}")
        return False

    await asyncio.sleep(3)
    await page.screenshot(path=f"H:/scriptcase/screenshots/final_{app_name}_02.png")

    # Fill name + SQL and create
    if await fill_name_and_create(page, app_name, sql):
        print("OK")
        return True

    print("SKIP: create failed")
    return False


async def generate_all(page):
    print("\n\n[4] Generating all apps...")
    scase = page.frame('nmFrmScase')
    if scase:
        try:
            await scase.evaluate("() => { nm_exec_menu('app_home'); }")
        except:
            pass
    await asyncio.sleep(5)

    home = None
    for f in page.frames:
        if f.url and 'open_app' in f.url:
            home = f
            break
    if not home:
        print("  Can't find home")
        return

    cbs = await home.locator("input[type='checkbox']").all()
    for cb in cbs:
        try:
            if not await cb.is_checked():
                await cb.check()
        except:
            pass

    try:
        await home.click("text=Generate")
        print("  Started")
    except:
        print("  Can't click Generate")
        return

    for i in range(90):
        await asyncio.sleep(5)
        for f in page.frames:
            try:
                text = await f.locator("body").text_content()
                if text and 'successful' in text.lower():
                    print(f"  Complete! ({(i+1)*5}s)")
                    await page.screenshot(path="H:/scriptcase/screenshots/final_gen_done.png")
                    return
            except:
                pass
        if i % 6 == 0 and i > 0:
            print(f"  Generating... ({(i+1)*5}s)")


async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=False, slow_mo=200,
                                          args=['--window-size=1400,900'])
        context = await browser.new_context(
            viewport={'width': 1400, 'height': 900},
            record_video_dir="H:/scriptcase/videos/final2/"
        )
        page = await context.new_page()
        await login_and_open(page)

        print("\n[3] Creating 7 remaining apps...")
        ok = 0

        # 1. Blank
        if await create_app(page, 'Blank', 'ctrl_clock_inout'):
            ok += 1

        # 2-3. Calendars
        if await create_app(page, 'Calendar', 'cal_holidays'):
            ok += 1
        if await create_app(page, 'Calendar', 'cal_leave'):
            ok += 1

        # 4-7. Charts
        for name, sql in CHART_SQLS.items():
            if await create_app(page, 'Chart', name, sql):
                ok += 1

        print(f"\n\nCreated: {ok} / 7")

        if ok > 0:
            await generate_all(page)

        # Copy video to desktop
        await asyncio.sleep(3)
        await context.close()
        await browser.close()

    print("\nDone! Video saved to H:/scriptcase/videos/final2/")

if __name__ == "__main__":
    asyncio.run(main())
