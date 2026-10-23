#!/usr/bin/env node
/**
 * Documentation screenshots.
 *
 * Builds a fresh Laravel 12 app with the kit and every module, seeds it with the data in
 * scripts/screenshots/ScreenshotSeeder.php, serves it, and photographs each screen in
 * light and dark mode at 1440 px wide, plus the admin shell on a phone. The theme is chosen
 * the way a user would: the stored `ui-kit.theme` choice, which puts the design system's
 * `light` or `dark` class on <html>.
 *
 *   npm install && npm run screenshots              # creates .screenshots-app/ on first run
 *   npm run screenshots -- --app=/path/to/app       # reuse an app that already has the kit installed
 *   UI_KIT_CHROME=/usr/bin/google-chrome npm run screenshots
 *
 * Writes docs/images/<screen>-{light,dark}.png. The images are COMMITTED, because
 * regenerating them needs PHP, Composer, Node and a browser; re-run this before a release
 * whenever a screen's appearance changes. shipbytes.dev copies docs/images/ at build time.
 *
 * Needs: php and composer on PATH, node 20+, and a Chromium (UI_KIT_CHROME, the Playwright
 * browser cache, or a system Chrome).
 */
import { execFileSync, spawn } from 'node:child_process'
import { appendFileSync, copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs'
import { homedir } from 'node:os'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const OUT = join(ROOT, 'docs/images')
const PORT = 8793
const BASE = `http://127.0.0.1:${PORT}`

const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')))
const APP = resolve(ROOT, args.app ?? '.screenshots-app')

function run(cmd, cmdArgs, opts = {}) {
  console.log(`  $ ${cmd} ${cmdArgs.join(' ')}`)
  return execFileSync(cmd, cmdArgs, { stdio: ['ignore', 'pipe', 'inherit'], encoding: 'utf8', maxBuffer: 64 << 20, ...opts })
}

// ---------------------------------------------------------------- 1. the app

if (!existsSync(join(APP, 'artisan'))) {
  console.log(`\ncreating a fresh Laravel 12 app at ${APP}…`)
  run('composer', ['create-project', 'laravel/laravel:^12.0', APP, '--no-interaction', '--prefer-dist', '--quiet'])
  run('composer', ['config', 'repositories.ui-kit', 'path', ROOT], { cwd: APP })
  run('composer', ['require', 'shipbytes/laravel-ui-kit:@dev', '--no-interaction', '--quiet'], { cwd: APP })
  run('php', ['artisan', 'ui-kit:install', '--modules=all', '--no-interaction'], { cwd: APP })
  run('npm', ['install', '--no-audit', '--no-fund', '--silent'], { cwd: APP })
  run('npm', ['run', 'build', '--silent'], { cwd: APP })
} else {
  console.log(`\nusing the app at ${APP}`)
}

// ---------------------------------------------------------------- 2. seed

console.log('\nseeding…')
mkdirSync(join(APP, 'database/seeders'), { recursive: true })
copyFileSync(join(ROOT, 'scripts/screenshots/ScreenshotSeeder.php'), join(APP, 'database/seeders/ScreenshotSeeder.php'))
if (!existsSync(join(APP, 'database/database.sqlite'))) writeFileSync(join(APP, 'database/database.sqlite'), '')
run('php', ['artisan', 'migrate:fresh', '--force', '--seed', '--seeder=ScreenshotSeeder', '--no-interaction'], { cwd: APP })

const routes = JSON.parse(run('php', ['artisan', 'route:list', '--json'], { cwd: APP }))
const route = (name) => {
  const r = routes.find((x) => x.name === name)
  if (!r) throw new Error(`route "${name}" is not registered in the app`)
  // drop optional segments such as /{guardName?}
  return '/' + r.uri.replace(/^\//, '').replace(/\/\{[^}]*\?\}/g, '')
}
const graceId = Number(run('php', ['artisan', 'tinker', '--execute=echo App\\Models\\User::where("email","grace@example.com")->value("id");'], { cwd: APP }).trim().split('\n').pop())
const linusId = Number(run('php', ['artisan', 'tinker', '--execute=echo App\\Models\\User::where("email","linus@example.com")->value("id");'], { cwd: APP }).trim().split('\n').pop())

// ---------------------------------------------------------------- 3. serve

console.log(`\nserving on ${BASE}…`)
const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
  cwd: APP,
  env: { ...process.env, APP_URL: BASE, APP_ENV: 'local', APP_DEBUG: 'true' },
  stdio: 'ignore',
})
process.on('exit', () => server.kill())
for (let i = 0; i < 50; i++) {
  try {
    await fetch(BASE + '/login')
    break
  } catch {
    await new Promise((r) => setTimeout(r, 200))
  }
}

// ---------------------------------------------------------------- 4. browser

function findChromium() {
  if (process.env.UI_KIT_CHROME) return existsSync(process.env.UI_KIT_CHROME) ? process.env.UI_KIT_CHROME : null
  const cache = join(homedir(), '.cache/ms-playwright')
  if (existsSync(cache)) {
    for (const dir of readdirSync(cache).filter((d) => d.startsWith('chromium-')).sort().reverse()) {
      for (const bin of ['chrome-linux64/chrome', 'chrome-linux/chrome', 'chrome-mac/Chromium.app/Contents/MacOS/Chromium']) {
        if (existsSync(join(cache, dir, bin))) return join(cache, dir, bin)
      }
    }
  }
  for (const p of ['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome']) {
    if (existsSync(p)) return p
  }
  return null
}

