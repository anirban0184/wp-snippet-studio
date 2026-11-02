import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

// Use npm-installed Playwright by default; an external installation can be used in constrained workspaces.
const require = createRequire( process.env.WPSSTUDIO_PLAYWRIGHT_PACKAGE || import.meta.url );
const { chromium } = require( 'playwright' );
const fixture = JSON.parse( fs.readFileSync( process.env.WPSSTUDIO_QA_FIXTURE, 'utf8' ) );
assert.match( fixture.baseURL, /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/ );
const base = fixture.baseURL;
const launch = { headless: true };
if ( process.env.WPSSTUDIO_CHROMIUM ) {
	launch.executablePath = process.env.WPSSTUDIO_CHROMIUM;
	launch.args = JSON.parse( process.env.WPSSTUDIO_CHROMIUM_ARGS || '[]' );
}
const browser = await chromium.launch( launch );
const context = await browser.newContext();
const page = await context.newPage();
const errors = [];
page.on( 'pageerror', error => errors.push( page.url() + ': ' + error.stack ) );
page.on( 'dialog', dialog => dialog.accept() );
let checks = 0;
const check = ( condition, label ) => { assert.ok( condition, label ); checks++; };
const studio = base + '/wp-admin/admin.php?page=wpsstudio';
const artifacts = process.env.WPSSTUDIO_QA_ARTIFACTS || 'build/qa';
fs.mkdirSync( artifacts, { recursive: true } );

async function login( target, credentials ) {
	await target.goto( base + '/wp-login.php' );
	await target.locator( '#user_login' ).fill( credentials.username );
	await target.locator( '#user_pass' ).fill( credentials.password );
	await Promise.all( [ target.waitForURL( /wp-admin/ ), target.locator( '#wp-submit' ).click() ] );
}
function row( title ) {
	return page.locator( '.wpsstudio-table tbody tr' ).filter( { has: page.getByRole( 'link', { name: title, exact: true } ) } );
}
async function create( title, type, code, scope = 'everywhere' ) {
	await page.goto( base + '/wp-admin/admin.php?page=wpsstudio-new' );
	await page.locator( '#wpsstudio-title' ).fill( title );
	await page.locator( '#wpsstudio-type' ).selectOption( type );
	if ( type === 'php' ) {
		await page.locator( '#wpsstudio-scope' ).selectOption( scope );
	}
	await page.locator( '#wpsstudio-description' ).fill( 'Browser workflow check' );
	await page.evaluate( value => {
		const cm = document.querySelector( '.CodeMirror' );
		if ( cm ) { cm.CodeMirror.setValue( value ); } else { document.querySelector( '#wpsstudio-code' ).value = value; }
	}, code );
	await Promise.all( [ page.waitForURL( /notice=saved/ ), page.getByRole( 'button', { name: 'Save inactive', exact: true } ).click() ] );
	const id = Number( new URL( page.url() ).searchParams.get( 'id' ) );
	fixture.snippets.push( id );
	fs.writeFileSync( process.env.WPSSTUDIO_QA_FIXTURE, JSON.stringify( fixture ), { mode: 0o600 } );
	return id;
}

