/**
 * Cars grid (Featured Cars, _design/v2/cars.html): marque filters and the car sheet dialog.
 *
 * - Filters: shows the buttons (hidden without JS, when every car shows), fades the grid out,
 *   lays the matching cards out again in rows of 3, 2, 3, 2, 3 and fades it back in (.38s).
 *   Cards are moved, never created; the buttons carry aria-pressed.
 * - Car sheet: a native <dialog> opened with showModal() (focus stays inside, Esc closes). All
 *   sheets are in the HTML; this shows one at a time. Tab wraps within the open sheet. Previous / next and the arrow keys move
 *   within the current filter and update the counter. Closing returns focus to the card and
 *   unlocks page scrolling.
 * - Reduced motion: no fades (CSS drops the transitions and the dialog animation).
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');
const PATTERN = [3, 2, 3, 2, 3];
const FADE_MS = 380;

const pad = (n) => String(n).padStart(2, '0');

function initCars(root) {
	const grid = root.querySelector('[data-cars-grid]');
	const dialog = root.querySelector('[data-cars-dialog]');
	const filters = root.querySelector('[data-cars-filters]');
	if (!grid) {
		return;
	}

	const cards = [...grid.querySelectorAll('[data-car]')];
	const sheets = dialog ? [...dialog.querySelectorAll('[data-sheet]')] : [];
	let marque = '';
	let current = null;

	const visible = () => cards.filter((c) => !marque || c.dataset.marque === marque);

	// Rows of 3, 2, 3, 2, 3 for the visible cards; the rest wait, hidden, in a holder.
	const layout = () => {
		const show = visible();
		const holder = document.createElement('div');
		holder.hidden = true;
		grid.replaceChildren();
		let i = 0;
		for (let p = 0; i < show.length; p++) {
			const n = PATTERN[p % PATTERN.length];
			const row = document.createElement('div');
			row.className = 'pm-cars__row in in-done';
			show.slice(i, i + n).forEach((card, j) => {
				card.classList.toggle('pm-car--wide', (n === 2 && j === 0) || (n === 3 && j === 1));
				row.append(card);
			});
			grid.append(row);
			i += n;
		}
		cards.filter((c) => !show.includes(c)).forEach((c) => holder.append(c));
		grid.append(holder);
	};

	const pick = (button) => {
		const value = button.dataset.filter || '';
		if (value === marque) {
			return;
		}
		filters.querySelectorAll('[data-filter]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
		marque = value;
		if (REDUCED.matches) {
			layout();
			return;
		}
		grid.classList.add('is-fading');
		window.setTimeout(() => {
			layout();
			window.requestAnimationFrame(() => grid.classList.remove('is-fading'));
		}, FADE_MS);
	};

	if (filters) {
		filters.hidden = false;
		filters.addEventListener('click', (event) => {
			const button = event.target.closest('[data-filter]');
			if (button) {
				pick(button);
			}
		});
	}

	if (!dialog || typeof dialog.showModal !== 'function') {
		return;
	}

	const show = (id, focusSelector) => {
		const list = visible();
		const index = list.findIndex((c) => c.dataset.car === id);
		current = id;
		sheets.forEach((sheet) => {
			const on = sheet.dataset.sheet === id;
			sheet.hidden = !on;
			sheet.classList.toggle('is-current', on);
			if (on) {
				sheet.querySelector('[data-lb-pos]').textContent = pad(index + 1);
				sheet.querySelector('[data-lb-total]').textContent = pad(list.length);
				dialog.setAttribute('aria-labelledby', sheet.getAttribute('aria-labelledby'));
				if (focusSelector) {
					sheet.querySelector(focusSelector)?.focus();
				}
			}
		});
	};

	const shift = (step, focusSelector) => {
		const list = visible();
		const index = list.findIndex((c) => c.dataset.car === current);
		const next = list[(index + step + list.length) % list.length];
		if (next) {
			show(next.dataset.car, focusSelector);
		}
	};

	grid.addEventListener('click', (event) => {
		const opener = event.target.closest('[data-car-open]');
		if (!opener) {
			return;
		}
		show(opener.dataset.carOpen);
		document.documentElement.classList.add('pm-lb-open');
		dialog.showModal(); // Focuses the first control of the open sheet (the close button).
	});

	dialog.addEventListener('click', (event) => {
		if (event.target === dialog || event.target.closest('[data-lb-close]')) {
			dialog.close();
		} else if (event.target.closest('[data-lb-prev]')) {
			shift(-1, '[data-lb-prev]');
		} else if (event.target.closest('[data-lb-next]')) {
			shift(1, '[data-lb-next]');
		}
	});

	dialog.addEventListener('keydown', (event) => {
		// Keep Tab inside the open sheet (a modal dialog would otherwise let it reach the browser bar).
		if (event.key === 'Tab') {
			const items = [...dialog.querySelectorAll('[data-sheet]:not([hidden]) :is(a[href], button)')];
			const first = items[0];
			const last = items.at(-1);
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
			return;
		}
		if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
			event.preventDefault();
			shift(event.key === 'ArrowRight' ? 1 : -1, event.key === 'ArrowRight' ? '[data-lb-next]' : '[data-lb-prev]');
		}
	});

	// Esc (cancel), the close button and a click outside all end here.
	dialog.addEventListener('close', () => {
		document.documentElement.classList.remove('pm-lb-open');
		cards.find((c) => c.dataset.car === current)?.querySelector('[data-car-open]')?.focus();
	});
}

document.querySelectorAll('[data-cars]').forEach(initCars);
