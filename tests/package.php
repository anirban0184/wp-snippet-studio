<?php
/** Inspect the exact submission ZIP without booting WordPress. */

$root = dirname( __DIR__ );
$file = $root . '/build/coder-snippet-studio-1.0.0.zip';
$zip = new ZipArchive();
if ( true !== $zip->open( $file ) || 15 !== $zip->numFiles ) { throw new RuntimeException( 'Unexpected installation manifest.' ); }
for ( $i = 0; $i < $zip->numFiles; ++$i ) {
	$name = $zip->getNameIndex( $i );
	if ( ! str_starts_with( $name, 'coder-snippet-studio/' ) || str_contains( $name, '..' ) ) { throw new RuntimeException( 'Unsafe archive path.' ); }
	$relative = substr( $name, strlen( 'coder-snippet-studio/' ) );
	$content = $zip->getFromIndex( $i );
	if ( ! in_array( $relative, array( 'coder-snippet-studio.php', 'uninstall.php', 'readme.txt', 'LICENSE' ), true ) && ! preg_match( '~^(includes|admin|assets)/[a-z0-9/.-]+\.(php|css|js)$~D', $relative ) ) { throw new RuntimeException( 'Development/unexpected file in archive.' ); }
	if ( ! is_file( $root . '/' . $relative ) || $content !== file_get_contents( $root . '/' . $relative ) ) { throw new RuntimeException( 'ZIP/source mismatch.' ); }
	if ( str_ends_with( $name, '.php' ) ) {
		foreach ( token_get_all( $content ) as $token ) {
			if ( is_array( $token ) && ( T_EVAL === $token[0] || ( T_STRING === $token[0] && in_array( strtolower( $token[1] ), array( 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'create_function' ), true ) ) ) ) { throw new RuntimeException( 'Executable-code gateway in runtime.' ); }
		}
	}
}
if ( false !== $zip->locateName( 'coder-snippet-studio/includes/class-runner.php' ) ) { throw new RuntimeException( 'Legacy runner was packaged.' ); }
$main = $zip->getFromName( 'coder-snippet-studio/coder-snippet-studio.php' );
if ( ! str_contains( $main, 'Plugin Name: Coder Snippet Studio' ) || ! str_contains( $main, 'Text Domain: coder-snippet-studio' ) || str_contains( $main, 'Update URI:' ) ) { throw new RuntimeException( 'Directory identity/update metadata mismatch.' ); }
$zip->close();
echo 'Submission ZIP: 15 files, exact source agreement, safe paths, no executable gateway, directory metadata preserved.' . PHP_EOL;
