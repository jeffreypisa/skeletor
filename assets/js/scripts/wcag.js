export function wcag() {
	let mouseDown = false;

	// Selecteer alle links en knoppen op de pagina
	const elements = document.querySelectorAll('a, button');

	// Voeg event listeners toe aan alle geselecteerde elementen
	elements.forEach((element) => {
		element.addEventListener('mousedown', () => {
			mouseDown = true;
		});

		element.addEventListener('mouseup', () => {
			mouseDown = false;
		});

		element.addEventListener('focus', (event) => {
			if (mouseDown) {
				event.target.blur();
			}
		});
	});
}