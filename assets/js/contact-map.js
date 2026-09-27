/**
 * Contact map: loads Google Maps only when asked (it sets cookies).
 *
 * The iframe is in the page inside a <template data-map-frame>; pressing [data-map-show] puts it
 * in place of the placeholder and moves focus to it. Nothing is requested from Google before.
 */

document.querySelectorAll('[data-map]').forEach((card) => {
	const button = card.querySelector('[data-map-show]');
	const template = card.querySelector('template[data-map-frame]');
	if (!button || !template) {
		return;
	}
	button.addEventListener('click', () => {
		const frame = template.content.firstElementChild.cloneNode(true);
		frame.setAttribute('tabindex', '0');
		card.querySelector('[data-map-placeholder]')?.remove();
		card.append(frame);
		card.classList.add('is-loaded');
		frame.focus();
	});
});
