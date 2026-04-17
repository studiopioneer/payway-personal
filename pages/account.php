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
<style>.hover\:surface-hover:hover{background-color:var(--p-surface-50,#f8fafc)!important;transition:background .15s}</style>
</head>
<body>
<div id="root"></div>
<?php
$nonce = wp_create_nonce( 'wp_rest' );
echo '<script>window.paywayAuditCfg={nonce:"' . esc_js( $nonce ) . '"};' .
     'window.__paywayFetchPatched||(window.__paywayFetchPatched=1,(function(){' .
     'var oF=window.fetch;window.fetch=function(u,o){' .
     'if(typeof u==="string"&&u.indexOf("/payway/v1/")>-1){' .
     'o=Object.assign({},o||{});var h=o.headers||{};' .
     'if(h instanceof Headers){h=Object.fromEntries(h.entries());}' .
     'h["X-WP-Nonce"]=(window.paywayAuditCfg&&window.paywayAuditCfg.nonce)||"";' .
     'o.headers=h;}return oF.call(this,u,o);}})());</script>';
?>
<?php
$nonce = wp_create_nonce( 'wp_rest' );
echo '<script>window.paywayAuditCfg={nonce:"' . esc_js( $nonce ) . '"};' .
     'window.__paywayFetchPatched||(window.__paywayFetchPatched=1,(function(){' .
     'var oF=window.fetch;window.fetch=function(u,o){' .
     'if(typeof u==="string"&&u.indexOf("/payway/v1/")>-1){' .
     'o=Object.assign({},o||{});var h=o.headers||{};' .
     'if(h instanceof Headers){h=Object.fromEntries(h.entries());}' .
     'h["X-WP-Nonce"]=(window.paywayAuditCfg&&window.paywayAuditCfg.nonce)||"";' .
     'o.headers=h;}return oF.call(this,u,o);}})());</script>';
?>
<?php wp_footer(); ?>
</body>
</html>
