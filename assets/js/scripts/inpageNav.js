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
	const root = document.documentElement;

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

		const parseCssNumber = (value) => {
			const parsed = parseFloat(value);
			return Number.isNaN(parsed) ? 0 : parsed;
		};

		const getHeaderHeight = () =>
			parseCssNumber(getComputedStyle(root).getPropertyValue('--header-height'));

		const getStickyOffset = () =>
			parseCssNumber(
				getComputedStyle(root).getPropertyValue('--header-sticky-offset')
			);
			
		const getBreakpointWidth = () => {
			const cssValue = getComputedStyle(root)
				.getPropertyValue('--bs-breakpoint-lg')
				.trim();
			const numericValue = parseInt(cssValue, 10);
		
			return Number.isNaN(numericValue) ? 992 : numericValue;
		};
		
		const isDesktop = () => window.innerWidth >= getBreakpointWidth();

		// ✅ Altijd extra ruimte boven de H2 na scrollen
		const EXTRA_H2_SPACE = 30;

		const getDirectionalOffset = (targetTop, currentTop) => {
			if (targetTop < currentTop) {
				const stickyOffset = getStickyOffset();
				if (isDesktop()) {
					return Math.max(stickyOffset, getHeaderHeight());
				}
				
				return stickyOffset;
			}

			return 0;
		};

		const updateAnchorOffset = () => {
			const offset = getStickyOffset() + EXTRA_H2_SPACE;
			root.style.setProperty('--inpage-anchor-offset', `${offset}px`);
			return offset;
		};

		const setActive = (id) => {
			links.forEach((link) => {
				link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`);
			});

			if (select.value !== id) {
				select.value = id;
			}
		};

		const updateActiveHeading = () => {
			if (!headings.length) return;
			const offset = updateAnchorOffset();
			let activeId = headings[0].id;

			headings.forEach((heading) => {
				const top = heading.getBoundingClientRect().top;
				if (top - offset <= 1) {
					activeId = heading.id;
				}
			});

			setActive(activeId);
		};

		updateActiveHeading();

		list.addEventListener('click', (event) => {
			const target = event.target.closest('a');
			if (!target) return;

			event.preventDefault();
			const id = target.getAttribute('href').replace('#', '');
			const heading = document.getElementById(id);

			if (heading) {
				const currentTop = window.pageYOffset;
				const headingTop = heading.getBoundingClientRect().top + currentTop;

				// ✅ directionele offset + altijd 30px extra ruimte
				const offset = getDirectionalOffset(headingTop, currentTop) + EXTRA_H2_SPACE;
				const targetTop = headingTop - offset;

				root.style.setProperty('--inpage-anchor-offset', `${offset}px`);
				setActive(id);
				window.scrollTo({
					top: Math.max(0, targetTop),
					behavior: 'smooth'
				});
			}
		});

		select.addEventListener('change', () => {
			const heading = document.getElementById(select.value);

			if (heading) {
				const currentTop = window.pageYOffset;
				const headingTop = heading.getBoundingClientRect().top + currentTop;

				// ✅ directionele offset + altijd 30px extra ruimte
				const offset = getDirectionalOffset(headingTop, currentTop) + EXTRA_H2_SPACE;
				const targetTop = headingTop - offset;

				root.style.setProperty('--inpage-anchor-offset', `${offset}px`);
				setActive(heading.id);
				window.scrollTo({
					top: Math.max(0, targetTop),
					behavior: 'smooth'
				});
			}
		});

		let scrollTicking = false;
		const handleScroll = () => {
			if (scrollTicking) return;
			scrollTicking = true;
			requestAnimationFrame(() => {
				updateActiveHeading();
				scrollTicking = false;
			});
		};

		window.addEventListener('scroll', handleScroll, { passive: true });
		window.addEventListener('resize', () => {
			updateActiveHeading();
		});

		if (!('IntersectionObserver' in window)) {
			mobile.classList.add('is-visible');
			return;
		}

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