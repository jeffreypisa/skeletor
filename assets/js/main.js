import bootstrap from 'bootstrap/dist/js/bootstrap.bundle';
window.bootstrap = bootstrap;

// Init plugins
import { lightgallery_init } from './plugins/lightgallery_init.js';
import { matchheight_init } from './plugins/matchheight_init.js';
import { swiper_init } from './plugins/swiper_init.js';

// Scripts
import { footer } from './scripts/footer.js';
import { header } from './scripts/header.js';
import { mobilemenu } from './scripts/mobilemenu.js';
import { nvr } from './scripts/nvr.js';
import { site_is_loaded } from './scripts/site_is_loaded.js';

document.addEventListener('DOMContentLoaded', () => {
	// Initialiseer plugins en scripts in volgorde
	header();
	mobilemenu();
	matchheight_init();
	footer();

	// Init plugins die afhankelijk zijn van DOM-content
	lightgallery_init();
	swiper_init();
	
	// Alles is geladen
	site_is_loaded();
});