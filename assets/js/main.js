import bootstrap from 'bootstrap/dist/js/bootstrap.bundle';
window.bootstrap = bootstrap;

// Init plugins
import { lightgalleryInit } from './plugins/lightgalleryInit.js';
import { matchheightInit } from './plugins/matchheightInit.js';
import { swiperInit } from './plugins/swiperInit.js';

// Scripts
import { cta } from './scripts/cta.js';
import { filter } from './scripts/filter.js';
import { footer } from './scripts/footer.js';
import { header } from './scripts/header.js';
import { mobileMenu } from './scripts/mobileMenu.js';
import { inpageNav } from './scripts/inpageNav.js';
import { scrollAnimations } from './scripts/scrollAnimations.js';
import { scrollToNextSection } from './scripts/scrollToNextSection.js';
import { siteIsLoaded } from './scripts/siteIsLoaded.js';
import { vimeo } from './scripts/vimeo.js';
import { wcag } from './scripts/wcag.js';

document.addEventListener('DOMContentLoaded', () => {
	// Initialiseer plugins en scripts in volgorde
	header();
	mobileMenu();
	footer();
	scrollToNextSection();

	// Init plugins die afhankelijk zijn van DOM-content
	lightgalleryInit();
	inpageNav();
	wcag();
	cta();
	if (document.querySelector('[data-filter-form]')) {
	  filter(); // initialiseer alleen als filter-formulier op pagina staat
	}
	scrollAnimations();
	matchheightInit();
	swiperInit()
	
	// Alles is geladen
	vimeo();
	siteIsLoaded();
});
