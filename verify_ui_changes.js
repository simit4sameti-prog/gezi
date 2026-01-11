
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Load the application
    await page.goto('file://' + process.cwd() + '/index.html');

    // 1. Login Logic
    // Wait for the Login Screen to appear
    await page.waitForSelector('text=Kayıt Ol');
    await page.click('text=Kayıt Ol');

    const username = 'testuser_' + Date.now();
    await page.fill('input[placeholder="Kullanıcı Adı"]', username);
    await page.selectOption('select', { index: 1 });
    await page.fill('input[placeholder="Cevabınız"]', 'answer');
    await page.fill('input[placeholder="Şifre"]', 'password');
    await page.click('button:has-text("Hesap Oluştur")');

    // Wait for map container (MainApp loaded)
    await page.waitForSelector('#map-container');
    console.log('Login successful.');

    // 2. Verify "Mod" and "City Names" buttons removal from Sidebar
    // The "Mod" button had text "Mod".
    const modButton = await page.$('button:has-text("Mod")');
    if (modButton) {
        // Double check visibility
        const isVisible = await modButton.isVisible();
        if (isVisible) {
            console.error('FAIL: "Mod" button is still visible.');
            process.exit(1);
        }
    }
    console.log('PASS: "Mod" button removed.');

    // The "City Names" button had title "Şehir İsimlerini Göster/Gizle" or icon "type"
    const typeButton = await page.$('button[title="Şehir İsimlerini Göster/Gizle"]');
    if (typeButton) {
        if (await typeButton.isVisible()) {
             console.error('FAIL: "City Names" button is still visible.');
             process.exit(1);
        }
    }
    console.log('PASS: "City Names" button removed.');

    // 3. Open Settings Modal
    // Finding the settings button.
    // In code: <button onClick={()=>setIsSettingsOpen(true)} ... title="Ayarlar">
    // So we search by title "Ayarlar"
    await page.click('button[title="Ayarlar"]');

    await page.waitForSelector('text=Ayarlar');
    console.log('Settings Modal opened.');

    // 4. Check for New Toggles in Settings
    const darkModeToggle = await page.$('text=Karanlık Mod');
    const cityNamesToggle = await page.$('text=Şehir İsimlerini Göster');

    if (!darkModeToggle) {
        console.error('FAIL: "Karanlık Mod" toggle not found in settings.');
        process.exit(1);
    }
    if (!cityNamesToggle) {
        console.error('FAIL: "Şehir İsimlerini Göster" toggle not found in settings.');
        process.exit(1);
    }
    console.log('PASS: New toggles found in Settings.');

    // 5. Check Notification Modal
    // Close settings first (Click close button inside modal)
    // The close button has an Icon with name="x".
    // We can click the button in the settings modal.
    // The settings modal is the last opened modal.
    // It has a title "Ayarlar" and an X button.
    // Let's click the X button within the Settings Modal container.
    // Or just click the backdrop? No, backdrop doesn't close it in code? Wait, code says backdrop-blur-sm but no onclick on backdrop div for settings.
    // The X button is: <button onClick={onClose} ...><Icon name="x" .../></button>
    // We can use page.click('button:has(svg.lucide-x)'); but there might be multiple if other modals are hidden but present? No, conditions usually remove them.
    // Safe bet:
    await page.click('div.fixed.z-\\[1700\\] button.absolute');

    // Open Notifications
    // Button with bell icon. In the top sidebar area.
    // <button onClick={()=>setIsNotifOpen(!isNotifOpen)} ...>
    // It's the button containing bell icon.
    await page.click('button:has(svg.lucide-bell)');

    await page.waitForSelector('text=Bildirimler');

    const notifEmpty = await page.$('text=Bildirim yok.');
    if (!notifEmpty) {
        // If there are notifications (unlikely for new user), check list.
        console.log('Notification list is not empty or text changed.');
    } else {
        console.log('PASS: Notification Modal opens and is empty.');
    }

    console.log('All verifications passed.');
    await browser.close();
})();
