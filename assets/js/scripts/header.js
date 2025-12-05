export function header() {
        const header = document.querySelector('.header');
        const topbar = document.querySelector('.topbar');
        const body = document.body;
        const root = document.documentElement;
        const dropdownToggles = document.querySelectorAll('[data-dropdown-trigger]');
        const dropdownPanels = document.querySelectorAll('[data-dropdown-panel]');
        const subNav = document.querySelector('.sub-nav');
        const panelTransition = 320;
        const stickyRevealOffset = 600;
        let lastScrollTop = 0;
        let ticking = false;
        let scrollThreshold = 24; // Voorkomt knipperen bij kleine bewegingen
        let scrolledThreshold = 140; // Vanaf wanneer de achtergrond wit wordt
        let stickyActive = false;
        let stickyJustActivated = false;

        const updateHeaderOffset = () => {
                const headerHeight = header?.offsetHeight || 0;
                const topbarHeight = topbar?.offsetHeight || 0;
                const totalHeight = headerHeight + topbarHeight;

                scrolledThreshold = Math.max(140, (headerHeight || 60) + 30);
                scrollThreshold = Math.max(16, Math.round((headerHeight || 60) * 0.4));
                root.style.setProperty('--header-height', `${headerHeight}px`);
                root.style.setProperty('--header-total-height', `${totalHeight}px`);
                root.style.setProperty('--header-sticky-space', `${totalHeight}px`);
        };

        const setStickyState = (active, { animate = false } = {}) => {
                stickyActive = active;
                header.classList.toggle('is-sticky', active);
                body.classList.toggle('header-sticky-active', active);

                if (!active) {
                        stickyJustActivated = false;
                        header.classList.remove('hidden', 'visible');
                        return;
                }

                if (animate) {
                        stickyJustActivated = true;
                        header.classList.add('hidden');
                        header.classList.remove('visible');

                        requestAnimationFrame(() => {
                                header.classList.add('visible');
                                header.classList.remove('hidden');
                        });

                        window.setTimeout(() => {
                                stickyJustActivated = false;
                        }, panelTransition);
                } else {
                        stickyJustActivated = false;
                }
        };

	const closeOpenDropdowns = () => {
		dropdownPanels.forEach((panel) => {
			panel.classList.remove('show');
			panel.classList.remove('is-leaving');
		});

                dropdownToggles.forEach((toggle) => {
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.classList.remove('show');
                        toggle.classList.remove('is-active');
                        const parent = toggle.closest('.nav-item');
                        parent?.classList.remove('show');
                        parent?.classList.remove('is-active');
                });

		subNav?.setAttribute('aria-hidden', 'true');
		subNav?.classList.remove('is-open');
	};

	const transitionExistingPanel = (panel) => {
		if (!panel || !panel.classList.contains('show')) return;

		panel.classList.add('is-leaving');
		panel.classList.remove('show');

		window.setTimeout(() => {
			panel.classList.remove('is-leaving');
		}, panelTransition);
	};

        dropdownToggles.forEach((toggle) => {
                const targetId = toggle.getAttribute('data-dropdown-trigger');

                toggle.addEventListener('click', (event) => {
                        event.preventDefault();

                        const currentExpanded = toggle.getAttribute('aria-expanded') === 'true';
			const targetPanel = document.querySelector(`[data-dropdown-panel="${targetId}"]`);

			const expandedPanel = document.querySelector('.dropdown-menu.show');

			if (currentExpanded) {
				closeOpenDropdowns();
				return;
			}

                        dropdownToggles.forEach((button) => {
                                const isActiveToggle = button === toggle;
                                const parent = button.closest('.nav-item');
                                button.setAttribute('aria-expanded', isActiveToggle ? 'true' : 'false');
                                button.classList.toggle('is-active', isActiveToggle);
                                parent?.classList.toggle('is-active', isActiveToggle);
                        });

			transitionExistingPanel(expandedPanel && expandedPanel != targetPanel ? expandedPanel : null);

			dropdownPanels.forEach((panel) => {
				if (panel !== targetPanel && panel !== expandedPanel) {
					panel.classList.remove('show');
					panel.classList.remove('is-leaving');
				}
			});

			if (targetPanel) {
				targetPanel.classList.add('show');
				subNav?.setAttribute('aria-hidden', 'false');
				subNav?.classList.add('is-open');
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
                const beyondScrolled = scrollTop > scrolledThreshold;
                const direction = scrollTop > lastScrollTop ? 'down' : 'up';
                const pastStickyReveal = scrollTop > stickyRevealOffset;

                if (document.querySelector('.dropdown-menu.show') && direction === 'down') {
                        closeOpenDropdowns();
                }

                if (beyondScrolled) {
                        header.classList.add('scrolled');
                } else {
                        header.classList.remove('scrolled');
                }

                if (scrollTop <= 0) {
                        setStickyState(false);
                        lastScrollTop = 0;
                        ticking = false;
                        return;
                }

                if (!pastStickyReveal) {
                        setStickyState(false);
                        lastScrollTop = scrollTop;
                        ticking = false;
                        return;
                }

                if (direction === 'up') {
                        if (!stickyActive) {
                                setStickyState(true, { animate: true });
                        }

                        if (!stickyJustActivated) {
                                header.classList.remove('hidden');
                                header.classList.add('visible');
                        }
                } else if (stickyActive && !stickyJustActivated && scrollTop > lastScrollTop + scrollThreshold) {
                        header.classList.remove('visible');
                        header.classList.add('hidden');
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

	header?.classList.add('visible');
	updateHeaderOffset();
}
