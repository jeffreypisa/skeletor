export function mobileMenu() {
        const mobileMenuBtn = document.getElementById('mobilemenubtn');
        const mobileMenu = document.getElementById('mobilemenu');

        if (!mobileMenuBtn || !mobileMenu) {
                return;
        }

        const menuPanels = mobileMenu.querySelectorAll('[data-menu-panel]');

        const toggleAria = (isOpen) => {
                mobileMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                mobileMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        };

        const setActivePanel = (panelId) => {
                menuPanels.forEach((panel) => {
                        const isTarget = panel.id === panelId;
                        panel.classList.toggle('is-active', isTarget);
                        panel.setAttribute('aria-hidden', isTarget ? 'false' : 'true');
                });
        };

        const openMenu = () => {
                mobileMenuBtn.classList.add('active');
                mobileMenu.classList.add('active');
                toggleAria(true);
                setActivePanel('submenu-root');
        };

        const closeMenu = () => {
                mobileMenuBtn.classList.remove('active');
                mobileMenu.classList.remove('active');
                toggleAria(false);
                setActivePanel('submenu-root');
        };

        const closeOnViewportChange = () => {
                if (mobileMenu.classList.contains('active')) {
                        closeMenu();
                }
        };

        mobileMenuBtn.addEventListener('click', () => {
                if (mobileMenu.classList.contains('active')) {
                        closeMenu();
                } else {
                        openMenu();
                }
        });

        mobileMenu.addEventListener('click', (event) => {
                const targetButton = event.target.closest('[data-submenu-target]');
                const backButton = event.target.closest('[data-submenu-back]');

                if (targetButton) {
                        event.preventDefault();
                        setActivePanel(targetButton.dataset.submenuTarget);
                        return;
                }

                if (backButton) {
                        event.preventDefault();
                        setActivePanel(backButton.dataset.submenuBack);
                        return;
                }

                if (event.target.matches('[data-close-mobilemenu]')) {
                        closeMenu();
                }
        });

        document.addEventListener('click', (event) => {
                const clickOutside =
                        !mobileMenu.contains(event.target) &&
                        !mobileMenuBtn.contains(event.target);

                if (mobileMenu.classList.contains('active') && clickOutside) {
                        closeMenu();
                }
        });

        document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && mobileMenu.classList.contains('active')) {
                        closeMenu();
                }
        });

        window.addEventListener('resize', closeOnViewportChange);
        window.addEventListener('scroll', closeOnViewportChange, { passive: true });
}
