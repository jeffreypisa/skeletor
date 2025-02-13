export function scrollToNextSection() {
	const scrollButtons = document.querySelectorAll('.js-scrollnextsection');

	scrollButtons.forEach(button => {
		button.addEventListener('click', () => {
			const currentSection = button.closest('section');

			if (!currentSection) {
				console.error('De huidige sectie kon niet worden gevonden.');
				return;
			}

			const allSections = Array.from(document.querySelectorAll('section'));
			const currentIndex = allSections.indexOf(currentSection);

			if (currentIndex === -1 || currentIndex === allSections.length - 1) {
				console.warn('Geen volgende sectie om naartoe te scrollen.');
				return;
			}

			const nextSection = allSections[currentIndex + 1];
			smoothScrollTo(nextSection, 200); // 1000 ms (1 seconde) voor langzamer scrollen
		});
	});
}

function smoothScrollTo(target, duration) {
	const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
	const startPosition = window.pageYOffset;
	const distance = targetPosition - startPosition;
	const startTime = performance.now();

	function animation(currentTime) {
		const elapsedTime = currentTime - startTime;
		const progress = Math.min(elapsedTime / duration, 1); // Zorgt dat het niet voorbij de 1 gaat
		const ease = easeInOutQuad(progress); // Easing functie voor soepel effect

		window.scrollTo(0, startPosition + distance * ease);

		if (progress < 1) {
			requestAnimationFrame(animation);
		}
	}

	requestAnimationFrame(animation);
}

function easeInOutQuad(t) {
	return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}