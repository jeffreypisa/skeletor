export function header() {
        const header = document.querySelector('.header');
        const topbar = document.querySelector('.topbar');
        const dropdownToggles = document.querySelectorAll('[data-dropdown-trigger]');
        const dropdownPanels = document.querySelectorAll('[data-dropdown-panel]');
        const subNav = document.querySelector('.sub-nav');
        let lastScrollTop = 0;
        let lastDirection = 'up';
        let ticking = false;
        const scrollThreshold = 10; // Voorkomt knipperen bij kleine bewegingen
        const scrolledThreshold = 50; // Vanaf wanneer de achtergrond wit wordt

        const updateHeaderOffset = () => {
                const headerHeight = header?.offsetHeight || 0;
                const topbarHeight = topbar?.offsetHeight || 0;
                const totalHeight = headerHeight || topbarHeight;

                document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
                document.documentElement.style.setProperty('--header-total-height', `${totalHeight}px`);
        };

        const closeOpenDropdowns = () => {
                dropdownPanels.forEach((panel) => panel.classList.remove('show'));

                dropdownToggles.forEach((toggle) => {
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.classList.remove('show');
                        toggle.classList.remove('is-active');
                        toggle.parentElement?.classList.remove('show');
                        toggle.parentElement?.classList.remove('is-active');
                });

                subNav?.setAttribute('aria-hidden', 'true');
        };

        dropdownToggles.forEach((toggle) => {
                const targetId = toggle.getAttribute('data-dropdown-trigger');

                toggle.addEventListener('click', (event) => {
                        event.preventDefault();

                        const currentExpanded = toggle.getAttribute('aria-expanded') === 'true';
                        const targetPanel = document.querySelector(`[data-dropdown-panel="${targetId}"]`);

                        closeOpenDropdowns();

                        if (!currentExpanded && targetPanel) {
                                toggle.setAttribute('aria-expanded', 'true');
                                toggle.classList.add('is-active');
                                toggle.parentElement?.classList.add('is-active');
                                targetPanel.classList.add('show');
                                subNav?.setAttribute('aria-hidden', 'false');
                        }
                });
        });

        document.addEventListener('click', (event) => {
                const isToggle = event.target.closest('[data-dropdown-trigger]');
                const isPanel = event.target.closest('[data-dropdown-panel]');

                if (!isToggle && !isPanel) {
                        closeOpenDropdowns();
                }
        });

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