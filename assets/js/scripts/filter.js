import noUiSlider from 'nouislider';
import flatpickr from 'flatpickr/dist/flatpickr.js';
import rangePlugin from 'flatpickr/dist/plugins/rangePlugin.js';
import { swiperInit } from '../plugins/swiperInit.js';

export function filter() {
        const filterForm = document.querySelector('[data-filter-form]');
        const resultContainer = document.querySelector('#filter-results');
        const loadMoreBtn = document.querySelector('[data-load-more]');

        if (!filterForm || !resultContainer) return;

        const wcOrderSelect = filterForm.querySelector('select[name="orderby"]');
        const searchInput = filterForm.querySelector('input[name="s"]');
        const requireSearch = filterForm.hasAttribute('data-require-search');

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

                        const rawName = el.name;
                        const isArray = rawName.endsWith('[]');
                        const name = rawName.replace(/\[\]$/, '');

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
                        } else if (isArray) {
                                if (!grouped[name]) grouped[name] = [];
                                grouped[name].push(el.value);
                        } else {
                                grouped[name] = el.value;
                        }
                });

                if (!('post_type' in grouped)) {
                        grouped['post_type'] = form.dataset.postType || 'post';
                }

for (const key in grouped) {
const value = grouped[key];
if (Array.isArray(value)) {
value.forEach(v => data.append(`${key}[]`, v));
} else {
data.append(key, value);
}
}

