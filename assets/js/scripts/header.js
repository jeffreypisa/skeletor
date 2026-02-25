export function header() {
    const header = document.querySelector('.header');
    const topbar = document.querySelector('.topbar');
    const body = document.body;
    const root = document.documentElement;
    const dropdownToggles = document.querySelectorAll('[data-dropdown-trigger]');
    const dropdownPanels = document.querySelectorAll('[data-dropdown-panel]');
    const subNav = document.querySelector('.sub-nav');
    const takeoverToggle = document.querySelector('[data-takeover-toggle]');
    const takeoverMenu = document.querySelector('[data-takeover-menu]');
    const panelTransition = 320;
    const stickyRevealOffset = 600;
    const menuBreakpoint = header?.dataset.menuBreakpoint || 'lg';
    const menuVariant = header?.dataset.menuVariant || 'dropdown';

    let lastScrollTop = 0;
    let ticking = false;
    let scrollThreshold = 24;
    let scrolledThreshold = 140;
    let stickyActive = false;
    let stickyJustActivated = false;
    let menuBreakpointWidth = getBreakpointWidth();

    function getBreakpointWidth() {
        if (menuBreakpoint === 'all') {
            return 0;
        }

        const breakpointVar = `--bs-breakpoint-${menuBreakpoint}`;
        const cssValue = getComputedStyle(document.documentElement)
            .getPropertyValue(breakpointVar)
            .trim();
        const numericValue = parseInt(cssValue, 10);

        return Number.isNaN(numericValue) ? 992 : numericValue;
    }

    const isDesktop = () => window.innerWidth >= menuBreakpointWidth;
    const isTakeoverVariant = () => menuVariant === 'takeover';
    const toggleLabelOpen = takeoverToggle?.dataset.labelOpen || 'Menu';
    const toggleLabelClose = takeoverToggle?.dataset.labelClose || 'Close';
    const configuredTakeoverButtonVariant =
        takeoverToggle?.dataset.takeoverButtonVariant || 'auto';
    const takeoverButtonClassLightHeader =
        takeoverToggle?.dataset.takeoverButtonClassLight || 'btn-outline-dark';
    const takeoverButtonClassDarkHeader =
        takeoverToggle?.dataset.takeoverButtonClassDark || 'btn-outline-light';
    const takeoverButtonActiveClassLightHeader =
        takeoverToggle?.dataset.takeoverButtonActiveClassLight ||
        takeoverButtonClassLightHeader;
    const takeoverButtonActiveClassDarkHeader =
        takeoverToggle?.dataset.takeoverButtonActiveClassDark ||
        takeoverButtonClassDarkHeader;
    const splitClassString = (value) =>
        (value || '')
            .split(/\s+/)
            .map((token) => token.trim())
            .filter(Boolean);
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');

    const getTakeoverFocusableElements = () => {
        if (!header || !takeoverMenu) return [];

        const combined = [
            ...Array.from(header.querySelectorAll(focusableSelector)),
            ...Array.from(takeoverMenu.querySelectorAll(focusableSelector)),
        ];

        return Array.from(new Set(combined)).filter((element) => {
            if (!(element instanceof HTMLElement)) return false;
            if (element.closest('[hidden]')) return false;
            if (element.closest('[aria-hidden="true"]')) return false;

            const styles = window.getComputedStyle(element);
            if (styles.display === 'none' || styles.visibility === 'hidden') {
                return false;
            }

            return element.getClientRects().length > 0;
        });
    };

    const syncTakeoverToggleVariant = () => {
        if (!takeoverToggle || !isTakeoverVariant()) return;
        const allManagedClasses = [
            ...splitClassString(takeoverButtonClassLightHeader),
            ...splitClassString(takeoverButtonClassDarkHeader),
            ...splitClassString(takeoverButtonActiveClassLightHeader),
            ...splitClassString(takeoverButtonActiveClassDarkHeader),
        ];
        const applyButtonClass = (classString) => {
            if (allManagedClasses.length > 0) {
                takeoverToggle.classList.remove(...allManagedClasses);
            }

            const nextClasses = splitClassString(classString);
            if (nextClasses.length > 0) {
                takeoverToggle.classList.add(...nextClasses);
            }
        };
        const isActiveState = document.body.classList.contains('takeovermenu-open');
        const classForTone = (tone) => {
            if (tone === 'dark') {
                return isActiveState
                    ? takeoverButtonActiveClassDarkHeader
                    : takeoverButtonClassDarkHeader;
            }

            return isActiveState
                ? takeoverButtonActiveClassLightHeader
                : takeoverButtonClassLightHeader;
        };

        if (configuredTakeoverButtonVariant === 'light' || configuredTakeoverButtonVariant === 'dark') {
            applyButtonClass(classForTone(configuredTakeoverButtonVariant));
            return;
        }

        const isTakeoverOpen = document.body.classList.contains('takeovermenu-open');
        const isTransparentHeader = document.body.classList.contains('header-transparent');
        const isHeaderScrolled = header?.classList.contains('scrolled');
        const resolvedTone = isTakeoverOpen || !isTransparentHeader || isHeaderScrolled
            ? 'light'
            : 'dark';

        applyButtonClass(classForTone(resolvedTone));
    };

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
            root.style.setProperty('--header-sticky-offset', '0px');
            return;
        }

        if (prepare) {
            body.classList.add('header-sticky-active');
            header.classList.add('no-anim');
            header.classList.add('is-sticky');
            header.classList.add('hidden');
            header.classList.remove('visible');
            root.style.setProperty('--header-sticky-offset', '0px');
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
            root.style.setProperty('--header-sticky-offset', '0px');

            requestAnimationFrame(() => {
                header.classList.add('visible');
                header.classList.remove('hidden');
                root.style.setProperty(
                    '--header-sticky-offset',
                    'var(--header-height)'
                );
            });

            window.setTimeout(() => {
                stickyJustActivated = false;
            }, panelTransition);
        } else {
            stickyJustActivated = false;
            header.classList.add('visible');
            header.classList.remove('hidden');
            root.style.setProperty(
                '--header-sticky-offset',
                'var(--header-height)'
            );
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

    const closeTakeover = ({ returnFocus = false } = {}) => {
        if (!takeoverMenu || !takeoverToggle) return;

        document.body.classList.remove('takeovermenu-open');
        takeoverToggle.setAttribute('aria-expanded', 'false');
        takeoverMenu.setAttribute('aria-hidden', 'true');
        takeoverToggle.textContent = toggleLabelOpen;
        syncTakeoverToggleVariant();

        if (returnFocus) {
            takeoverToggle.focus();
        }
    };

    const openTakeover = () => {
        if (!takeoverMenu || !takeoverToggle) return;

        closeOpenDropdowns();
        document.body.classList.add('takeovermenu-open');
        takeoverToggle.setAttribute('aria-expanded', 'true');
        takeoverMenu.setAttribute('aria-hidden', 'false');
        takeoverToggle.textContent = toggleLabelClose;
        syncTakeoverToggleVariant();
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
        const isTakeoverToggle = event.target.closest('[data-takeover-toggle]');
        const isTakeoverMenu = event.target.closest('[data-takeover-menu]');

        if (!isToggle && !isPanel) {
            closeOpenDropdowns();
        }

        if (
            isTakeoverVariant() &&
            document.body.classList.contains('takeovermenu-open') &&
            !isTakeoverToggle &&
            !isTakeoverMenu
        ) {
            closeTakeover();
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

        syncTakeoverToggleVariant();

        if (!desktopView) {
            setStickyState(false);
            closeTakeover();
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
                root.style.setProperty('--header-sticky-offset', '0px');
            }
        } else {
            if (stickyActive) {
                if (!stickyJustActivated && header.classList.contains('hidden')) {
                    setStickyState(true, { animate: true });
                } else {
                    header.classList.remove('hidden');
                    header.classList.add('visible');
                    root.style.setProperty(
                        '--header-sticky-offset',
                        'var(--header-height)'
                    );
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
        syncTakeoverToggleVariant();

        if (!isDesktop()) {
            setStickyState(false);
            closeTakeover();
        }
    });

    takeoverToggle?.addEventListener('click', () => {
        if (!isTakeoverVariant()) return;

        if (document.body.classList.contains('takeovermenu-open')) {
            closeTakeover({ returnFocus: true });
            return;
        }

        openTakeover();
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Tab' &&
            isTakeoverVariant() &&
            document.body.classList.contains('takeovermenu-open')
        ) {
            const focusableElements = getTakeoverFocusableElements();
            if (!focusableElements.length) return;

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            const activeElement = document.activeElement;

            if (!focusableElements.includes(activeElement)) {
                event.preventDefault();
                (event.shiftKey ? lastElement : firstElement).focus();
                return;
            }

            if (!event.shiftKey && activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
                return;
            }

            if (event.shiftKey && activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
                return;
            }
        }

        if (event.key !== 'Escape') return;

        closeOpenDropdowns();
        if (document.body.classList.contains('takeovermenu-open')) {
            closeTakeover({ returnFocus: true });
        }
    });

    header?.classList.add('visible');
    updateHeaderOffset();
    syncTakeoverToggleVariant();
}
