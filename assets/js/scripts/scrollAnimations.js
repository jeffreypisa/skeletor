// JavaScript
export function scrollAnimations() {
	const observerOptions = {
		root: null,
		rootMargin: '0px',
		threshold: 0.2
	};

	const observer = new IntersectionObserver((entries, observer) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				if (entry.target.classList.contains('animate-typewriter')) {
					setFixedHeight(entry.target);
					entry.target.classList.add('in-view');
					typewriterEffect(entry.target, () => releaseFixedHeight(entry.target));
				} else if (entry.target.classList.contains('animate-word-rise')) {
					wordRiseEffect(entry.target);
					addInViewWithDelay(entry.target);
					schedulePostAnimationCleanup(entry.target, 'word-rise');
				} else if (entry.target.classList.contains('animate-char-fade-soft')) {
					charFadeSoftEffect(entry.target);
					addInViewWithDelay(entry.target);
					schedulePostAnimationCleanup(entry.target, 'char-fade-soft');
				} else if (entry.target.classList.contains('animate-line-reveal')) {
					lineAnimationEffect(entry.target, 'line-reveal');
					addInViewWithDelay(entry.target);
					schedulePostAnimationCleanup(entry.target, 'line-reveal');
				} else if (entry.target.classList.contains('animate-stagger-lines')) {
					lineAnimationEffect(entry.target, 'stagger-lines');
					addInViewWithDelay(entry.target);
					schedulePostAnimationCleanup(entry.target, 'stagger-lines');
				} else if (entry.target.classList.contains('animate-underline-draw')) {
					underlineDrawEffect(entry.target);
					addInViewWithDelay(entry.target);
					schedulePostAnimationCleanup(entry.target, 'underline-draw');
				} else {
					entry.target.classList.add('in-view');
				}
				observer.unobserve(entry.target);
			}
		});
	}, observerOptions);

	const animatedElements = document.querySelectorAll('[class*="animate-"]');
	animatedElements.forEach(el => observer.observe(el));

	attachResizeCleanupListener(animatedElements);
}

function setFixedHeight(element) {
	const rectHeight = element.getBoundingClientRect().height;
	const contentHeight = element.scrollHeight;
	const height = Math.max(rectHeight, contentHeight);

	if (height > 0) {
		element.style.minHeight = `${Math.ceil(height)}px`;
	}
}

function releaseFixedHeight(element) {
	element.style.minHeight = '';
}

function schedulePostAnimationCleanup(element, animationType = '') {
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const baseDelay = animationType ? 1400 : 1200;
	const minDelay = Math.max(120, Math.round(baseDelay / speedMultiplier));
	const animationDelay = getPostAnimationDelay(element, animationType);
	const delay = Math.max(minDelay, animationDelay);
	window.setTimeout(() => {
		if (
			animationType === 'line-reveal' ||
			animationType === 'stagger-lines' ||
			animationType === 'word-rise' ||
			animationType === 'char-fade-soft'
		) {
			restoreLineAnimationFlow(element);
		}
		releaseFixedHeight(element);
	}, delay);
}

function getPostAnimationDelay(element, animationType = '') {
	const animationTargets = {
		'word-rise': '.word-rise-word',
		'char-fade-soft': '.char-fade-soft-char',
		'line-reveal': '.line-reveal-line-inner',
		'stagger-lines': '.stagger-lines-line-inner'
	};

	const selector = animationTargets[animationType];
	if (!selector) {
		return 0;
	}

	const items = element.querySelectorAll(selector);
	if (!items.length) {
		return 0;
	}

	let maxDelay = 0;
	items.forEach((item) => {
		const inlineDelay = parseTimeToMs(item.style.transitionDelay || '0ms');
		if (inlineDelay > maxDelay) {
			maxDelay = inlineDelay;
		}
	});

	const style = window.getComputedStyle(items[0]);
	const duration = parseTimeListToMaxMs(style.transitionDuration);
	const delay = parseTimeListToMaxMs(style.transitionDelay);
	const settleBuffer = 140;

	return Math.round(maxDelay + duration + delay + settleBuffer);
}

