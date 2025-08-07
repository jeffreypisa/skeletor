// Updated JavaScript for Swiper Component
import Swiper from 'swiper/bundle';

export function swiperInit() {
	const swiperContainers = document.querySelectorAll('.js-swiper');

	if (!swiperContainers.length) {

		return;
	}

	swiperContainers.forEach((container) => {
		try {
			const swiperId = container.getAttribute('data-swiper-id');

			// Parse de instellingen uit het data-attribuut
			const settings = JSON.parse(container.getAttribute('data-swiper-settings')) || {};

			// Pas overflow: hidden toe indien ingesteld
			if (settings.overflowHidden) {
				container.style.overflow = 'hidden';
			}

			// Initialiseer de Swiper met de gecombineerde instellingen
			const defaultSettings = {
				navigation: {
					nextEl: container.querySelector(`.swiper-button-next-${swiperId}`),
					prevEl: container.querySelector(`.swiper-button-prev-${swiperId}`)
				},
				pagination: settings.dots
					? { el: container.querySelector('.swiper-pagination'), clickable: true }
					: false,
				on: {
					init: function () {
						if (!this.params.loop) {
							updateNavigation(this);
						}

						// Pas styling toe voor nextSlideVisible indien van toepassing
						applyNextSlideVisible(container, settings, this);

						// Controleer of er bullets zijn en pas marge toe
						togglePaginationClass(container, this);

						// Verberg bullets als alle slides zichtbaar zijn
						togglePaginationVisibility(container, this);
					},
					slideChange: function () {
						if (!this.params.loop) {
							updateNavigation(this);
						}
						applyNextSlideVisible(container, settings, this);
						togglePaginationClass(container, this);
						togglePaginationVisibility(container, this);
					},
					resize: function () {
						applyNextSlideVisible(container, settings, this);
						togglePaginationClass(container, this);
						togglePaginationVisibility(container, this);
					},
					reachBeginning: function () {
						updateNavigation(this);
					},
					reachEnd: function () {
						updateNavigation(this);
					},
					fromEdge: function () {
						updateNavigation(this);
					}
				},
				loop: settings.loop || false
			};

			const finalSettings = Object.assign({}, defaultSettings, settings);

			const swiperInstance = new Swiper(container, finalSettings);

			if (!finalSettings.loop) {
				updateNavigation(swiperInstance);
			}

			applyNextSlideVisible(container, settings, swiperInstance);
			togglePaginationClass(container, swiperInstance);
			togglePaginationVisibility(container, swiperInstance);

		} catch (error) {
			console.error('Fout bij het verwerken van Swiper-instellingen:', error);
		}
	});
}

function applyNextSlideVisible(container, settings, swiper) {
	if (
		settings.nextSlideVisible &&
		swiper.slides.length > 1 &&
		swiper.params.slidesPerView === 1
	) {
		container.style.maxWidth = '80%';
		container.style.margin = '0 auto';
	} else {
		container.style.maxWidth = '';
		container.style.margin = '';
	}
}

function updateNavigation(swiper) {
	const container = swiper.el;
	const swiperId = container.getAttribute('data-swiper-id');
	const prevButton = container.querySelector(`.swiper-button-prev-${swiperId}`);
	const nextButton = container.querySelector(`.swiper-button-next-${swiperId}`);

	if (prevButton) {
		if (swiper.isBeginning) {
			prevButton.classList.add('disabled-swiper-button');
		} else {
			prevButton.classList.remove('disabled-swiper-button');
		}
	}

	if (nextButton) {
		if (swiper.isEnd) {
			nextButton.classList.add('disabled-swiper-button');
		} else {
			nextButton.classList.remove('disabled-swiper-button');
		}
	}
}

function togglePaginationClass(container, swiper) {
	const allSlidesVisible = swiper.slides.length <= swiper.params.slidesPerView;

	if (swiper.pagination.el && !allSlidesVisible) {
		container.classList.add('has-pagination');
	} else {
		container.classList.remove('has-pagination');
	}
}

function togglePaginationVisibility(container, swiper) {
	const allSlidesVisible = swiper.slides.length <= swiper.params.slidesPerView;
	const paginationEl = container.querySelector('.swiper-pagination');

	if (paginationEl) {
		paginationEl.style.display = allSlidesVisible ? 'none' : 'flex';
	}
} 