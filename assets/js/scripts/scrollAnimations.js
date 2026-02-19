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
					typewriterEffect(entry.target);
				} else if (entry.target.classList.contains('animate-word-rise')) {
					setFixedHeight(entry.target);
					wordRiseEffect(entry.target);
					addInViewWithDelay(entry.target);
				} else if (entry.target.classList.contains('animate-char-fade-soft')) {
					setFixedHeight(entry.target);
					charFadeSoftEffect(entry.target);
					addInViewWithDelay(entry.target);
				} else if (entry.target.classList.contains('animate-line-reveal')) {
					setFixedHeight(entry.target);
					lineAnimationEffect(entry.target, 'line-reveal');
					addInViewWithDelay(entry.target);
				} else if (entry.target.classList.contains('animate-stagger-lines')) {
					setFixedHeight(entry.target);
					lineAnimationEffect(entry.target, 'stagger-lines');
					addInViewWithDelay(entry.target);
				} else {
					entry.target.classList.add('in-view');
				}
				observer.unobserve(entry.target);
			}
		});
	}, observerOptions);

	const animatedElements = document.querySelectorAll('[class*="animate-"]');
	animatedElements.forEach(el => observer.observe(el));
}

function setFixedHeight(element) {
	const clone = element.cloneNode(true);
	clone.style.visibility = 'hidden';
	clone.style.position = 'absolute';
	clone.style.whiteSpace = 'normal'; // Zorg dat het klopt met de originele tekstweergave
	clone.style.width = `${element.offsetWidth}px`;
	document.body.appendChild(clone);

	const height = clone.offsetHeight;
	element.style.minHeight = `${height}px`;
	document.body.removeChild(clone);
}

function typewriterEffect(element) {
	const text = element.getAttribute('data-text');
	if (!text) return;

	element.textContent = '';
	let i = 0;
	const speed = 50;

	function type() {
		if (i < text.length) {
			element.textContent += text.charAt(i);
			i++;
			setTimeout(type, speed);
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

function getAnimationTarget(element, dataAttribute = '') {
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

	writeSplitWords(target, rawText, 'word-rise-word', 55);
	target.dataset.wordRiseReady = '1';
}

function charFadeSoftEffect(element) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target || target.dataset.charFadeSoftReady === '1') return;

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;

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
		span.style.transitionDelay = `${visibleIndex * 22}ms`;
		span.textContent = char;
		target.appendChild(span);
		visibleIndex++;
	});

	target.dataset.charFadeSoftReady = '1';
}

function lineAnimationEffect(element, animationType) {
	const target = getAnimationTarget(element, 'wordRiseText');
	if (!target) return;

	const readyKey = animationType === 'line-reveal' ? 'lineRevealReady' : 'staggerLinesReady';
	if (target.dataset[readyKey] === '1') return;

	const rawText = getAnimationText(target, 'wordRiseText');
	if (!rawText) return;

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
		inner.style.transitionDelay = `${index * 85}ms`;
		inner.textContent = groupWords.join(' ');

		line.appendChild(inner);
		target.appendChild(line);
	});

	target.dataset[readyKey] = '1';
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