data.append('action', 'ajax_filter');
if (window.ajaxurl && window.ajaxurl.nonce) {
data.append('nonce', window.ajaxurl.nonce);
}
return data;
};

	const ajaxUrl = (typeof window.ajaxurl !== 'undefined' && window.ajaxurl.url) ?
		window.ajaxurl.url :
		'/wp-admin/admin-ajax.php';

	let currentPage = 1;
	let maxPages = null;

        const toggleLoader = (show) => {
                const results = document.querySelector('#filter-results');
                const loader = document.querySelector('[data-filter-loader]');
                if (loader) loader.classList.toggle('d-none', !show);
                if (results) results.classList.toggle('loading', show);
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

        const initHierarchyFilters = () => {
                requestAnimationFrame(() => {
                        const setExpanded = (btn, expanded) => {
                                const option = btn.closest('[data-filter-option]');
                                const children = option ? option.querySelector(':scope > [data-filter-branch-children]') : null;
                                const icon = btn.querySelector('[data-branch-icon]');

                                if (children) {
                                        children.hidden = !expanded;
                                }

                                btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                                btn.classList.toggle('is-expanded', expanded);

                                if (icon) {
                                        icon.textContent = expanded ? '▾' : '▸';
                                }
                        };

                        const initChildLimits = (container) => {
                                const limit = parseInt(container.dataset.limitChildren || 0, 10);
                                if (!limit) return;

                                const options = container.querySelectorAll(':scope > [data-filter-option]');
                                const toggleBtn = container.querySelector(':scope > [data-children-toggle]');
                                const expandLabel = container.dataset.childExpandLabel || 'Toon meer';
                                const collapseLabel = container.dataset.childCollapseLabel || 'Toon minder';

                                if (!toggleBtn || options.length <= limit) return;

                                const setState = (expanded) => {
                                        container.dataset.childrenExpanded = expanded ? 'true' : 'false';
                                        options.forEach((opt, index) => {
                                                opt.style.display = (expanded || index < limit) ? '' : 'none';
                                        });
                                        toggleBtn.textContent = expanded ? collapseLabel : expandLabel;
                                        toggleBtn.setAttribute('aria-label', expanded ? collapseLabel : expandLabel);
                                        toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                                };

                                setState(container.dataset.childrenExpanded === 'true');
                                toggleBtn.classList.remove('d-none');

                                if (!toggleBtn.dataset.initialized) {
                                        toggleBtn.addEventListener('click', () => {
                                                const expanded = container.dataset.childrenExpanded === 'true';
                                                setState(!expanded);
                                        });
                                        toggleBtn.dataset.initialized = 'true';
                                }
                        };

                        document.querySelectorAll('[data-filter-branch-toggle]').forEach(btn => {
                                if (btn.dataset.initialized) return;

                                const defaultExpanded = btn.dataset.defaultExpanded === 'true';
                                setExpanded(btn, defaultExpanded);

                                btn.addEventListener('click', () => {
                                        const expanded = btn.getAttribute('aria-expanded') === 'true';
                                        setExpanded(btn, !expanded);
                                });

                                btn.dataset.initialized = 'true';
                        });

                        document.querySelectorAll('[data-hierarchical-options]').forEach(wrapper => {
                                const expandAllBtn = wrapper.querySelector('[data-expand-all-branches]');
                                const collapseAllBtn = wrapper.querySelector('[data-collapse-all-branches]');

                                const setAll = (expanded) => {
                                        wrapper.querySelectorAll('[data-filter-branch-toggle]').forEach(btn => setExpanded(btn, expanded));
                                };

                                const expandCheckedParents = () => {
                                        wrapper.querySelectorAll('input:checked').forEach(input => {
                                                let option = input.closest('[data-filter-option]');
                                                while (option) {
                                                        const toggle = option.querySelector(':scope > .d-flex [data-filter-branch-toggle]');
                                                        if (toggle) {
                                                                setExpanded(toggle, true);
                                                        }
                                                        option = option.parentElement?.closest('[data-filter-option]') || null;
                                                }
                                        });
                                };

                                expandCheckedParents();

                                wrapper.querySelectorAll('[data-filter-branch-children]').forEach(initChildLimits);

                                if (expandAllBtn && !expandAllBtn.dataset.initialized) {
                                        expandAllBtn.addEventListener('click', () => setAll(true));
                                        expandAllBtn.dataset.initialized = 'true';
                                }

                                if (collapseAllBtn && !collapseAllBtn.dataset.initialized) {
                                        collapseAllBtn.addEventListener('click', () => setAll(false));
                                        collapseAllBtn.dataset.initialized = 'true';
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

                if (requireSearch && searchInput && searchInput.value.trim() === '') {

                        if (!append) {
                                resultContainer.innerHTML = '<p class="text-muted">Voer een zoekterm in om resultaten te zien.</p>';
                                if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
                        }
                        return;
                }

                const data = serializeForm(filterForm);
                const colClassField = filterForm.querySelector('input[name="col_class"]');
                if (colClassField) {
                        data.set('col_class', colClassField.value);
                }
                const teaseTemplateField = filterForm.querySelector('input[name="tease_template"]');
                if (teaseTemplateField) {
                        data.set('tease_template', teaseTemplateField.value);
                }
                if (wcOrderSelect) {
                        const selected = wcOrderSelect.options[wcOrderSelect.selectedIndex];
                        if (selected && selected.dataset.order) {
                                data.set('order', selected.dataset.order);
                        } else {
                                data.delete('order');
                        }
                }
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
                                initHierarchyFilters();
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
                                        if (Object.keys(counts).length) {
                                                document.querySelectorAll('[data-option-count]').forEach(target => {
                                                        const [filterName, val] = target.dataset.optionCount.split(':');
                                                        const count = counts?.[filterName]?.[val] ?? 0;
                                                        target.textContent = count;
                                                });
                                        }
                                }
                        });
        };

	filterForm.addEventListener('change', debounce(() => {
		currentPage = 1;
		if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
		fetchFilteredResults(false);
	}, 300));

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

                        if (wcOrderSelect) {
                                wcOrderSelect.selectedIndex = 0;
                        }

                        // Resultaten verversen
                        fetchFilteredResults(false);
                });
        }

        initSliders();
        initDatePickers();
        initOptionToggles();
        initHierarchyFilters();
        initFilterButtons();
}