function parseTimeToMs(value = '0ms') {
	const raw = `${value}`.trim();
	if (!raw) return 0;
	if (raw.endsWith('ms')) return Number.parseFloat(raw) || 0;
	if (raw.endsWith('s')) return (Number.parseFloat(raw) || 0) * 1000;
	return Number.parseFloat(raw) || 0;
}

function parseTimeListToMaxMs(value = '') {
	const list = `${value}`.split(',');
	let max = 0;

	list.forEach((entry) => {
		const parsed = parseTimeToMs(entry);
		if (parsed > max) {
			max = parsed;
		}
	});

	return max;
}

function attachResizeCleanupListener(animatedElements) {
	if (window.__scrollAnimationResizeCleanupAttached) return;
	window.__scrollAnimationResizeCleanupAttached = true;

	let resizeTimeout;
	window.addEventListener('resize', () => {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(() => {
			animatedElements.forEach((element) => {
				if (
					element.classList.contains('animate-word-rise') ||
					element.classList.contains('animate-char-fade-soft') ||
					element.classList.contains('animate-line-reveal') ||
					element.classList.contains('animate-stagger-lines') ||
					element.classList.contains('animate-typewriter')
				) {
					restoreLineAnimationFlow(element);
					releaseFixedHeight(element);
				}

				if (element.classList.contains('animate-underline-draw')) {
					restoreLineAnimationFlow(element);
					releaseFixedHeight(element);
					const target = getAnimationTarget(element, 'wordRiseText');
					if (target) {
						delete target.dataset.underlineDrawReady;
					}
					underlineDrawEffect(element);
				}
			});
		}, 120);
	});
}

function typewriterEffect(element, onComplete) {
	const text = element.getAttribute('data-text');
	if (!text) return;

	element.style.visibility = 'visible';
	element.textContent = '';
	let i = 0;
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const speed = Math.max(8, Math.round(50 / speedMultiplier));

	function type() {
		if (i < text.length) {
			element.textContent += text.charAt(i);
			i++;
			setTimeout(type, speed);
		} else if (typeof onComplete === 'function') {
			onComplete();
		}
	}

	type();
}

function addInViewWithDelay(element) {
	requestAnimationFrame(() => {
		requestAnimationFrame(() => {
			element.classList.add('in-view');
		});
	});
}

function getAnimationSpeedMultiplier(element) {
	const value = Number.parseFloat(element?.dataset?.inviewSpeed || '1');
	if (!Number.isFinite(value)) return 1;
	return Math.max(0.1, Math.min(10, value));
}

function getAnimationTarget(element, dataAttribute = '') {
	if (element.children.length === 1 && element.children[0].children.length === 0) {
		return element.children[0];
	}

	if (dataAttribute && element.dataset[dataAttribute] !== undefined) {
		return element;
	}

	if (element.children.length === 0) {
		return element;
	}

	if (element.children.length === 1 && element.children[0].children.length === 0) {
		return element.children[0];
	}

	return null;
}

function getAnimationText(target, dataAttribute = '') {
	const dataValue = dataAttribute ? target.dataset[dataAttribute] : '';
	const fallbackDataText = target.dataset.text || '';
	const rawText = dataValue || fallbackDataText || target.textContent || '';
	return rawText.replace(/\s+/g, ' ').trim();
}

function clearTarget(target) {
	while (target.firstChild) {
		target.removeChild(target.firstChild);
	}
}

function wordRiseEffect(element) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target || target.dataset.wordRiseReady === '1') return;

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;
	target.dataset.lineOriginalText = rawText;
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const delayStep = Math.max(6, Math.round(55 / speedMultiplier));

	writeSplitWords(target, rawText, 'word-rise-word', delayStep);
	target.dataset.wordRiseReady = '1';
}

function charFadeSoftEffect(element) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target || target.dataset.charFadeSoftReady === '1') return;

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;
	target.dataset.lineOriginalText = rawText;
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const delayStep = Math.max(2, Math.round(22 / speedMultiplier));

	clearTarget(target);

	const chars = Array.from(rawText);
	let visibleIndex = 0;

	chars.forEach((char) => {
		if (char === ' ') {
			target.appendChild(document.createTextNode(' '));
			return;
		}

		const span = document.createElement('span');
		span.className = 'char-fade-soft-char';
		span.style.transitionDelay = `${visibleIndex * delayStep}ms`;
		span.textContent = char;
		target.appendChild(span);
		visibleIndex++;
	});

	target.dataset.charFadeSoftReady = '1';
}

