// Updated JavaScript for Swiper Component
import Swiper from 'swiper/bundle';
import { matchheightUpdate } from './matchheightInit.js';

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
						syncGridLayoutClass(container, this);

                                                if (!this.params.loop) {
                                                        updateNavigation(this);
                                                }

						// Pas styling toe voor nextSlideVisible indien van toepassing
						applyNextSlideVisible(container, settings, this);

						// Controleer of er bullets zijn en pas marge toe
                                                togglePaginationClass(container, this);

                                                // Verberg bullets als alle slides zichtbaar zijn
                                                togglePaginationVisibility(container, this);

                                                matchheightUpdate();
                                        },
                                        slideChange: function () {
						syncGridLayoutClass(container, this);

                                                if (!this.params.loop) {
                                                        updateNavigation(this);
                                                }
                                                applyNextSlideVisible(container, settings, this);
                                                togglePaginationClass(container, this);
                                                togglePaginationVisibility(container, this);

                                                matchheightUpdate();
                                        },
                                        resize: function () {
						syncGridLayoutClass(container, this);

                                                applyNextSlideVisible(container, settings, this);
                                                togglePaginationClass(container, this);
                                                togglePaginationVisibility(container, this);

                                                matchheightUpdate();
                                        },
					breakpoint: function () {
						syncGridLayoutClass(container, this);
						this.update();
						applyNextSlideVisible(container, settings, this);
						togglePaginationClass(container, this);
						togglePaginationVisibility(container, this);
						matchheightUpdate();
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

                        syncGridLayoutClass(container, swiperInstance);
                        applyNextSlideVisible(container, settings, swiperInstance);
                        togglePaginationClass(container, swiperInstance);
                        togglePaginationVisibility(container, swiperInstance);

                        matchheightUpdate();

                } catch (error) {
                        console.error('Fout bij het verwerken van Swiper-instellingen:', error);
                }
        });
}

function applyNextSlideVisible(container, settings, swiper) {
	const gridRows = getGridRows(swiper);

	if (
		settings.nextSlideVisible &&
		swiper.slides.length > 1 &&
		swiper.params.slidesPerView === 1 &&
		gridRows === 1
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
	const allSlidesVisible = swiper.slides.length <= getVisibleSlideSlots(swiper);

	if (swiper.pagination.el && !allSlidesVisible) {
		container.classList.add('has-pagination');
	} else {
		container.classList.remove('has-pagination');
	}
}

function togglePaginationVisibility(container, swiper) {
	const allSlidesVisible = swiper.slides.length <= getVisibleSlideSlots(swiper);
	const paginationEl = container.querySelector('.swiper-pagination');

	if (paginationEl) {
		paginationEl.style.display = allSlidesVisible ? 'none' : 'flex';
	}
}

function getGridRows(swiper) {
	const rows = Number(swiper?.params?.grid?.rows || 1);
	return Number.isFinite(rows) && rows > 0 ? rows : 1;
}

function getVisibleSlideSlots(swiper) {
	const slidesPerView = Number(swiper?.params?.slidesPerView);

	if (!Number.isFinite(slidesPerView) || slidesPerView <= 0) {
		return 1;
	}

	return slidesPerView * getGridRows(swiper);
}

function syncGridLayoutClass(container, swiper) {
	const isGrid = getGridRows(swiper) > 1;
	const fillMode = swiper?.params?.grid?.fill;

	container.classList.toggle('swiper-grid', isGrid);
	container.classList.toggle('swiper-grid-column', isGrid && fillMode === 'column');
}
