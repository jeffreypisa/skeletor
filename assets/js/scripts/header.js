export function header() {
	const header = document.querySelector('header');
	let lastScrollTop = 0;
	let isHeaderVisible = true;

	window.addEventListener('scroll', () => {
		const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

		// Als we helemaal bovenaan zijn, zorg dat de header zichtbaar is
		if (scrollTop === 0) {
			header.style.opacity = '1';
			header.style.transform = 'translateY(0)';
			isHeaderVisible = true;
			return;
		}

		// Bij naar beneden scrollen
		if (scrollTop > lastScrollTop && isHeaderVisible) {
			// Verberg de header
			header.style.opacity = '0';
			header.style.transform = 'translateY(-100%)';
			isHeaderVisible = false;
		}

		// Bij naar boven scrollen
		if (scrollTop < lastScrollTop && !isHeaderVisible) {
			// Laat de header weer zien
			header.style.opacity = '1';
			header.style.transform = 'translateY(0)';
			isHeaderVisible = true;
		}

		// Update de laatste scrollpositie
		lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
	});
}