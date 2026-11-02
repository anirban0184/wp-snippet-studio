<?php
/** Deterministic, allow-listed installation archive. Run: php scripts/build.php */

$root = dirname( __DIR__ );
if ( ! class_exists( ZipArchive::class ) ) {
	fwrite( STDERR, "The development build requires PHP's zip extension.\n" );
	exit( 1 );
}
preg_match( '/^ \* Version: ([0-9.]+)$/m', file_get_contents( $root . '/wp-snippet-studio.php' ), $match );
$version = $match[1] ?? '';
if ( ! preg_match( '/^\d+\.\d+\.\d+$/D', $version ) ) {
	throw new RuntimeException( 'Invalid plugin version.' );
}
$files = array( 'wp-snippet-studio.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'CHANGELOG.md', 'SECURITY.md', 'docs/security-and-recovery.md', 'docs/development.md', 'docs/validation.md' );
foreach ( array( 'includes', 'admin', 'assets' ) as $directory ) {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $directory, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		if ( $file->isFile() && ! $file->isLink() && in_array( $file->getExtension(), array( 'php', 'css', 'js' ), true ) ) {
			$files[] = substr( $file->getPathname(), strlen( $root ) + 1 );
		}
	}
}
sort( $files, SORT_STRING );
if ( ! is_dir( $root . '/build' ) ) {
	mkdir( $root . '/build', 0755, true );
}
$output = $root . '/build/wp-snippet-studio-' . $version . '.zip';
$zip = new ZipArchive();
if ( true !== $zip->open( $output, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	throw new RuntimeException( 'Could not create the build archive.' );
}
foreach ( $files as $file ) {
	$name = 'wp-snippet-studio/' . $file;
	if ( ! $zip->addFile( $root . '/' . $file, $name ) ) {
		throw new RuntimeException( 'Missing build source: ' . $file );
	}
	$zip->setMtimeName( $name, 1704067200 );
	$zip->setExternalAttributesName( $name, ZipArchive::OPSYS_UNIX, 0100644 << 16 );
}
$zip->close();
file_put_contents( $output . '.sha256', hash_file( 'sha256', $output ) . '  ' . basename( $output ) . "\n" );
echo $output . ' (' . count( $files ) . " files)\n";