function lineAnimationEffect(element, animationType) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target) return;
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const delayStep = Math.max(8, Math.round(85 / speedMultiplier));

	const readyKey = animationType === 'line-reveal' ? 'lineRevealReady' : 'staggerLinesReady';
	if (target.dataset[readyKey] === '1') return;

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;
	target.dataset.lineOriginalText = rawText;

	const words = rawText.split(' ');
	if (words.length === 0) return;

	clearTarget(target);

	const tempWordSpans = [];
	words.forEach((word, index) => {
		const span = document.createElement('span');
		span.className = 'line-measure-word';
		span.textContent = word;
		target.appendChild(span);
		tempWordSpans.push(span);

		if (index < words.length - 1) {
			target.appendChild(document.createTextNode(' '));
		}
	});

	const groups = [];
	let currentGroup = [];
	let previousTop = null;

	tempWordSpans.forEach((span) => {
		const top = Math.round(span.offsetTop);
		if (previousTop === null || top === previousTop) {
			currentGroup.push(span.textContent);
		} else {
			groups.push(currentGroup);
			currentGroup = [span.textContent];
		}
		previousTop = top;
	});

	if (currentGroup.length > 0) {
		groups.push(currentGroup);
	}

	clearTarget(target);

	groups.forEach((groupWords, index) => {
		const line = document.createElement('span');
		line.className = `${animationType}-line`;

		const inner = document.createElement('span');
		inner.className = `${animationType}-line-inner`;
		inner.style.transitionDelay = `${index * delayStep}ms`;
		inner.textContent = groupWords.join(' ');

		line.appendChild(inner);
		target.appendChild(line);
	});

	target.dataset[readyKey] = '1';
}

function underlineDrawEffect(element) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target || target.dataset.underlineDrawReady === '1') return;
	const speedMultiplier = getAnimationSpeedMultiplier(element);
	const delayStep = Math.max(12, Math.round(180 / speedMultiplier));

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;
	target.dataset.lineOriginalText = rawText;

	const words = rawText.split(' ');
	if (words.length === 0) return;

	clearTarget(target);

	const tempWordSpans = [];
	words.forEach((word, index) => {
		const span = document.createElement('span');
		span.className = 'line-measure-word';
		span.textContent = word;
		target.appendChild(span);
		tempWordSpans.push(span);

		if (index < words.length - 1) {
			target.appendChild(document.createTextNode(' '));
		}
	});

	const groups = [];
	let currentGroup = [];
	let previousTop = null;

	tempWordSpans.forEach((span) => {
		const top = Math.round(span.offsetTop);
		if (previousTop === null || top === previousTop) {
			currentGroup.push(span.textContent);
		} else {
			groups.push(currentGroup);
			currentGroup = [span.textContent];
		}
		previousTop = top;
	});

	if (currentGroup.length > 0) {
		groups.push(currentGroup);
	}

	clearTarget(target);

	groups.forEach((groupWords, index) => {
		const line = document.createElement('span');
		line.className = 'underline-draw-line';
		line.style.setProperty('--underline-delay', `${index * delayStep}ms`);

		const inner = document.createElement('span');
		inner.className = 'underline-draw-line-inner';
		inner.textContent = groupWords.join(' ');

		line.appendChild(inner);
		target.appendChild(line);
	});

	target.dataset.underlineDrawReady = '1';
}

function restoreLineAnimationFlow(element) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target) return;

	const rawText = target.dataset.lineOriginalText || getAnimationText(target, 'wordRiseText');
	if (!rawText) return;

	target.textContent = rawText;
}

function writeSplitWords(target, text, className, delayStep = 55) {
	const words = text.split(' ');
	clearTarget(target);

	words.forEach((word, index) => {
		const span = document.createElement('span');
		span.className = className;
		span.style.transitionDelay = `${index * delayStep}ms`;
		span.textContent = word;
		target.appendChild(span);

		if (index < words.length - 1) {
			target.appendChild(document.createTextNode(' '));
		}
	});
}
