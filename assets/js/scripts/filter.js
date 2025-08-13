import noUiSlider from 'nouislider';
import flatpickr from 'flatpickr/dist/flatpickr.js';
import rangePlugin from 'flatpickr/dist/plugins/rangePlugin.js';
import { swiperInit } from '../plugins/swiperInit.js';

export function filter() {
	const filterForm = document.querySelector('[data-filter-form]');
	const resultContainer = document.querySelector('#filter-results');
	const loadMoreBtn = document.querySelector('[data-load-more]');

	if (!filterForm || !resultContainer) return;

	const debounce = (fn, delay) => {
		let timeout;
		return (...args) => {
			clearTimeout(timeout);
			timeout = setTimeout(() => fn(...args), delay);
		};
	};

	const serializeForm = (form) => {
		const data = new FormData();
		const grouped = {};

		form.querySelectorAll('input, select, textarea').forEach((el) => {
			if (!el.name || el.disabled) return;
			const name = el.name.replace(/\[\]$/, '');

			if (el.type === 'checkbox') {
				if (el.checked) {
					if (!grouped[name]) grouped[name] = [];
					grouped[name].push(el.value);
				}
			} else if (el.type === 'radio') {
				if (el.checked) {
					grouped[name] = el.value;
				}
			} else if (el.tagName === 'SELECT' && el.multiple) {
				if (!grouped[name]) grouped[name] = [];
				Array.from(el.selectedOptions).forEach(opt => grouped[name].push(opt.value));
			} else {
				grouped[name] = el.value;
			}
		});

		for (const key in grouped) {
			const value = grouped[key];
			if (Array.isArray(value)) {
				value.forEach(v => data.append(`${key}[]`, v));
			} else {
				data.append(key, value);
			}
		}

		data.append('action', 'ajax_filter');
		data.append('post_type', form.dataset.postType || 'post');
		return data;
	};

	const ajaxUrl = (typeof window.ajaxurl !== 'undefined' && window.ajaxurl.url)
		? window.ajaxurl.url
		: '/wp-admin/admin-ajax.php';

	let currentPage = 1;
	let maxPages = null;

	const toggleLoader = (show) => {
		const results = document.querySelector('#filter-results');
		if (!results) return;
	
		results.classList.toggle('loading', show);
	};

	const animateItems = () => {
		const items = resultContainer.querySelectorAll('.fade-in-item');
		items.forEach((item, index) => {
			item.classList.remove('visible'); // reset animatie
			setTimeout(() => {
				item.classList.add('visible');
			}, 40 * index);
		});
	};
	
        const initOptionToggles = () => {
                requestAnimationFrame(() => {
                        document.querySelectorAll('[data-limit-options]').forEach(wrapper => {
                                const limit = parseInt(wrapper.dataset.limitOptions || 0, 10);
                                const expandLabel = wrapper.dataset.expandLabel || 'Toon meer';
                                const collapseLabel = wrapper.dataset.collapseLabel || 'Toon minder';

                                const options = wrapper.querySelectorAll('.form-check');
                                const toggleBtn = wrapper.querySelector('.filter-toggle-btn');

                                if (!toggleBtn || limit <= 0 || options.length <= limit) return;

                                const setState = (isExpanded) => {
                                        wrapper.dataset.expanded = isExpanded ? 'true' : 'false';
                                        options.forEach((opt, index) => {
                                                opt.style.display = (isExpanded || index < limit) ? '' : 'none';
                                        });
                                        toggleBtn.textContent = isExpanded ? collapseLabel : expandLabel;
                                };

                                const expanded = wrapper.dataset.expanded === 'true';
                                setState(expanded);

                                toggleBtn.classList.remove('d-none');

                                if (!toggleBtn.dataset.initialized) {
                                        toggleBtn.addEventListener('click', () => {
                                                const isExpanded = wrapper.dataset.expanded === 'true';
                                                setState(!isExpanded);
                                        });
                                        toggleBtn.dataset.initialized = 'true';
                                }
                        });
                });
        };
	
        const initFilterButtons = () => {
                document.querySelectorAll('.filter-buttons').forEach(group => {
                        const hiddenInput = group.nextElementSibling;
                        if (!hiddenInput || hiddenInput.type !== 'hidden') return;
	
			const buttons = group.querySelectorAll('[data-filter-button]');
	
			// Verwijder alle active eerst
			buttons.forEach(btn => btn.classList.remove('active'));
	
			// Lees huidige waarde uit input
			let currentValue = hiddenInput.value || '';
	
			// Zoek knop met die waarde
			let activeBtn = Array.from(buttons).find(btn => btn.dataset.value === currentValue);
	
			// Als niks gevonden, neem de 'alle'-knop (lege value)
			if (!activeBtn) {
				activeBtn = Array.from(buttons).find(btn => btn.dataset.value === '');
				if (activeBtn) {
					hiddenInput.value = ''; // reset de waarde expliciet
				}
			}
	
			if (activeBtn) {
				activeBtn.classList.add('active');
			}
	
			// Event listeners
			buttons.forEach(btn => {
				btn.addEventListener('click', () => {
					buttons.forEach(b => b.classList.remove('active'));
					btn.classList.add('active');
					hiddenInput.value = btn.dataset.value;
					filterForm.dispatchEvent(new Event('change', { bubbles: true }));
				});
			});
                });
        };

        // Flatpickr initialiseren op datumvelden
        // Gebruik data-date-picker voor een enkel veld of
        // data-date-range-start="key" en data-date-range-end="key" voor een van/tot range
        const initDatePickers = () => {
               document.querySelectorAll('[data-date-picker]').forEach(el => {
                       if (el._flatpickr) return;
                       const format = el.dataset.dateFormat || 'd-m-Y';
                       flatpickr(el, {
                               dateFormat: format,
                               onChange: () => el.dispatchEvent(new Event('change', { bubbles: true }))
                       });
               });

               document.querySelectorAll('[data-date-range-start]').forEach(startEl => {
                       if (startEl._flatpickr) return;
                       const key = startEl.dataset.dateRangeStart;
                       const endEl = document.querySelector(`[data-date-range-end="${key}"]`);
                       const format = startEl.dataset.dateFormat || 'd-m-Y';
                       flatpickr(startEl, {
                               dateFormat: format,
                               plugins: endEl ? [new rangePlugin({ input: endEl })] : [],
                               onChange: () => {
                                       startEl.dispatchEvent(new Event('change', { bubbles: true }));
                                       if (endEl) endEl.dispatchEvent(new Event('change', { bubbles: true }));
                               }
                       });
               });
       };

        const initSliders = () => {
                document.querySelectorAll('[data-slider]').forEach(sliderEl => {
                        if (sliderEl.classList.contains('noUi-target')) return;

			const min = parseFloat(sliderEl.dataset.min);
			const max = parseFloat(sliderEl.dataset.max);
			const parent = sliderEl.closest('.range-wrapper') || sliderEl.parentElement;

			if (!parent) return;

			const inputMin = parent.querySelector(`input[name^="min_"]`);
			const inputMax = parent.querySelector(`input[name^="max_"]`);

			const startMin = parseFloat(inputMin?.value || min);
			const startMax = parseFloat(inputMax?.value || max);

			noUiSlider.create(sliderEl, {
				start: [startMin, startMax],
				connect: true,
				range: { min, max },
				step: 1,
				tooltips: false,
				format: {
					to: value => Math.round(value),
					from: value => parseFloat(value)
				}
			});

			sliderEl.noUiSlider.on('update', (values) => {
				if (inputMin) inputMin.value = Math.round(values[0]);
				if (inputMax) inputMax.value = Math.round(values[1]);
			});

			sliderEl.noUiSlider.on('change', () => {
				filterForm.dispatchEvent(new Event('change', { bubbles: true }));
			});

			[inputMin, inputMax].forEach(input => {
				input.addEventListener('change', () => {
					const newMin = parseFloat(inputMin.value) || min;
					const newMax = parseFloat(inputMax.value) || max;
					sliderEl.noUiSlider.set([newMin, newMax]);
				});
			});
		});
	};

	const fetchFilteredResults = (append = false) => {
		const data = serializeForm(filterForm);
		data.append('paged', currentPage);
	
		toggleLoader(true);
	
		fetch(ajaxUrl, {
			method: 'POST',
			body: data
		})
			.then(res => res.text())
			.then(html => {
				toggleLoader(false);
	
				if (append) {
					resultContainer.insertAdjacentHTML('beforeend', html);
				} else {
					resultContainer.innerHTML = html;
					animateItems();
				}
	
                                initSliders();
                                initDatePickers();
                                initOptionToggles();
                                initFilterButtons();
                                swiperInit();
	
				const el = document.createElement('div');
				el.innerHTML = html;
	
				// ⛳️ Aantal pagina's bepalen voor "Laad meer"
				const maxPagesEl = el.querySelector('[data-max-pages]');
				maxPages = maxPagesEl ? parseInt(maxPagesEl.dataset.maxPages || 1, 10) : 1;
				if (loadMoreBtn) {
					loadMoreBtn.classList.toggle('d-none', currentPage >= maxPages);
				}
	
				// ✅ Externe result count bijwerken zonder te crashen
                                const ajaxResultCount = el.querySelector('#result-count');
                                const externalResultCount = document.querySelector('[data-result-count]');
                                if (ajaxResultCount && externalResultCount) {
                                        externalResultCount.innerHTML = ajaxResultCount.innerHTML;
                                }

                                // 🔢 Update option counts for filters
                               const optionCountsEl = el.querySelector('[data-option-counts]');
                               if (optionCountsEl) {
                                       let counts = {};
                                       try {
                                               counts = JSON.parse(optionCountsEl.dataset.optionCounts || '{}');
                                       } catch {
                                               counts = {};
                                       }
                                       const targets = {};
                                       document.querySelectorAll('[data-option-count]').forEach((el) => {
                                               const key = el.dataset.optionCount;
                                               targets[key] = el;
                                               el.textContent = '0';
                                       });

                                       Object.entries(counts).forEach(([filterName, values]) => {
                                               Object.entries(values).forEach(([val, count]) => {
                                                       const key = `${filterName}:${val}`;
                                                       if (targets[key]) {
                                                               targets[key].textContent = count;
                                                       }
                                               });
                                       });
                               }
                       });
       };

	filterForm.addEventListener('change', debounce(() => {
		currentPage = 1;
		if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
		fetchFilteredResults(false);
	}, 300));

	const searchInput = filterForm.querySelector('input[name="s"]');
	if (searchInput) {
		searchInput.addEventListener('input', debounce(() => {
			currentPage = 1;
			if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
			fetchFilteredResults(false);
		}, 400));

		searchInput.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') e.preventDefault();
		});
	}

	if (loadMoreBtn) {
		loadMoreBtn.addEventListener('click', () => {
			currentPage++;
			fetchFilteredResults(true);
		});
	}

	const resetBtn = filterForm.querySelector('[data-filter-reset]');
	if (resetBtn) {
		resetBtn.addEventListener('click', () => {
			currentPage = 1;
			if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
		
                        filterForm.reset();

                        // Reset expliciet de hidden inputs van button-filters
                        document.querySelectorAll('.filter-buttons').forEach(group => {
				const hiddenInput = group.nextElementSibling;
				if (hiddenInput && hiddenInput.type === 'hidden') {
					hiddenInput.value = ''; // leegmaken = 'Alles'
				}
			});
			
			// Init opnieuw zodat juiste knop actief is
			initFilterButtons();
		
			// Reset sliders visueel én de bijbehorende inputvelden
                        document.querySelectorAll('[data-slider]').forEach(sliderEl => {
				const min = parseFloat(sliderEl.dataset.min);
				const max = parseFloat(sliderEl.dataset.max);
		
				const parent = sliderEl.closest('.range-wrapper') || sliderEl.parentElement;
				const inputMin = parent.querySelector(`input[name^="min_"]`);
				const inputMax = parent.querySelector(`input[name^="max_"]`);
		
				if (inputMin) inputMin.value = min;
				if (inputMax) inputMax.value = max;
		
				if (sliderEl.noUiSlider) {
					sliderEl.noUiSlider.set([min, max]);
				}
                        });

                        // Reset date pickers naar oorspronkelijke waardes
                        filterForm.querySelectorAll('[data-date-range-start]').forEach(startEl => {
                                const key = startEl.dataset.dateRangeStart;
                                const endEl = filterForm.querySelector(`[data-date-range-end="${key}"]`);
                                if (startEl._flatpickr) {
                                        const dates = [];
                                        if (startEl.value) dates.push(startEl.value);
                                        if (endEl && endEl.value) dates.push(endEl.value);
                                        if (dates.length) {
                                                startEl._flatpickr.setDate(dates, false);
                                        } else {
                                                startEl._flatpickr.clear();
                                        }
                                }
                        });
                        filterForm.querySelectorAll('[data-date-picker]').forEach(el => {
                                if (el.dataset.dateRangeStart || el.dataset.dateRangeEnd) return;
                                if (el._flatpickr) {
                                        el._flatpickr.setDate(el.value || null, false);
                                }
                        });

                        // Reset zoekveld expliciet (om debounce goed te triggeren)
                        if (searchInput) searchInput.value = '';
		
			// Resultaten verversen
			fetchFilteredResults(false);
		});
	}

        initSliders();
        initDatePickers();
        initOptionToggles();
        initFilterButtons();
}