try {
	await login( page, fixture.admin );
	let response = await page.goto( studio );
	check( ! response.headers()['x-wpsstudio-everywhere'] && ! response.headers()['x-wpsstudio-admin'], 'Management page bypasses automatic PHP before execution' );
	check( await page.locator( '.wpsstudio-tabs a' ).count() === 6, 'All six type navigation links are present' );
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 8, 'Fixture rows rendered' );
	for ( const width of [ 1440, 1024, 782, 736, 480, 360, 320 ] ) {
		await page.setViewportSize( { width, height: 1000 } );
		const geometry = await page.evaluate( () => {
			const cells = Array.from( document.querySelector( '.wpsstudio-table tbody tr' ).cells ).map( cell => cell.getBoundingClientRect() );
			return { ys: cells.map( cell => cell.y ), document: document.documentElement.scrollWidth, viewport: innerWidth, scroller: document.querySelector( '.wpsstudio-table-scroll' ).getBoundingClientRect().width };
		} );
		check( Math.max( ...geometry.ys ) - Math.min( ...geometry.ys ) < 1, 'All six columns share one line at width ' + width );
		check( geometry.document <= geometry.viewport, 'Horizontal scrolling is confined to table at width ' + width );
		if ( width === 1440 || width === 360 ) {
			await page.screenshot( { path: path.join( artifacts, 'dashboard-' + width + '.png' ), fullPage: true } );
		}
	}
	await page.setViewportSize( { width: 1440, height: 1000 } );
	await Promise.all( [ page.waitForURL( /type=css/ ), page.getByRole( 'link', { name: /CSS Styles/ } ).click() ] );
	await page.waitForLoadState( 'domcontentloaded' );
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 1, 'Type navigation filters rows' );
	await page.goto( studio );
	let target = row( 'Frontend color' );
	await target.locator( '[name=priority]' ).fill( '-12' );
	await Promise.all( [ page.waitForURL( /notice=priority/ ), target.getByRole( 'button', { name: 'Save priority' } ).click() ] );
	check( await row( 'Frontend color' ).locator( '[name=priority]' ).inputValue() === '-12', 'Inline priority persists' );
	await page.goto( studio );
	target = row( 'Frontend script' );
	await Promise.all( [ page.waitForURL( /notice=deactivate/ ), target.getByRole( 'switch' ).click() ] );
	check( await row( 'Frontend script' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'false', 'Status switch deactivates' );
	await page.goto( studio );
	await Promise.all( [ page.waitForURL( /notice=activate/ ), row( 'Frontend script' ).getByRole( 'switch' ).click() ] );
	check( await row( 'Frontend script' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'true', 'Status switch reactivates' );

	// Authentication and CSRF are tested over HTTP against the actual admin route.
	const forged = { wpsstudio_action: 'delete', id: fixture.snippets[4], revision: 4 };
	response = await context.request.post( studio, { form: forged } );
	check( response.status() === 403, 'Logged-in write without a nonce rejected' );
	await page.goto( studio );
	check( await row( 'Frontend script' ).count() === 1, 'Rejected write leaves snippet intact' );
	const anonymous = await browser.newContext();
	response = await anonymous.request.get( studio );
	check( response.url().includes( 'wp-login.php' ) && !( await response.text() ).includes( 'Frontend script' ), 'Anonymous source/dashboard access redirected to login' );
	response = await anonymous.request.post( studio, { form: forged } );
	check( response.url().includes( 'wp-login.php' ), 'Anonymous write cannot reach mutation handler' );
	const editorContext = await browser.newContext();
	const editorPage = await editorContext.newPage();
	await login( editorPage, fixture.editor );
	response = await editorPage.goto( studio );
	check( response.status() === 403 && !( await response.text() ).includes( 'Frontend script' ), 'Editor cannot access privileged dashboard or source' );
	response = await editorContext.request.post( studio, { form: forged } );
	check( response.status() === 403, 'Editor cannot mutate snippets' );
	await editorContext.close();
	await anonymous.close();

	response = await context.request.get( base + '/' );
	check( response.headers()['x-wpsstudio-everywhere'] === 'yes' && response.headers()['x-wpsstudio-frontend'] === 'yes' && !response.headers()['x-wpsstudio-admin'], 'Frontend PHP scope' );
	response = await context.request.get( base + '/wp-admin/index.php' );
	check( response.headers()['x-wpsstudio-everywhere'] === 'yes' && response.headers()['x-wpsstudio-admin'] === 'yes' && !response.headers()['x-wpsstudio-frontend'], 'Admin PHP scope' );
	response = await context.request.get( base + '/?rest_route=/' );
	check( response.headers()['x-wpsstudio-everywhere'] === 'yes' && !response.headers()['x-wpsstudio-frontend'] && !response.headers()['x-wpsstudio-admin'], 'Everywhere includes REST; frontend/admin scopes do not' );
	await page.goto( base + '/?page_id=' + fixture.post );
	check( await page.locator( '#wpsstudio-e2e-html strong' ).textContent() === 'HTML', 'Active HTML shortcode renders' );
	check( await page.locator( '#wpsstudio-e2e-unsafe' ).count() === 0 && ( await page.locator( 'body' ).textContent() ).includes( '<b id="wpsstudio-e2e-unsafe">Plain text</b>' ), 'Text shortcode cannot inject markup' );
	check( await page.evaluate( () => window.wpsstudioE2e ) === 73, 'Active JS actually executes in footer' );
	check( ( await page.evaluate( () => getComputedStyle( document.documentElement ).getPropertyValue( '--wpsstudio-e2e' ) ) ).trim() === '73', 'Active CSS actually reaches frontend' );
	await page.goto( studio );
	await Promise.all( [ page.waitForURL( /notice=safe_on/ ), page.getByRole( 'button', { name: 'Enable Safe Mode' } ).click() ] );
	await page.goto( base + '/?page_id=' + fixture.post );
	check( await page.locator( '#wpsstudio-e2e-html' ).count() === 0 && await page.evaluate( () => window.wpsstudioE2e ) === undefined, 'Safe Mode suppresses content and JavaScript on a new request' );
	response = await context.request.get( base + '/' );
	check( !response.headers()['x-wpsstudio-everywhere'] && !response.headers()['x-wpsstudio-frontend'], 'Safe Mode suppresses PHP on a new request' );
	await page.goto( studio );
	await Promise.all( [ page.waitForURL( /notice=safe_off/ ), page.getByRole( 'button', { name: 'Turn Safe Mode off' } ).click() ] );

	const id = await create( 'New reusable content', 'text', '</textarea><script>window.wpsstudioPwned = true;</script>' );
	check( Number.isInteger( id ) && id > 0, 'Browser editor creates a snippet' );
	check( await page.evaluate( () => window.wpsstudioPwned ) === undefined, 'Editing hostile text does not execute it' );
	check( await page.locator( '#wpsstudio-shortcode' ).inputValue() === '[wpsstudio id="' + id + '"]', 'Content editor provides correct shortcode' );
	await page.screenshot( { path: path.join( artifacts, 'editor-desktop.png' ), fullPage: true } );
	await page.goto( studio );
	check( await row( 'New reusable content' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'false', 'Browser saves are inactive' );
	await Promise.all( [ page.waitForURL( /notice=delete/ ), row( 'New reusable content' ).getByRole( 'button', { name: 'Delete', exact: true } ).click() ] );
	check( await row( 'New reusable content' ).count() === 0, 'Browser delete persists' );

	await page.goto( studio );
	await Promise.all( [ page.waitForURL( /notice=run/ ), row( 'Manual maintenance' ).getByRole( 'button', { name: 'Run once', exact: true } ).click() ] );
	await Promise.all( [ page.waitForNavigation(), row( 'Manual maintenance' ).getByRole( 'button', { name: 'Run once', exact: true } ).click() ] );
	check( ( await page.locator( '.notice-error' ).textContent() ).includes( 'already been attempted' ), 'Browser run-once replay is blocked' );
	// A real fatal and a deferred callback fatal run only on this disposable site.
	for ( const [ title, code, marker ] of [
		[ 'Immediate fatal recovery', 'echo "WPSSTUDIO_PRIVATE_FATAL"; trigger_error("WPSSTUDIO_PRIVATE_FATAL", E_USER_ERROR);', 'WPSSTUDIO_PRIVATE_FATAL' ],
		[ 'Callback fatal recovery', 'add_action("init", static function () { throw new \\Error("WPSSTUDIO_PRIVATE_CALLBACK"); });', 'WPSSTUDIO_PRIVATE_CALLBACK' ]
	] ) {
		await create( title, 'php', code );
		await page.goto( studio );
		const activation = await row( title ).evaluate( tr => {
			const form = tr.querySelector( '[name=wpsstudio_action][value=activate]' ).form;
			return Object.fromEntries( new FormData( form ) );
		} );
		// Keep core background AJAX/REST requests from racing the deliberately fatal request.
		await page.goto( 'about:blank' );
		response = await context.request.post( studio, { form: activation } );
		check( response.status() === 200, 'Activation response stays on the protected management route' );
		response = await context.request.get( base + '/wp-login.php' );
		check( response.status() === 200 && !( await response.text() ).includes( marker ), 'Login recovery route bypasses automatic PHP' );
		response = await context.request.get( base + '/' );
		check( response.status() === 500 && !( await response.text() ).includes( marker ), title + ': fatal request fails without exposing private error text or partial direct output' );
		await page.goto( studio );
		check( await page.getByRole( 'button', { name: 'Turn Safe Mode off' } ).count() === 1, 'Fatal enables Safe Mode for the next request' );
		await Promise.all( [ page.waitForURL( /notice=delete/ ), row( title ).getByRole( 'button', { name: 'Delete', exact: true } ).click() ] );
		await Promise.all( [ page.waitForURL( /notice=safe_off/ ), page.getByRole( 'button', { name: 'Turn Safe Mode off' } ).click() ] );
	}
	check( errors.length === 0, 'No browser JavaScript errors: ' + errors.join( ', ' ) );
	console.log( checks + ' browser checks passed.' );
} finally {
	await browser.close();
}
