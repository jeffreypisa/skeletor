export function filter() {
	const filterForm = document.querySelector('[data-filter-form]');
	const resultContainer = document.querySelector('#filter-results');

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
			} else if (el.tagName === 'SELECT' && el.multiple) {
				if (!grouped[name]) grouped[name] = [];
				Array.from(el.selectedOptions).forEach((opt) => {
					grouped[name].push(opt.value);
				});
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

	const ajaxUrl =
		(typeof window.ajaxurl !== 'undefined' && window.ajaxurl.url)
			? window.ajaxurl.url
			: '/wp-admin/admin-ajax.php';

	let currentPage = 1;
	let maxPages = null;

	const loadMoreBtn = document.querySelector('[data-load-more]');

	const fetchFilteredResults = (append = false) => {
		const data = serializeForm(filterForm);
		data.append('paged', currentPage);

		fetch(ajaxUrl, {
			method: 'POST',
			body: data
		})
			.then((res) => res.text())
			.then((html) => {
				if (append) {
					resultContainer.insertAdjacentHTML('beforeend', html);
				} else {
					resultContainer.innerHTML = html;
				}

				// 🔧 maxPages ophalen vanuit nieuwe HTML
				const el = document.createElement('div');
				el.innerHTML = html;
				const maxPagesEl = el.querySelector('[data-max-pages]');
				maxPages = maxPagesEl ? parseInt(maxPagesEl.dataset.maxPages || 1, 10) : 1;

				// 🔧 verberg de knop als laatste pagina is bereikt
				if (currentPage >= maxPages && loadMoreBtn) {
					loadMoreBtn.classList.add('d-none');
				}
			});
	};

	// 🔁 Bij wijziging in form: reset en herlaad
	filterForm.addEventListener(
		'change',
		debounce(() => {
			currentPage = 1;
			if (loadMoreBtn) loadMoreBtn.classList.remove('d-none');
			fetchFilteredResults(false);
		}, 300)
	);
	
	// 🔁 Bij typen in zoekveld: debounce + fetch
	const searchInput = filterForm.querySelector('input[name="s"]');
	if (searchInput) {
		searchInput.addEventListener(
			'input',
			debounce(() => {
				currentPage = 1;
				if (loadMoreBtn) loadMoreBtn.classList.remove('d-none');
				fetchFilteredResults(false);
			}, 400) // iets langere delay om te voorkomen dat elke letter een call doet
		);
		searchInput.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault(); // voorkomt page reload
			}
		});
	}
	
	// 🔁 Load more knop
	if (loadMoreBtn) {
		loadMoreBtn.addEventListener('click', () => {
			currentPage++;
			fetchFilteredResults(true); // append=true
		});
	}
}