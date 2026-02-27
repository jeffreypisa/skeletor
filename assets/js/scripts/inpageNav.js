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

	const parseJsonAttr = (value, fallback) => {
		if (!value) return fallback;

		try {
			const parsed = JSON.parse(value);
			return parsed ?? fallback;
		} catch {
			return fallback;
		}
	};

	const normalizeUrl = (url) => {
		if (!url) return '';

		try {
			const parsed = new URL(url, window.location.origin);
			const cleanPath = parsed.pathname.replace(/\/$/, '');
			return `${parsed.origin}${cleanPath}`;
		} catch {
			return '';
		}
	};

	const sections = document.querySelectorAll('[data-inpage-nav-section]');
	const root = document.documentElement;

	sections.forEach((section) => {
		const sectionUid =
			section.dataset.inpageNavUid ||
			`inpage-nav-${Math.random().toString(36).slice(2, 10)}`;
		section.dataset.inpageNavUid = sectionUid;

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

		const parentLinks = parseJsonAttr(
			nav.dataset.inpageNavParentLinks || mobile.dataset.inpageNavParentLinks,
			[]
		).filter((item) => item && typeof item === 'object' && item.title && item.url);

		const currentParentId =
			nav.dataset.inpageNavCurrentParentId ||
			mobile.dataset.inpageNavCurrentParentId ||
			'';
		const parentIconType =
			nav.dataset.inpageNavParentIcon || mobile.dataset.inpageNavParentIcon || '';
		const parentIconUpMarkup = nav.dataset.inpageNavIconUp || '';
		const parentIconDownMarkup = nav.dataset.inpageNavIconDown || '';

		const hasParentLinks = parentLinks.length > 0;
		const desktopBreakpointCss = getComputedStyle(root)
			.getPropertyValue('--bs-breakpoint-lg')
			.trim();
		const desktopBreakpoint = parseInt(desktopBreakpointCss, 10);
		const desktopBreakpointValue = Number.isNaN(desktopBreakpoint)
			? 992
			: desktopBreakpoint;
		const isDesktopViewport = window.innerWidth >= desktopBreakpointValue;
		const isDesktop = () => window.innerWidth >= desktopBreakpointValue;

		if (!headings.length && !hasParentLinks) {
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
		});

		const headingLinkRefs = [];
		const headingOptionRefs = [];
		const parentToggleRefs = [];

		const setParentExpanded = (parentItem, expanded) => {
			const subList = parentItem.querySelector('.inpage-nav-sublist');
			const toggleLink = parentItem.querySelector('a[data-nav-type="parent-toggle"]');
			const icon = parentItem.querySelector('.inpage-nav-parent-icon');

			if (!subList || !toggleLink) return;

			parentItem.classList.toggle('is-collapsed', !expanded);
			subList.hidden = !expanded;
			toggleLink.setAttribute('aria-expanded', expanded ? 'true' : 'false');

			if (icon) {
				icon.innerHTML = expanded ? parentIconUpMarkup : parentIconDownMarkup;
			}
		};

		if (hasParentLinks) {
			const currentPageUrl = normalizeUrl(window.location.href);
			const currentParentIndex = parentLinks.findIndex((item) => {
				const itemId = item.id !== undefined ? String(item.id) : '';
				return (
					item.is_current === true ||
					(currentParentId && itemId && itemId === String(currentParentId)) ||
					normalizeUrl(item.url) === currentPageUrl
				);
			});

			parentLinks.forEach((item, index) => {
				const parentLi = document.createElement('li');
				parentLi.classList.add('inpage-nav-parent-item');

				const parentLink = document.createElement('a');
				parentLink.href = item.url;
				parentLink.textContent = item.title;
				parentLink.dataset.navType = 'url';
				parentLi.appendChild(parentLink);

				const parentOption = document.createElement('option');
				parentOption.value = item.url;
				parentOption.textContent = item.title;
				parentOption.dataset.navType = 'url';
				select.appendChild(parentOption);

				const isCurrentParent = index === currentParentIndex;
				const hasParentIcon =
					isDesktopViewport &&
					['arrow', 'chevron'].includes(parentIconType) &&
					parentIconUpMarkup &&
					parentIconDownMarkup;

				if (hasParentIcon) {
					const icon = document.createElement('span');
					icon.classList.add(
						'inpage-nav-parent-icon',
						`inpage-nav-parent-icon--${parentIconType}`
					);
					icon.innerHTML = isCurrentParent ? parentIconUpMarkup : parentIconDownMarkup;
					icon.setAttribute('aria-hidden', 'true');
					parentLink.appendChild(icon);
				}

				if (isCurrentParent) {
					parentLi.classList.add('is-current');
					parentLink.setAttribute('aria-current', 'page');

					if (headings.length) {
						const subList = document.createElement('ul');
						subList.classList.add('inpage-nav-sublist');
						subList.id = `inpage-nav-sublist-${sectionUid}-${index}`;
						parentLink.dataset.navType = 'parent-toggle';
						parentLink.setAttribute('aria-expanded', 'true');
						parentLink.setAttribute('aria-controls', subList.id);
						parentToggleRefs.push(parentLi);

						headings.forEach((heading) => {
							const subItem = document.createElement('li');
							const subLink = document.createElement('a');
							subLink.href = `#${heading.id}`;
							subLink.textContent = heading.textContent.trim();
							subLink.dataset.navType = 'anchor';
							subItem.appendChild(subLink);
							subList.appendChild(subItem);
							headingLinkRefs.push(subLink);

							const subOption = document.createElement('option');
							subOption.value = heading.id;
							subOption.textContent = `- ${heading.textContent.trim()}`;
							subOption.dataset.navType = 'anchor';
							select.appendChild(subOption);
							headingOptionRefs.push(subOption);
						});

						parentLi.appendChild(subList);
					}
				}

				list.appendChild(parentLi);
			});
		} else {
			headings.forEach((heading) => {
				const text = heading.textContent.trim();

				const item = document.createElement('li');
				const link = document.createElement('a');
				link.href = `#${heading.id}`;
				link.textContent = text;
				link.dataset.navType = 'anchor';
				item.appendChild(link);
				list.appendChild(item);
				headingLinkRefs.push(link);

				const option = document.createElement('option');
				option.value = heading.id;
				option.textContent = text;
				option.dataset.navType = 'anchor';
				select.appendChild(option);
				headingOptionRefs.push(option);
			});
		}

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
			headingLinkRefs.forEach((link) => {
				link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`);
			});

			const optionMatch = headingOptionRefs.find((option) => option.value === id);
			if (optionMatch && select.value !== id) {
				select.value = id;
			}
		};

		const updateActiveHeading = () => {
			if (!headings.length || !headingLinkRefs.length) return;
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

		const smoothScrollToHeading = (id) => {
			const heading = document.getElementById(id);
			if (!heading) return;

			const currentTop = window.pageYOffset;
			const headingTop = heading.getBoundingClientRect().top + currentTop;
			const offset = getDirectionalOffset(headingTop, currentTop) + EXTRA_H2_SPACE;
			const targetTop = headingTop - offset;

			root.style.setProperty('--inpage-anchor-offset', `${offset}px`);
			setActive(id);
			window.scrollTo({
				top: Math.max(0, targetTop),
				behavior: 'smooth'
			});
		};

		list.addEventListener('click', (event) => {
			const target = event.target.closest('a');
			if (!target) return;

			if (target.dataset.navType === 'parent-toggle') {
				event.preventDefault();
				const parentItem = target.closest('.inpage-nav-parent-item');
				if (!parentItem) return;
				const isExpanded = target.getAttribute('aria-expanded') === 'true';
				setParentExpanded(parentItem, !isExpanded);
				return;
			}

			if (target.dataset.navType !== 'anchor') {
				return;
			}

			event.preventDefault();
			const id = target.getAttribute('href').replace('#', '');
			smoothScrollToHeading(id);
		});

		parentToggleRefs.forEach((parentItem) => setParentExpanded(parentItem, true));

		select.addEventListener('change', () => {
			const option = select.options[select.selectedIndex];
			if (!option) return;

			if (option.dataset.navType === 'url') {
				window.location.href = option.value;
				return;
			}

			smoothScrollToHeading(option.value);
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
