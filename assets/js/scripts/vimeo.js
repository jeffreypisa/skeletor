import Player from '@vimeo/player';

export function vimeo() {
	const iframe = document.querySelector('.strook-banner .video-container iframe');

	if (iframe) {
		const player = new Player(iframe);

		// Luister naar het 'play'-event
		player.on('play', function () {
			iframe.closest('.strook-banner').classList.add('video-loaded');
		});
	}
}