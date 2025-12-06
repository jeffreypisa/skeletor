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
    const menuBreakpoint = header?.dataset.menuBreakpoint || 'lg';

    let lastScrollTop = 0;
    let ticking = false;
    let scrollThreshold = 24;
    let scrolledThreshold = 140;
    let stickyActive = false;
    let stickyJustActivated = false;
    let menuBreakpointWidth = getBreakpointWidth();

    function getBreakpointWidth() {
        const breakpointVar = `--bs-breakpoint-${menuBreakpoint}`;
        const cssValue = getComputedStyle(document.documentElement)
            .getPropertyValue(breakpointVar)
            .trim();
        const numericValue = parseInt(cssValue, 10);

        return Number.isNaN(numericValue) ? 992 : numericValue;
    }

    const isDesktop = () => window.innerWidth >= menuBreakpointWidth;

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

    const setStickyState = (active, { animate = false, prepare = false } = {}) => {
        stickyActive = active;

        if (!active) {
            stickyJustActivated = false;
            header.classList.remove('is-sticky', 'hidden', 'visible', 'no-anim');
            body.classList.remove('header-sticky-active');
            return;
        }

        if (prepare) {
            body.classList.add('header-sticky-active');
            header.classList.add('no-anim');
            header.classList.add('is-sticky');
            header.classList.add('hidden');
            header.classList.remove('visible');
            header.offsetHeight;
            header.classList.remove('no-anim');
            stickyJustActivated = false;
            return;
        }

        body.classList.add('header-sticky-active');
        header.classList.add('is-sticky');

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
            header.classList.add('visible');
            header.classList.remove('hidden');
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
            toggle.parentElement?.classList.remove('show');
            toggle.parentElement?.classList.remove('is-active');
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
            const targetPanel = document.querySelector(
                `[data-dropdown-panel="${targetId}"]`
            );
            const expandedPanel = document.querySelector('.dropdown-menu.show');

            if (currentExpanded) {
                closeOpenDropdowns();
                return;
            }

            dropdownToggles.forEach((button) => {
                const active = button === toggle;
                button.setAttribute('aria-expanded', active ? 'true' : 'false');
                button.classList.toggle('is-active', active);
                button.parentElement?.classList.toggle('is-active', active);
            });

            transitionExistingPanel(
                expandedPanel && expandedPanel !== targetPanel ? expandedPanel : null
            );

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
        const desktopView = isDesktop();

        if (desktopView && document.querySelector('.dropdown-menu.show')) {
            closeOpenDropdowns();
        }

        if (beyondScrolled) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        if (!desktopView) {
            setStickyState(false);
            lastScrollTop = scrollTop;
            ticking = false;
            return;
        }

        if (scrollTop <= 0) {
            setStickyState(false);
            lastScrollTop = 0;
            ticking = false;
            return;
        }

        if (!pastStickyReveal) {
            if (direction === 'down') {
                setStickyState(false);
            }
            lastScrollTop = scrollTop;
            ticking = false;
            return;
        }

        if (direction === 'down') {
            if (!stickyActive && pastStickyReveal) {
                setStickyState(true, { prepare: true });
            } else if (
                stickyActive &&
                !stickyJustActivated &&
                scrollTop > lastScrollTop + scrollThreshold
            ) {
                header.classList.remove('visible');
                header.classList.add('hidden');
            }
        } else {
            if (stickyActive) {
                if (!stickyJustActivated && header.classList.contains('hidden')) {
                    setStickyState(true, { animate: true });
                } else {
                    header.classList.remove('hidden');
                    header.classList.add('visible');
                }
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
        menuBreakpointWidth = getBreakpointWidth();
        updateHeaderOffset();
        closeOpenDropdowns();

        if (!isDesktop()) {
            setStickyState(false);
        }
    });

    header?.classList.add('visible');
    updateHeaderOffset();
}