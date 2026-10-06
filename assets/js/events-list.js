/**
 * Events list (Events page, _design/events/events.html): Upcoming / Past tabs.
 *
 * Shows the tab buttons (hidden without JS, when both groups show one after the other) and the
 * first group only. A tab fades the list out (.45s), swaps the group at 380ms and fades it back,
 * as the cars filter does. Groups are shown and hidden, never created; the buttons carry
 * aria-pressed. Reduced motion: instant.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');
const SWAP_MS = 380;

function initEvents(root) {
	const tabs = root.querySelector('[data-events-tabs]');
	const list = root.querySelector('[data-events-list]');
	const groups = [...root.querySelectorAll('[data-events-group]')];
	if (!tabs || !list || !groups.length) {
		return;
	}

	const buttons = [...tabs.querySelectorAll('[data-events-tab]')];
	let current = buttons.find((b) => b.getAttribute('aria-pressed') === 'true')?.dataset.eventsTab || groups[0].dataset.eventsGroup;
	let timer = 0;

	const show = (key) => groups.forEach((g) => { g.hidden = g.dataset.eventsGroup !== key; });

	const pick = (button) => {
		const key = button.dataset.eventsTab;
		if (key === current) {
			return;
		}
		current = key;
		buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
		window.clearTimeout(timer);
		if (REDUCED.matches) {
			show(key);
			return;
		}
		list.classList.add('is-fading');
		timer = window.setTimeout(() => {
			show(key);
			window.requestAnimationFrame(() => list.classList.remove('is-fading'));
		}, SWAP_MS);
	};

	tabs.hidden = false;
	show(current);
	tabs.addEventListener('click', (event) => {
		const button = event.target.closest('[data-events-tab]');
		if (button) {
			pick(button);
		}
	});
}

document.querySelectorAll('[data-events]').forEach(initEvents);
