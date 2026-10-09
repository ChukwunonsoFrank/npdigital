import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8899';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 690, height: 1000 } });

await page.goto(base + '/login');
await page.fill('input[type="email"], #email', 'amp-visual-check@example.com');
await page.fill('input[type="password"], #password', 'password');
await page.click('button[type="submit"]');
await page.waitForURL('**/dashboard**', { timeout: 15000 });

await page.goto(base + '/dashboard/account');
await page.waitForLoadState('networkidle');

// Closed accordion state (default)
await page.screenshot({ path: '.amp/in/account-closed.png', fullPage: false });

// Open the accordion
await page.click('button:has-text("Account Details")');
await page.waitForTimeout(300);

// Start editing the full name
await page.click('button[aria-label="Edit Full Name"]');
await page.waitForTimeout(500);

// Screenshot the editing row
const row = page.locator('div.flex.items-center.justify-between:has(input[wire\\:model])').first();
await row.screenshot({ path: '.amp/in/account-editing-row.png' });
await page.screenshot({ path: '.amp/in/account-editing.png', fullPage: false });

await browser.close();
console.log('done');
