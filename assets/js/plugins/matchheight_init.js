import MatchHeight from 'matchheight';

export function matchheight_init() {
	// Controleer of MatchHeight beschikbaar is
	if (typeof MatchHeight !== 'function') {
		console.error('MatchHeight library is not loaded.');
		return;
	}

	// Initialiseert MatchHeight
	new MatchHeight();

	// Stel de waarde voor `--vh` in
	const setViewportHeight = () => {
		const vh = window.innerHeight * 0.01;
		document.documentElement.style.setProperty('--vh', `${vh}px`);
	};

	setViewportHeight();
	window.addEventListener('resize', setViewportHeight);
}