const CHROME = findChromium()
if (!CHROME) {
  console.error('\nno Chromium found. Install one (npx playwright install chromium) or set UI_KIT_CHROME.')
  process.exit(1)
}
const { chromium } = await import('playwright-core')
const browser = await chromium.launch({ executablePath: CHROME })
mkdirSync(OUT, { recursive: true })

const problems = []
const written = []

async function settle(page) {
  await page.waitForLoadState('networkidle')
  await page.evaluate(() => document.fonts.ready)
  await page.addStyleTag({ content: '*, *::before, *::after { animation: none !important; caret-color: transparent !important; }' })
  await page.waitForTimeout(250)
}

async function shot(page, name, theme) {
  const file = join(OUT, `${name}-${theme}.png`)
  await settle(page)
  const body = await page.textContent('body')
  if (/Whoops|Illuminate\\|ErrorException|Vite manifest/.test(body ?? '')) {
    problems.push(`${name} (${theme}): the page shows an error`)
  }
  // The head snippet and the theme store put exactly one of light / dark on <html>.
  if (!(await page.evaluate((t) => document.documentElement.classList.contains(t), theme))) {
    problems.push(`${name} (${theme}): <html> does not carry the "${theme}" class`)
  }
  await page.screenshot({ path: file })
  written.push(file)
  console.log(`  ${(name + '-' + theme).padEnd(32)} ${page.url().replace(BASE, '')}`)
}

async function open(page, path) {
  const res = await page.goto(BASE + path)
  if (res && res.status() >= 400) problems.push(`${path}: HTTP ${res.status()}`)
}

async function login(page, email) {
  await open(page, '/login')
  await page.fill('#email', email)
  await page.fill('#password', 'password')
  await page.click('button[type="submit"]')
  await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 15000 })
  await page.waitForLoadState('networkidle')
}

async function context(theme, viewport = { width: 1440, height: 900 }) {
  const ctx = await browser.newContext({ viewport, deviceScaleFactor: 1, colorScheme: theme, locale: 'en-GB', timezoneId: 'UTC' })
  await ctx.addInitScript((t) => localStorage.setItem('ui-kit.theme', t), theme)
  return ctx
}

for (const theme of ['light', 'dark']) {
  console.log(`\n${theme}`)

  // guests
  let ctx = await context(theme)
  let page = await ctx.newPage()
  for (const [name, path] of [['login', '/login'], ['register', '/register'], ['forgot-password', '/forgot-password']]) {
    await open(page, path)
    await shot(page, name, theme)
  }
  // a user with two-factor authentication lands on the challenge
  await login(page, 'grace@example.com')
  await page.waitForURL('**/two-factor-challenge', { timeout: 15000 })
  await shot(page, 'two-factor-challenge', theme)
  await ctx.close()

  // admin
  ctx = await context(theme)
  page = await ctx.newPage()
  await login(page, 'admin@example.com')
  for (const [name, path] of [
    ['admin-dashboard', '/admin'],
    ['admin-users', '/admin/users'],
    ['admin-user-detail', `/admin/users/${graceId}`],
    ['admin-support', '/admin/support'],
  ]) {
    await open(page, path)
    await shot(page, name, theme)
  }
  // Load the first ticket by its URL. Clicking its View link goes through
  // wire:navigate, whose progress bar is still fading when the shot is taken.
  await open(page, new URL(await page.getAttribute('a[href*="/admin/support/"]', 'href'), BASE).pathname)
  await shot(page, 'admin-support-ticket', theme)
  for (const [name, path] of [
    ['admin-changelog', '/admin/changelog'],
    ['admin-changelog-form', '/admin/changelog/create'],
    ['admin-contacts', '/admin/contacts'],
    ['admin-activity', '/admin/activity'],
    ['profile', '/profile'],
  ]) {
    await open(page, path)
    await shot(page, name, theme)
  }
  // impersonation: the admin becomes a user and sees the ribbon in the user shell
  await open(page, route('impersonate').replace('{id}', String(linusId)))
  await open(page, '/profile')
  await shot(page, 'impersonation', theme)
  await open(page, route('impersonate.leave'))
  await ctx.close()

  // a signed-in user's side of support
  ctx = await context(theme)
  page = await ctx.newPage()
  await login(page, 'linus@example.com')
  await open(page, '/support')
  await shot(page, 'support-list', theme)
  await open(page, '/support/create')
  await shot(page, 'support-create', theme)
  await open(page, '/support')
  await open(page, new URL(await page.getAttribute('a[href*="/support/"]:not([href$="/create"])', 'href'), BASE).pathname)
  await shot(page, 'support-ticket', theme)
  await ctx.close()

  // the public pages, as a guest sees them
  ctx = await context(theme)
  page = await ctx.newPage()
  for (const [name, path] of [['contact', '/contact'], ['changelog', '/changelog']]) {
    await open(page, path)
    await shot(page, name, theme)
  }
  await ctx.close()

  // the admin shell on a phone
  ctx = await context(theme, { width: 390, height: 844 })
  page = await ctx.newPage()
  await login(page, 'admin@example.com')
  await open(page, '/admin')
  await shot(page, 'admin-dashboard-mobile', theme)
  await ctx.close()
}

await browser.close()
server.kill()

for (const file of written) {
  if (statSync(file).size < 8 * 1024) problems.push(`${file} is suspiciously small`)
}

if (problems.length) {
  console.error('\nproblems:\n  ' + problems.join('\n  '))
  process.exit(1)
}

console.log(`\n→ docs/images/ (${written.length} images)\n`)
