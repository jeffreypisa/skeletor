import lightGallery from 'lightgallery';

// Plugins
import lgThumbnail from 'lightgallery/plugins/thumbnail';
import lgZoom from 'lightgallery/plugins/zoom';
import lgVideo from 'lightgallery/plugins/video';

export function lightgalleryInit() {
	const containers = document.querySelectorAll('.js-lightgallery');
	if (!containers.length) {
		return;
	}

	const pauseAllVimeoIframes = () => {
		const iframes = document.querySelectorAll('.lg-container iframe[src*="vimeo.com"]');
		iframes.forEach((iframe) => {
			if (iframe?.contentWindow) {
				iframe.contentWindow.postMessage('{"method":"pause"}', '*');
			}
		});
	};

	containers.forEach((container) => {
		if (container.dataset.lgInitialized === '1') {
			return;
		}

		lightGallery(container, {
			selector: '.js-gallery-item',
			speed: 500,
			download: false,
			counter: true,
			plugins: [lgThumbnail, lgZoom, lgVideo]
		});

		container.dataset.lgInitialized = '1';
		container.addEventListener('lgBeforeSlide', pauseAllVimeoIframes);
		container.addEventListener('lgBeforeClose', pauseAllVimeoIframes);
	});
}
