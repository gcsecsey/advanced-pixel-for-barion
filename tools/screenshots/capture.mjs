#!/usr/bin/env node
/**
 * Drives the Playground demo store to each screenshot-worthy state.
 *
 * Two modes:
 *   --check   headless; runs every scene, dumps the console output it saw and
 *             writes page-only PNGs to tools/screenshots/out/. Use this to
 *             confirm the events actually fire before capturing for real.
 *   (default) headed Chromium with DevTools open; pauses at each scene so you
 *             can capture the whole window (Cmd+Shift+4, then Space).
 *
 * Playwright screenshots never include the DevTools panel, which is where the
 * Barion event log lives — hence the manual capture step for scenes 2-5.
 */
import { chromium } from 'playwright';
import readline from 'node:readline/promises';
import { mkdir, writeFile } from 'node:fs/promises';
import { stdin, stdout } from 'node:process';

const BASE = process.env.PLAYGROUND_URL ?? 'http://127.0.0.1:9400';
const CHECK = process.argv.includes('--check');
const OUT = new URL('./out/', import.meta.url).pathname;
const ASSETS = new URL('../../.wordpress-org/', import.meta.url).pathname;

const rl = CHECK ? null : readline.createInterface({ input: stdin, output: stdout });

let log = [];
const scenes = [];

/** Console lines seen since the last reset, filtered to the ones we care about. */
function interesting() {
	return log.filter((l) => /barion|pixel|testing message|sending message/i.test(l));
}

async function pause(n, total, what, capture) {
	const seen = interesting();
	console.log(`\n${'─'.repeat(70)}`);
	console.log(`SCENE ${n}/${total} — ${what}`);
	console.log(`saves to: .wordpress-org/screenshot-${capture}.png`);
	if (seen.length) {
		console.log('\nconsole:');
		seen.forEach((l) => console.log(`  ${l}`));
	} else {
		console.log('\nconsole: (nothing Barion-related seen)');
	}
	console.log('─'.repeat(70));
	if (!CHECK) await rl.question('Press Enter once captured… ');
}

function scene(n, what, capture, fn) {
	scenes.push({ n, what, capture, fn });
}

// ---------------------------------------------------------------- the scenes

scene(1, 'Cookie banner visible, consent not yet given', 5, async (page) => {
	await page.goto(`${BASE}/shop/`, { waitUntil: 'networkidle' });
	await page.waitForSelector('#demo-consent-banner', { state: 'visible', timeout: 15000 });
});

scene(2, 'Settings > Barion Pixel', 1, async (page) => {
	await page.goto(`${BASE}/wp-admin/options-general.php?page=advanced-pixel-for-barion`, {
		waitUntil: 'networkidle',
	});
	await page.waitForSelector('input[name="wc_barion_pixel_settings[pixel_id]"]');
	// This scene has no console content, so --check captures the final file
	// directly, at 2x. A headed run must not overwrite it with a 1x version.
	if (!CHECK) return;
	// Short viewport: the form is only ~700px tall, and the full height leaves
	// half the image empty, which reads as dead space on the listing page.
	const viewport = page.viewportSize();
	await page.setViewportSize({ width: viewport.width, height: 820 });
	await page.screenshot({ path: `${ASSETS}screenshot-1.png`, fullPage: false });
	await page.setViewportSize(viewport);
});

scene(3, 'Product page — contentView on load, addToCart on click', 2, async (page) => {
	await page.goto(`${BASE}/product/beanie/`, { waitUntil: 'networkidle' });
	await page.waitForTimeout(2500);
	const add = page.locator('button[name="add-to-cart"], .single_add_to_cart_button').first();
	await add.click();
	await page.waitForTimeout(2500);
});

scene(4, 'Checkout — initiateCheckout + setEncryptedEmail', 3, async (page) => {
	await page.goto(`${BASE}/checkout/`, { waitUntil: 'networkidle' });
	await page.waitForTimeout(3000);
});

scene(5, 'Order received — purchase with contents and revenue', 4, async (page) => {
	await page.goto(`${BASE}/checkout/`, { waitUntil: 'networkidle' });
	const fields = {
		'#billing_first_name': 'Anna',
		'#billing_last_name': 'Kovács',
		'#billing_address_1': 'Váci út 1',
		'#billing_city': 'Budapest',
		'#billing_postcode': '1132',
		'#billing_phone': '+36 1 234 5678',
		'#billing_email': 'anna.kovacs+demo@example.com',
	};
	for (const [sel, value] of Object.entries(fields)) {
		const el = page.locator(sel);
		if (await el.count()) await el.fill(value);
	}
	await page.waitForTimeout(1500); // let setEncryptedEmail fire on the email change
	const cod = page.locator('#payment_method_cod');
	if (await cod.count()) await cod.check();
	await page.locator('#place_order').click();
	await page.waitForURL(/order-received/, { timeout: 60000 });
	await page.waitForTimeout(3000);
});

// ---------------------------------------------------------------------- main

const browser = await chromium.launch({
	headless: CHECK,
	args: CHECK ? [] : ['--auto-open-devtools-for-tabs', '--window-size=1400,1100'],
});
// Headed runs must use the real window viewport (`viewport: null`). With a fixed
// Playwright viewport, docked DevTools draws over the bottom of the emulated
// page instead of shrinking it, which hides anything positioned at the bottom —
// the consent banner in scene 1.
const context = await browser.newContext(
	CHECK ? { viewport: { width: 1400, height: 1000 }, deviceScaleFactor: 2 } : { viewport: null }
);
const page = await context.newPage();
page.on('console', (m) => log.push(`${m.type()}: ${m.text()}`));
page.on('pageerror', (e) => log.push(`pageerror: ${e.message}`));

await mkdir(OUT, { recursive: true });

// Playground auto-logs-in on the first request; this just burns that redirect
// so the scenes start from a settled, authenticated session.
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });

const transcript = {};
for (const s of scenes) {
	log = [];
	await s.fn(page);
	transcript[`scene-${s.n}`] = { what: s.what, console: interesting() };
	if (CHECK) await page.screenshot({ path: `${OUT}scene-${s.n}.png` });
	await pause(s.n, scenes.length, s.what, s.capture);
	// Consent must be granted after scene 1, or no events fire at all.
	if (s.n === 1) {
		await page.click('#demo-consent-accept');
		await page.waitForTimeout(500);
	}
}

if (CHECK) {
	await writeFile(`${OUT}console.json`, JSON.stringify(transcript, null, 2));
	console.log(`\nWrote ${OUT}console.json`);
}

rl?.close();
await browser.close();
