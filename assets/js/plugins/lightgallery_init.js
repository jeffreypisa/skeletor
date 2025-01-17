import lightGallery from 'lightgallery';

// Plugins
import lgThumbnail from 'lightgallery/plugins/thumbnail';
import lgZoom from 'lightgallery/plugins/zoom';

export function lightgallery_init() {
	const container = document.getElementById('gallery-container');
	if (!container) return; // Veiligheidscontrole: Voorkomt fouten als de container niet bestaat.

	lightGallery(container, {
		speed: 500,
		plugins: [lgThumbnail, lgZoom]
	});

	const requestFullScreen = () => {
		const el = document.documentElement;
		if (el.requestFullscreen) {
			el.requestFullscreen();
		} else if (el.msRequestFullscreen) {
			el.msRequestFullscreen();
		} else if (el.mozRequestFullScreen) {
			el.mozRequestFullScreen();
		} else if (el.webkitRequestFullscreen) {
			el.webkitRequestFullscreen();
		}
	};

	container.addEventListener('lgAfterOpen', () => {
		requestFullScreen();
	});
}