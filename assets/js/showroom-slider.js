/**
 * Showroom photo crossfade, as in the design.
 *
 * Hooks: [data-showroom] with stacked .pm-showroom__photo and .pm-showroom__bg images,
 * [data-slider-prev] / [data-slider-next] (desktop and mobile pairs), [data-slider-count],
 * [data-slider-caption], [data-slider-dot] buttons and a focusable [data-slider-viewport] for the
 * arrow keys and a drag or swipe of more than 50px.
 * Toggles .is-active on the current photo and its blurred copy; CSS does the 0.75s fade
 * (instant under reduced motion). Arrows wrap.
 */

const SWIPE_PX = 50;

function initShowroom(root) {
	const photos = Array.from(root.querySelectorAll('.pm-showroom__photo'));
	const backs = Array.from(root.querySelectorAll('.pm-showroom__bg'));
	const count = root.querySelector('[data-slider-count]');
	const caption = root.querySelector('[data-slider-caption]');
	const dots = Array.from(root.querySelectorAll('[data-slider-dot]'));
	const viewport = root.querySelector('[data-slider-viewport]');
	const total = photos.length;

	if (total < 2) {
		return;
	}

	const pad = (n) => String(n).padStart(2, '0');
	let current = 0;

	const go = (index) => {
		current = (index + total) % total;
		photos.forEach((img, i) => {
			img.classList.toggle('is-active', i === current);
			img.setAttribute('aria-hidden', i === current ? 'false' : 'true');
		});
		backs.forEach((img, i) => img.classList.toggle('is-active', i === current));
		if (count) {
			count.textContent = `${pad(current + 1)} / ${pad(total)}`;
		}
		if (caption) {
			caption.textContent = photos[current].dataset.caption || '';
		}
		dots.forEach((dot, i) => dot.setAttribute('aria-current', i === current ? 'true' : 'false'));
	};

	root.querySelectorAll('[data-slider-prev]').forEach((btn) => btn.addEventListener('click', () => go(current - 1)));
	root.querySelectorAll('[data-slider-next]').forEach((btn) => btn.addEventListener('click', () => go(current + 1)));
	dots.forEach((dot) => dot.addEventListener('click', () => go(Number(dot.dataset.sliderDot))));

	viewport?.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			go(current - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			go(current + 1);
		}
	});

	// Drag with a mouse, swipe with a finger or pen (as the Photo slider). Horizontal only: the
	// page still scrolls (touch-action: pan-y in the CSS).
	let x0 = null;
	viewport?.addEventListener('pointerdown', (event) => {
		x0 = event.clientX;
	});
	const end = (event) => {
		if (x0 === null) {
			return;
		}
		const dx = event.clientX - x0;
		x0 = null;
		if (Math.abs(dx) > SWIPE_PX) {
			go(current + (dx < 0 ? 1 : -1));
		}
	};
	viewport?.addEventListener('pointerup', end);
	viewport?.addEventListener('pointerleave', end);
	viewport?.addEventListener('pointercancel', () => {
		x0 = null;
	});
}

document.querySelectorAll('[data-showroom]').forEach(initShowroom);
