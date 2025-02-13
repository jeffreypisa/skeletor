export function footer() {
	function footerdown() {
		const footer = document.querySelector('footer').offsetHeight;
		const windowHeight = window.innerHeight;
		const contentMinHeight = windowHeight - footer;
		document.querySelector('main').style.minHeight = `${contentMinHeight}px`;
	}

	footerdown();

	window.addEventListener('resize', footerdown);
}