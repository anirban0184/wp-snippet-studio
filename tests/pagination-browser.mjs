import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire( process.env.WPSSTUDIO_PLAYWRIGHT_PACKAGE || import.meta.url );
const { chromium } = require( 'playwright' );
const fixture = JSON.parse( fs.readFileSync( process.env.WPSSTUDIO_QA_FIXTURE, 'utf8' ) );
assert.match( fixture.baseURL, /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/ );
assert.equal( fixture.pagination_ids?.length, 53, 'Pagination fixtures are required' );
const launch = { headless: true };
if ( process.env.WPSSTUDIO_CHROMIUM ) {
	launch.executablePath = process.env.WPSSTUDIO_CHROMIUM;
	launch.args = JSON.parse( process.env.WPSSTUDIO_CHROMIUM_ARGS || '[]' );
}
const browser = await chromium.launch( launch );
const page = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
const errors = [];
page.on( 'pageerror', error => errors.push( error.message ) );
const studio = fixture.baseURL + '/wp-admin/admin.php?page=wpsstudio';
let checks = 0;
const check = ( value, message ) => { assert.ok( value, message ); checks++; };

async function collect( type, expected ) {
	const found = [];
	let next = studio + ( type ? '&type=' + type : '' );
	let pages = 0;
	while ( next ) {
		assert.ok( ++pages <= Math.ceil( expected.length / 25 ), 'Pagination links cannot cycle' );
		await page.goto( next );
		const links = page.locator( '.wpsstudio-name > a' );
		const count = await links.count();
		check( count === Math.min( 25, expected.length - found.length ), 'Correct row count on page ' + pages + ' of ' + ( type || 'all' ) );
		for ( const href of await links.evaluateAll( elements => elements.map( element => element.href ) ) ) {
			found.push( Number( new URL( href ).searchParams.get( 'id' ) ) );
		}
		if ( type ) {
			check( ( await page.locator( '.wpsstudio-type' ).allTextContents() ).every( value => value === type.toUpperCase() ), 'Type filter preserved across page ' + pages );
		}
		check( await page.locator( '.wpsstudio-pagination' ).getByRole( 'link', { name: 'Previous', exact: true } ).count() === ( pages > 1 ? 1 : 0 ), 'Previous navigation follows current page' );
		const nextLink = page.locator( '.wpsstudio-pagination' ).getByRole( 'link', { name: 'Next', exact: true } );
		next = await nextLink.count() ? await nextLink.getAttribute( 'href' ) : '';
	}
	check( JSON.stringify( found ) === JSON.stringify( expected.map( Number ) ), 'Every expected ID appears in stable order without duplication for ' + ( type || 'all' ) );
}

try {
	await page.goto( fixture.baseURL + '/wp-login.php' );
	await page.locator( '#user_login' ).fill( fixture.admin.username );
	await page.locator( '#user_pass' ).fill( fixture.admin.password );
	await Promise.all( [ page.waitForURL( /wp-admin/ ), page.locator( '#wp-submit' ).click() ] );
	await collect( '', fixture.snippets );
	await collect( 'text', [ fixture.snippets[6], ...fixture.pagination_ids ] );
	await page.goto( studio + '&paged=99999999' );
	check( await page.locator( '.wpsstudio-name > a' ).count() === 11, 'Out-of-range page clamps to the last page' );
	for ( const suffix of [ '&paged[]=2', '&paged=-1', '&paged=1e2', '&type[]=php' ] ) {
		await page.goto( studio + suffix );
		check( await page.locator( '.wpsstudio-name > a' ).count() === 25, 'Malformed read-only filter safely defaults: ' + suffix );
	}
	check( errors.length === 0, 'No browser errors during pagination: ' + errors.join( ', ' ) );
	console.log( checks + ' pagination browser checks passed across 61 snippets.' );
} finally {
	await browser.close();
}
