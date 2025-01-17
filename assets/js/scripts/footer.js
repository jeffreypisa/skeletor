export function footer() {
	function footerdown() {
		const header = document.querySelector('header').offsetHeight;
		const footer = document.querySelector('footer').offsetHeight;
		const windowHeight = window.innerHeight;
		const contentMinHeight = windowHeight - header - footer;
		document.querySelector('main').style.minHeight = `${contentMinHeight}px`;
	}

	footerdown();

	window.addEventListener('resize', footerdown);
}