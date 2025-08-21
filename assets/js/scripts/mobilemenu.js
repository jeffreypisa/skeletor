export function mobileMenu() {
        const mobileMenuBtn = document.getElementById('mobilemenubtn');
        const mobileMenu = document.getElementById('mobilemenu');
        const breakpoint = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--mobile-menu-breakpoint')) || 992;

        mobileMenuBtn.addEventListener('click', () => {
                const isOpen = mobileMenuBtn.classList.toggle('active');
                mobileMenu.classList.toggle('active', isOpen);
                mobileMenuBtn.setAttribute('aria-expanded', isOpen);
        });

        // Sluiten bij klikken buiten het menu
        document.addEventListener('click', (e) => {
                if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                        mobileMenuBtn.classList.remove('active');
                        mobileMenu.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
        });

        // Sluiten bij venstergrootte voorbij het breekpunt
        window.addEventListener('resize', () => {
                if (window.innerWidth >= breakpoint) {
                        mobileMenuBtn.classList.remove('active');
                        mobileMenu.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
        });

        // Sluiten met Escape-toets
        document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                        mobileMenuBtn.classList.remove('active');
                        mobileMenu.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
        });
}
