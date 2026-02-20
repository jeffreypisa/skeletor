<?php

require_once __DIR__ . '/library/accordion/Accordion.php';
require_once __DIR__ . '/library/button/Button.php';
require_once __DIR__ . '/library/breadcrumbs/Breadcrumbs.php';
require_once __DIR__ . '/library/filter/Filter.php';
require_once __DIR__ . '/library/filter/FilterAjax.php';
require_once __DIR__ . '/library/shared/InviewAnimationOptions.php';
require_once __DIR__ . '/library/heading/Heading.php';
require_once __DIR__ . '/library/image/Image.php';
require_once __DIR__ . '/library/icon/Icon.php';
require_once __DIR__ . '/library/swiper/Swiper.php';
require_once __DIR__ . '/library/text/Text.php';
require_once __DIR__ . '/library/inpage-nav/InpageNav.php';

new Components_Accordion();
new Components_Button();
new Components_Breadcrumbs();
new Components_Filter();
new Components_Heading();
new Components_Image();
new Components_Icon();
new Components_InpageNav();
new Components_Swiper();
new Components_Text();

// AJAX-handler registreren zodra deze file geladen wordt
if (class_exists('Components_FilterAjax')) {
	add_action('wp_ajax_ajax_filter', ['Components_FilterAjax', 'handle']);
	add_action('wp_ajax_nopriv_ajax_filter', ['Components_FilterAjax', 'handle']);
} else {
	error_log('❌ Components_FilterAjax bestaat niet bij registratie');
}
