<?php
function weburov_gdpr_banner() {
    ?>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/gdpr-banner.css">
    <div id="weburov-gdpr-banner">
        <p>Този сайт използва бисквитки за по-добро изживяване. <button id="weburov-gdpr-accept">Разбрах</button></p>
    </div>
    <script src="<?php echo get_template_directory_uri(); ?>/js/gdpr-banner.js"></script>
    <?php
}
add_action('wp_footer', 'weburov_gdpr_banner');
?>
