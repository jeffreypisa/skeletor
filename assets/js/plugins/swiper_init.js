import Swiper from 'swiper/bundle';

export function swiper_init() {
	const swiperContainer = document.querySelector('.swiper');
	if (!swiperContainer) {
		console.warn('Swiper container not found. Skipping Swiper initialization.');
		return;
	}

	// Initialiseer Swiper en return de instantie
	return new Swiper('.swiper', {
		direction: 'vertical',
		loop: true,
		pagination: {
			el: '.swiper-pagination',
			clickable: true
		},
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev'
		},
		scrollbar: {
			el: '.swiper-scrollbar',
			draggable: true
		},
		speed: 500,
		autoplay: {
			delay: 3000,
			disableOnInteraction: false
		}
	});
}