import assert from 'node:assert/strict';
import fs from 'node:fs';

const fixture = JSON.parse( fs.readFileSync( process.env.WPSSTUDIO_QA_FIXTURE, 'utf8' ) );
assert.match( fixture.baseURL, /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/ );
const mode = process.env.WPSSTUDIO_ROUTE_MODE;
assert.ok( [ 'plain', 'pretty', 'index' ].includes( mode ) );
const cases = [
	[ '/', 'frontend', [ 200 ] ],
	[ '/index.php?page_id=' + fixture.post, 'frontend', [ 200, 301, 302 ] ],
	[ '/?rest_route=/', 'rest', [ 200 ] ],
	[ '/index.php?rest_route=/', 'rest', [ 200 ] ],
	[ '/wp-login.php', 'recovery', [ 200 ] ],
	[ '/wp-admin/admin.php?page=wpsstudio', 'recovery', [ 302 ] ]
];
if ( mode !== 'plain' ) {
	cases.push( [ '/wp-json/', 'rest', [ 200 ] ], [ '/index.php/wp-json/', 'rest', [ 200 ] ] );
}
let checks = 0;
for ( const [ url, scope, statuses ] of cases ) {
	const response = await fetch( fixture.baseURL + url, { redirect: 'manual', signal: AbortSignal.timeout( 15000 ) } );
	assert.ok( statuses.includes( response.status ), mode + ' ' + url + ' returned ' + response.status );
	checks++;
	assert.equal( response.headers.get( 'x-wpsstudio-everywhere' ), scope === 'recovery' ? null : 'yes', 'Everywhere scope on ' + mode + ' ' + url );
	assert.equal( response.headers.get( 'x-wpsstudio-frontend' ), scope === 'frontend' ? 'yes' : null, 'Frontend scope on ' + mode + ' ' + url );
	assert.equal( response.headers.get( 'x-wpsstudio-admin' ), null, 'Admin scope excluded on ' + mode + ' ' + url );
	checks += 3;
	if ( scope === 'rest' ) {
		assert.ok( response.headers.get( 'content-type' )?.includes( 'application/json' ), 'REST route reached WordPress JSON handling' );
		const body = await response.json();
		assert.ok( Array.isArray( body.namespaces ), 'REST index is returned' );
		checks += 2;
	} else {
		await response.arrayBuffer();
	}
}
console.log( checks + ' route checks passed for ' + mode + ' permalinks.' );
