"""
ScriptCase AI Agent - Batch create all applications using Express Creation
"""
import asyncio
from playwright.async_api import async_playwright

SC_URL = "http://127.0.0.1:8093/scriptcase/devel/iface/login.php"
SC_USER = "admin"
SC_PASS = "admin"

# Tables to select (exclude sec_* tables since they already have apps)
TABLES_TO_SELECT = [
    'public.activity_types',
    'public.audit_log',
    'public.clients',
    'public.clock_entries',
    'public.departments',
    'public.employee_sec_link',
    'public.employees',
    'public.holidays',
    'public.leave_approvals',
    'public.leave_balances',
    'public.leave_policies',
    'public.leave_requests',
    'public.leave_types',
    'public.notifications',
    'public.overtime_rules',
    'public.project_members',
    'public.project_tasks',
    'public.projects',
    'public.system_config',
    'public.timesheet_lines',
    'public.timesheet_periods',
    'public.timesheets',
    # Views
    'public.v_current_clock_status',
    'public.v_employee_directory',
    'public.v_leave_balance_current',
    'public.v_leave_request_full',
    'public.v_project_hours_summary',
    'public.v_timesheet_summary',
]


async def login(page):
    print("[1] Logging in...")
    await page.goto(SC_URL)
    await page.wait_for_load_state("networkidle")
    await asyncio.sleep(2)

    await page.fill("#id_field_user", SC_USER)
    await page.fill("#id_field_pass", SC_PASS)
    await page.evaluate("""() => {
        var form = document.querySelector('form[name="form_login"]');
        if (form) { form.onsubmit = null; form.submit(); }
    }""")

    await asyncio.sleep(5)
    await page.wait_for_load_state("networkidle")
    await asyncio.sleep(3)

    title = await page.title()
    print(f"  Title: {title}")
    return "Login" not in title


async def open_project(page):
    print("[2] Opening project...")
    for f in page.frames:
        try:
            el = f.locator("div[onclick*=\"nm_open_project('project\"]").first
            if await el.count() > 0:
                await el.click()
                print("  Clicked project card")
                break
        except:
            pass
    await asyncio.sleep(8)
    print("  Project opened")


async def open_batch_wizard(page):
    print("[3] Opening Batch Applications wizard...")
    main_frame = None
    for f in page.frames:
        if f.name == 'nmFrmScase':
            main_frame = f
            break

    if main_frame:
        await main_frame.evaluate("""() => {
            try { nm_exec_menu('app_new_batch'); }
            catch(e) { parent.nm_exec_menu('app_new_batch'); }
        }""")
    await asyncio.sleep(5)
    print("  Batch wizard opened")


