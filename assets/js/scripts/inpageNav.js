export function inpageNav() {
	const slugify = (text) => {
		return text
			.toLowerCase()
			.trim()
			.replace(/[^a-z0-9\s-]/g, '')
			.replace(/\s+/g, '-')
			.replace(/-+/g, '-');
	};

	const ensureUniqueId = (base, usedIds) => {
		let candidate = base;
		let counter = 2;

		while (usedIds.has(candidate)) {
			candidate = `${base}-${counter}`;
			counter += 1;
		}

		usedIds.add(candidate);
		return candidate;
	};

	const sections = document.querySelectorAll('[data-inpage-nav-section]');

	sections.forEach((section) => {
		const content = section.querySelector('[data-inpage-nav-content]');
		const list = section.querySelector('[data-inpage-nav-list]');
		const nav = section.querySelector('[data-inpage-nav]');
		const select = section.querySelector('[data-inpage-nav-select]');
		const mobile = section.querySelector('[data-inpage-nav-mobile]');

		if (!content || !list || !nav || !select || !mobile) {
			return;
		}

		const headings = Array.from(content.querySelectorAll('h2')).filter((heading) =>
			heading.textContent.trim()
		);

		if (!headings.length) {
			nav.style.display = 'none';
			mobile.style.display = 'none';
			return;
		}

		const usedIds = new Set();

		headings.forEach((heading) => {
			const text = heading.textContent.trim();

			if (!heading.id) {
				const slug = ensureUniqueId(slugify(text), usedIds);
				heading.id = slug;
			} else {
				usedIds.add(heading.id);
			}

			const item = document.createElement('li');
			const link = document.createElement('a');
			link.href = `#${heading.id}`;
			link.textContent = text;
			item.appendChild(link);
			list.appendChild(item);

			const option = document.createElement('option');
			option.value = heading.id;
			option.textContent = text;
			select.appendChild(option);
		});

		const links = Array.from(list.querySelectorAll('a'));

		const setActive = (id) => {
			links.forEach((link) => {
				link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`);
			});

			if (select.value !== id) {
				select.value = id;
			}
		};

		if (headings[0]?.id) {
			setActive(headings[0].id);
		}

		list.addEventListener('click', (event) => {
			const target = event.target.closest('a');
			if (!target) return;

			event.preventDefault();
			const id = target.getAttribute('href').replace('#', '');
			const heading = document.getElementById(id);

			if (heading) {
				heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});

		select.addEventListener('change', () => {
			const heading = document.getElementById(select.value);

			if (heading) {
				heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});

		if (!('IntersectionObserver' in window)) {
			mobile.classList.add('is-visible');
			return;
		}

		const headingObserver = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						setActive(entry.target.id);
					}
				});
			},
			{
				rootMargin: '-35% 0px -55% 0px',
				threshold: 0
			}
		);

		headings.forEach((heading) => headingObserver.observe(heading));

		const sectionObserver = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					mobile.classList.toggle('is-visible', entry.isIntersecting);
				});
			},
			{
				rootMargin: '-10% 0px -10% 0px',
				threshold: 0
			}
		);

		sectionObserver.observe(section);
	});
}