import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire( process.env.WPSSTUDIO_PLAYWRIGHT_PACKAGE || import.meta.url );
const { chromium } = require( 'playwright' );
const fixturePath = process.env.WPSSTUDIO_QA_FIXTURE;
const fixture = JSON.parse( fs.readFileSync( fixturePath, 'utf8' ) );
assert.match( fixture.baseURL, /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/ );
const base = fixture.baseURL;
const launch = { headless: true };
if ( process.env.WPSSTUDIO_CHROMIUM ) { launch.executablePath = process.env.WPSSTUDIO_CHROMIUM; launch.args = JSON.parse( process.env.WPSSTUDIO_CHROMIUM_ARGS || '[]' ); }
const browser = await chromium.launch( launch );
const context = await browser.newContext();
const page = await context.newPage();
const errors = [];
page.on( 'pageerror', error => errors.push( error.message ) );
page.on( 'dialog', dialog => dialog.accept() );
let checks = 0;
const check = ( condition, label ) => { assert.ok( condition, label ); ++checks; };
const studio = base + '/wp-admin/admin.php?page=wpsstudio';
const artifacts = process.env.WPSSTUDIO_QA_ARTIFACTS || 'build/content';
fs.mkdirSync( artifacts, { recursive: true } );
const row = title => page.locator( '.wpsstudio-table tbody tr' ).filter( { has: page.getByRole( 'link', { name: title, exact: true } ) } );
async function login( target, credentials ) {
	await target.goto( base + '/wp-login.php' );
	await target.locator( '#user_login' ).fill( credentials.username );
	await target.locator( '#user_pass' ).fill( credentials.password );
	await Promise.all( [ target.waitForURL( /wp-admin/ ), target.locator( '#wp-submit' ).click() ] );
}
async function submit( button, pattern ) {
	await Promise.all( [ page.waitForURL( pattern ), button.click() ] );
	await page.waitForLoadState( 'load' );
}
async function create( type, content, title ) {
	await page.goto( base + '/wp-admin/admin.php?page=wpsstudio-new' );
	check( JSON.stringify( await page.locator( '#wpsstudio-type option' ).evaluateAll( els => els.map( e => e.value ) ) ) === '["html","text"]', 'Only HTML/text editor options' );
	check( ! await page.locator( '#wpsstudio-scope, [name=priority]' ).count(), 'No executable run locations or priority controls' );
	await page.locator( '#wpsstudio-title' ).fill( title );
	await page.locator( '#wpsstudio-type' ).selectOption( type );
	await page.locator( '#wpsstudio-description' ).fill( 'Browser source round trip' );
	await page.evaluate( value => { const cm = document.querySelector( '.CodeMirror' ); if ( cm ) { cm.CodeMirror.setValue( value ); } else { document.querySelector( '#wpsstudio-code' ).value = value; } }, content );
	await submit( page.getByRole( 'button', { name: 'Save inactive', exact: true } ), /notice=saved/ );
	const id = Number( new URL( page.url() ).searchParams.get( 'id' ) );
	fixture.snippets.push( id ); fs.writeFileSync( fixturePath, JSON.stringify( fixture ), { mode: 0o600 } );
	return id;
}
try {
	await login( page, fixture.admin );
	await page.goto( studio );
	check( await page.locator( '.wpsstudio-tabs a' ).count() === 3, 'All/HTML/Text navigation' );
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 25, 'First page contains 25 records' );
	for ( const width of [ 1440, 1024, 782, 736, 480, 360, 320 ] ) {
		await page.setViewportSize( { width, height: 1000 } );
		const geometry = await page.evaluate( () => { const cells = Array.from( document.querySelector( '.wpsstudio-table tbody tr' ).cells ).map( e => e.getBoundingClientRect() ); return { y: cells.map( e => e.y ), document: document.documentElement.scrollWidth, viewport: innerWidth }; } );
		check( geometry.y.length === 5 && Math.max( ...geometry.y ) - Math.min( ...geometry.y ) < 1, 'Five columns on one row: ' + width );
		if ( geometry.document > geometry.viewport ) {
			console.log( 'Responsive overflow diagnostics', await page.evaluate( () => Array.from( document.querySelectorAll( 'body *' ) ).filter( el => ! el.closest( '.wpsstudio-table-scroll' ) && el.getBoundingClientRect().right > innerWidth ).map( el => ( { tag: el.tagName, id: el.id, className: el.className, right: el.getBoundingClientRect().right } ) ).slice( 0, 20 ) ) );
		}
		check( geometry.document <= geometry.viewport, 'Table-only horizontal scrolling: ' + width );
		if ( width === 1440 || width === 360 ) { await page.screenshot( { path: path.join( artifacts, 'content-' + width + '.png' ), fullPage: true } ); }
	}
	await page.setViewportSize( { width: 1440, height: 1000 } );
	await page.getByRole( 'link', { name: 'Next', exact: true } ).click();
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 4, 'Second page contains remaining records' );
	await page.goto( studio + '&type=html' );
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 1, 'HTML filter' );
	await page.goto( studio + '&type[]=html&paged[]=2' );
	check( await page.locator( '.wpsstudio-table tbody tr' ).count() === 25, 'Malformed read-only filters use safe defaults' );
	const source = '<strong>বাংলা & "quotes"</strong>\n[wpsstudio id="999999"]';
	const id = await create( 'text', source, 'Text workflow' );
	check( await page.locator( '#wpsstudio-code' ).inputValue() === source, 'Unicode/text source round trip' );
	await page.goto( studio );
	check( await row( 'Text workflow' ).count() === 0, 'New record uses stable next page order' );
	await page.goto( studio + '&paged=2' );
	check( await row( 'Text workflow' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'false', 'New content saved inactive' );
	await submit( row( 'Text workflow' ).getByRole( 'switch' ), /notice=activate/ );
	await page.goto( studio + '&paged=2' );
	check( await row( 'Text workflow' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'true', 'Individual activation' );
	await row( 'Text workflow' ).getByRole( 'link', { name: 'Edit', exact: true } ).click();
	await page.locator( '#wpsstudio-description' ).fill( 'Metadata edit must deactivate' );
	await submit( page.getByRole( 'button', { name: 'Save inactive', exact: true } ), /notice=saved/ );
	await page.goto( studio + '&paged=2' );
	check( await row( 'Text workflow' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'false', 'Active edit becomes inactive' );
	await row( 'Text workflow' ).getByRole( 'button', { name: 'Copy shortcode for Text workflow', exact: true } ).click();
	check( await row( 'Text workflow' ).locator( 'input[readonly]' ).inputValue() === `[wpsstudio id="${ id }"]`, 'Reusable shortcode supplied for copy' );
	await submit( row( 'Text workflow' ).getByRole( 'button', { name: 'Delete', exact: true } ), /notice=delete/ );
	check( !( await ( await context.request.get( studio + '&paged=2' ) ).text() ).includes( 'Text workflow' ), 'Delete persists' );
	const htmlId = await create( 'html', '<p onclick="alert(1)">Safe <strong>HTML</strong></p><script>bad()</script>', 'HTML workflow' );
	const filtered = await page.locator( '#wpsstudio-code' ).inputValue();
	check( ! filtered.includes( 'onclick=' ) && ! filtered.includes( '<script' ) && filtered.includes( '<strong>HTML</strong>' ), 'HTML filtered on real form save' );
	await page.goto( studio );
	const tokenForm = await row( 'Reusable HTML' ).locator( 'form' ).first().evaluate( form => Object.fromEntries( new FormData( form ) ) );
	let response = await context.request.post( studio, { form: { wpsstudio_action: 'delete', id: fixture.snippets[0], revision: 2 } } );
	check( response.status() === 403, 'Missing nonce denied' );
	for ( const action of [ 'run', 'priority' ] ) {
		response = await context.request.post( studio, { form: { ...tokenForm, wpsstudio_action: action } } );
		check( response.status() === 403, 'Removed action denied: ' + action );
	}
	await page.goto( base + '/wp-admin/admin.php?page=wpsstudio-new' );
	const nonce = await page.locator( '.wpsstudio-editor-form input[name="_wpnonce"]' ).inputValue();
	response = await context.request.post( base + '/wp-admin/admin.php?page=wpsstudio-new', { form: { wpsstudio_action: 'save', id: '0', revision: '0', _wpnonce: nonce, type: 'php', title: 'Rejected PHP', description: '', code: 'update_option("wpsstudio_forbidden", 1);' } } );
	check( ( await response.text() ).includes( 'Choose HTML or plain text.' ), 'Forged PHP save rejected with a valid creation nonce' );
	const anonymous = await browser.newContext();
	response = await anonymous.request.get( studio );
	check( response.url().includes( 'wp-login.php' ) && !( await response.text() ).includes( 'Reusable HTML' ), 'Anonymous admin access denied' );
	const publicPage = await anonymous.newPage();
	await publicPage.goto( base + '/?page_id=' + fixture.post );
	check( await publicPage.locator( '#wpsstudio-html strong' ).innerText() === 'HTML', 'Active HTML renders publicly' );
	check( ! await publicPage.locator( '#wpsstudio-html' ).getAttribute( 'onclick' ), 'No output event handler' );
	check( await publicPage.locator( '#wpsstudio-unsafe' ).count() === 0 && ( await publicPage.locator( 'body' ).innerText() ).includes( '<b id="wpsstudio-unsafe">Plain & literal</b>' ), 'Plain text cannot create HTML elements' );
	check( ( await publicPage.locator( 'body' ).innerText() ).includes( '[wpsstudio id="999999"]' ), 'Nested shortcode remains literal' );
	const editorContext = await browser.newContext(); const editorPage = await editorContext.newPage();
	await login( editorPage, fixture.editor );
	response = await editorContext.request.get( studio );
	check( response.status() === 403 && !( await response.text() ).includes( 'Reusable HTML' ), 'Editor cannot read management content' );
	response = await editorContext.request.post( studio, { form: tokenForm } );
	check( response.status() === 403, 'Editor cannot mutate with a stolen administrator nonce' );
	await page.goto( studio );
	await submit( page.getByRole( 'button', { name: 'Pause all output', exact: true } ), /notice=pause/ );
	await publicPage.reload();
	check( await publicPage.locator( '#wpsstudio-html' ).count() === 0 && !( await publicPage.locator( 'body' ).innerText() ).includes( 'Plain & literal' ), 'Pause suppresses both public types' );
	check( await row( 'Reusable HTML' ).getByRole( 'switch' ).getAttribute( 'aria-checked' ) === 'true', 'Pause preserves active state' );
	await submit( page.getByRole( 'button', { name: 'Resume output', exact: true } ), /notice=resume/ );
	await publicPage.reload();
	check( await publicPage.locator( '#wpsstudio-html' ).count() === 1, 'Resume restores content' );
	await page.goto( studio + '&id=' + htmlId );
	await page.screenshot( { path: path.join( artifacts, 'content-editor.png' ), fullPage: true } );
	check( errors.length === 0, 'No browser JavaScript errors: ' + errors.join( '; ' ) );
	console.log( checks + ' content browser/HTTP/responsive checks passed.' );
} finally {
	await browser.close();
}