async def select_tables_and_create(page):
    print("[4] Selecting tables...")

    # Find the batch wizard frame (nmFrmBot2 or similar)
    batch_frame = None
    for f in page.frames:
        if f.url and 'create_type=express' in f.url:
            batch_frame = f
            break

    if not batch_frame:
        # Try to find frame with "Express creation" content
        for f in page.frames:
            if f.url and f.url != 'about:blank':
                try:
                    text = await f.locator("body").text_content()
                    if text and 'Express creation' in text:
                        batch_frame = f
                        break
                except:
                    pass

    if not batch_frame:
        print("  ERROR: Cannot find batch wizard frame!")
        return False

    print(f"  Found batch frame: {batch_frame.name}")

    # Get all checkboxes for tables
    checkboxes = await batch_frame.locator("input[type='checkbox']").all()
    print(f"  Found {len(checkboxes)} checkboxes")

    # First, uncheck any that are already checked (start clean)
    # Then check only our tables

    # Method 1: Click "Select All" first, then uncheck sec_* tables
    # The select-all checkbox is at the bottom
    # Actually, let's just check each table individually

    for table in TABLES_TO_SELECT:
        try:
            # Find the checkbox next to this table name
            # The label text is next to the checkbox
            label = batch_frame.locator(f"text={table}")
            if await label.count() > 0:
                # Click the checkbox - it's the input before or near the label
                # Try clicking the label itself (often toggles checkbox)
                checkbox = batch_frame.locator(f"input[type='checkbox'][value*='{table}'], input[type='checkbox'][value*='{table.split('.')[1]}']").first
                if await checkbox.count() > 0:
                    await checkbox.check()
                    print(f"    Checked: {table}")
                else:
                    # Try clicking the text label directly
                    await label.click()
                    print(f"    Clicked label: {table}")
            else:
                print(f"    NOT FOUND: {table}")
        except Exception as e:
            print(f"    ERROR checking {table}: {e}")

    await asyncio.sleep(2)
    await page.screenshot(path="H:/scriptcase/screenshots/07_tables_selected.png")

    # Count how many are checked
    checked = await batch_frame.locator("input[type='checkbox']:checked").count()
    print(f"\n  Total checked: {checked}")

    # Click Next
    print("\n[5] Clicking Next...")
    try:
        await batch_frame.click("text=Next")
        await asyncio.sleep(5)
        await page.screenshot(path="H:/scriptcase/screenshots/08_after_next.png")
        print("  Clicked Next")
    except:
        try:
            await batch_frame.click("button:has-text('Next')")
            await asyncio.sleep(5)
            print("  Clicked Next button")
        except:
            print("  ERROR: Could not click Next")
            return False

    # After Next, there might be more wizard steps
    # Take screenshot to see what's next
    await page.screenshot(path="H:/scriptcase/screenshots/09_wizard_step2.png")

    # Check what the next step shows
    for f in page.frames:
        if f.url and f.url != 'about:blank':
            try:
                text = await f.locator("body").text_content()
                if text and ('Create' in text or 'Generate' in text or 'Finish' in text or 'Next' in text):
                    text_clean = ' '.join(text.split())[:300]
                    if 'Express' in text or 'batch' in text.lower() or 'wizard' in f.url:
                        print(f"  [{f.name}] {text_clean}")

                        # Look for Create/Finish/Next buttons
                        for btn_text in ['Create', 'Finish', 'Generate', 'Next', 'Ok', 'OK']:
                            btn = f.locator(f"text={btn_text}").first
                            if await btn.count() > 0:
                                tag = await btn.evaluate("el => el.tagName")
                                print(f"    Found button: '{btn_text}' [{tag}]")
            except:
                pass

    # Try to proceed through remaining wizard steps
    # Keep clicking Next/Create/Finish until done
    for step in range(5):
        await asyncio.sleep(3)
        found_btn = False
        for f in page.frames:
            if f.url and ('express' in f.url or 'wizard' in f.url or 'batch' in f.url):
                for btn_text in ['Create', 'Finish', 'Next', 'Generate']:
                    try:
                        btn = f.locator(f"text='{btn_text}'").first
                        if await btn.count() > 0:
                            await btn.click()
                            print(f"  Step {step+1}: Clicked '{btn_text}'")
                            found_btn = True
                            break
                    except:
                        pass
                    try:
                        btn = f.locator(f"button:has-text('{btn_text}'), a:has-text('{btn_text}')").first
                        if await btn.count() > 0:
                            await btn.click()
                            print(f"  Step {step+1}: Clicked '{btn_text}'")
                            found_btn = True
                            break
                    except:
                        pass
            if found_btn:
                break

        await page.screenshot(path=f"H:/scriptcase/screenshots/10_step_{step+1}.png")

        if not found_btn:
            print(f"  No more buttons found at step {step+1}")
            break

    # Wait for generation to complete
    print("\n[6] Waiting for generation to complete...")
    await asyncio.sleep(10)
    await page.screenshot(path="H:/scriptcase/screenshots/11_final.png")

    return True


async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=False,
            slow_mo=150,
            args=['--window-size=1400,900']
        )
        context = await browser.new_context(viewport={'width': 1400, 'height': 900})
        page = await context.new_page()

        if not await login(page):
            print("Login failed!")
            await browser.close()
            return

        await open_project(page)
        await open_batch_wizard(page)
        success = await select_tables_and_create(page)

        if success:
            print("\n=== BATCH CREATION COMPLETE ===")
        else:
            print("\n=== BATCH CREATION HAD ISSUES - CHECK SCREENSHOTS ===")

        await asyncio.sleep(5)
        await browser.close()

    print("\nDone. Check H:/scriptcase/screenshots/")


if __name__ == "__main__":
    asyncio.run(main())
