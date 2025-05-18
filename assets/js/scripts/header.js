export function header() {
	const header = document.querySelector('.header');
	let lastScrollTop = 0;
	let lastDirection = 'up';
	let ticking = false;
	const scrollThreshold = 10; // Voorkomt knipperen bij kleine bewegingen
	const scrolledThreshold = 50; // Vanaf wanneer de achtergrond wit wordt

	function updateHeader() {
		const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

		// Voeg of verwijder de 'scrolled' class op basis van scrollhoogte
		if (scrollTop > scrolledThreshold) {
			header.classList.add('scrolled');
		} else {
			header.classList.remove('scrolled');
		}

		if (scrollTop > lastScrollTop + scrollThreshold) {
			// Snel naar beneden scrollen → header verdwijnt
			if (lastDirection !== 'down') {
				header.classList.remove('visible');
				header.classList.add('hidden');
				lastDirection = 'down';
			}
		} else if (scrollTop < lastScrollTop - scrollThreshold) {
			// Een beetje naar boven scrollen → header verschijnt
			if (lastDirection !== 'up') {
				header.classList.remove('hidden');
				header.classList.add('visible');
				lastDirection = 'up';
			}
		}

		lastScrollTop = scrollTop;
		ticking = false;
	}

	window.addEventListener('scroll', () => {
		if (!ticking) {
			requestAnimationFrame(updateHeader);
			ticking = true;
		}
	});
}