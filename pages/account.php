<?php
/**
 *
 * @author Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 14.02.2025, CreativeMotion
 */

// Dynamic asset discovery  no hardcoded hashes needed after each build
$assets_dir = dirname( __DIR__ ) . '/assets/';
$assets_url = PAYWAY_PLUGIN_URL . '/assets/';

$js_files  = glob( $assets_dir . 'index-*.js' );
$css_files = glob( $assets_dir . 'index-*.css' );

$js_url   = ( $js_files  && count( $js_files ) )  ? $assets_url . basename( $js_files[0] )  : '';
$css_urls = ( $css_files && count( $css_files ) )  ? array_map( function( $f ) use ( $assets_url ) { return $assets_url . basename( $f ); }, $css_files ) : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <link rel="icon" type="image/svg+xml" href="https://payway.store/wp-content/uploads/2024/08/pwlogo-blue.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Payway  </title>
    <?php if ( $js_url ) : ?>
    <script type="module" charset="utf-8" crossorigin src="<?php echo esc_url( $js_url ); ?>"></script>
    <?php endif; ?>
    <?php foreach ( $css_urls as $css_url ) : ?>
    <link rel="stylesheet" crossorigin href="<?php echo esc_url( $css_url ); ?>">
    <?php endforeach; ?>
</head>
<body>
<div id="root"></div>
</body>
</html>
