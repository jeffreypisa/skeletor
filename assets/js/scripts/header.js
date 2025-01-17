export function header() {
	const header = document.querySelector('header');
	let lastScrollTop = 0;
	let isHeaderVisible = true;
	let isSlidingIn = false;

	window.addEventListener('scroll', () => {
		const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

		// Als we helemaal naar boven scrollen, reset de header naar zichtbaar
		if (scrollTop === 0) {
			header.style.opacity = '1';
			header.style.transform = 'translateY(0)';
			isHeaderVisible = true;
			isSlidingIn = false; // Reset sliding state
			return; // Geen verdere logica nodig
		}

		// Bij het naar beneden scrollen
		if (scrollTop > 80 && scrollTop > lastScrollTop && isHeaderVisible) {
			// Verberg de header direct
			header.style.opacity = '0';
			header.style.transform = 'translateY(-100%)';
			isHeaderVisible = false;
		}

		// Bij het naar boven scrollen
		if (scrollTop < lastScrollTop - 10 && !isHeaderVisible) {
			// Maak de header direct zichtbaar
			header.style.opacity = '1';

			// Trigger de slide-in animatie alleen de eerste keer
			if (!isSlidingIn) {
				header.style.transform = 'translateY(0)';
				isSlidingIn = true;
			}

			isHeaderVisible = true;
		}

		// Update de vorige scrollwaarde
		lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
	});
}