export function header() {
        const header = document.querySelector('.header');
        const topbar = document.querySelector('.topbar');
        const dropdownToggles = document.querySelectorAll('.primary-navigation .dropdown-toggle');
        let lastScrollTop = 0;
        let lastDirection = 'up';
        let ticking = false;
        const scrollThreshold = 10; // Voorkomt knipperen bij kleine bewegingen
        const scrolledThreshold = 50; // Vanaf wanneer de achtergrond wit wordt

        const updateHeaderOffset = () => {
                const headerHeight = header?.offsetHeight || 0;
                const topbarHeight = topbar?.offsetHeight || 0;
                document.documentElement.style.setProperty('--header-total-height', `${headerHeight + topbarHeight}px`);
        };

        const closeOpenDropdowns = () => {
                dropdownToggles.forEach((toggle) => {
                        if (window.bootstrap?.Dropdown) {
                                const instance = window.bootstrap.Dropdown.getOrCreateInstance(toggle);
                                instance.hide();
                        }

                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.classList.remove('show');
                        toggle.parentElement?.classList.remove('show');

                        const dropdownMenu = toggle.parentElement?.querySelector('.dropdown-menu');
                        dropdownMenu?.classList.remove('show');
                });
        };

        function updateHeader() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                if (document.querySelector('.dropdown-menu.show')) {
                        closeOpenDropdowns();
                }

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

        window.addEventListener('resize', () => {
                updateHeaderOffset();
                closeOpenDropdowns();
        });

        updateHeaderOffset();
}