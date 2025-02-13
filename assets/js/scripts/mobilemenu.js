export function mobileMenu() {
	const mobileMenuBtn = document.getElementById('mobilemenubtn');
	const mobileMenu = document.getElementById('mobilemenu');

	mobileMenuBtn.addEventListener('click', () => {
		mobileMenuBtn.classList.toggle('active');
		mobileMenu.classList.toggle('active');
	});

	// Optioneel: Sluiten bij klikken buiten het menu
	document.addEventListener('click', (e) => {
		if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
			mobileMenuBtn.classList.remove('active');
			mobileMenu.classList.remove('active');
		}
	});
}