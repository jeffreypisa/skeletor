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