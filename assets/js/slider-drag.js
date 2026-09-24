/**
 * Latest Cars slider, as in the design.
 *
 * Hooks: [data-slider] with [data-slider-track], [data-slider-viewport], [data-slider-prev],
 * [data-slider-next] and [data-slider-count]. All slides are in the HTML; this only moves the track.
 *
 * - Prev/next wrap around. Left/right arrow keys work when the viewport has focus.
 * - Pointer drag follows the finger (x 1.05) and snaps: every 55% of a slide dragged moves one slide.
 * - Transform transition 1.05s with the site ease. Recalculated on resize.
 * - Reduced motion: no transition, instant jumps.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');
const EASE = 'cubic-bezier(.16,.84,.3,1)';

function initSlider(root) {
	const track = root.querySelector('[data-slider-track]');
	const viewport = root.querySelector('[data-slider-viewport]');
	const count = root.querySelector('[data-slider-count]');
	const slides = track ? Array.from(track.children) : [];

	if (!track || slides.length < 2) {
		return;
	}

	const total = slides.length;
	const pad = (n) => String(n).padStart(2, '0');
	let current = 0;
	let drag = null;

	// Distance between slide starts: width + the 18px gap.
	const step = () => {
		const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
		return slides[0].getBoundingClientRect().width + gap;
	};

	const shift = (animate) => {
		track.style.transition = animate && !REDUCED.matches ? `transform 1.05s ${EASE}` : 'none';
		track.style.transform = `translate3d(${-current * step()}px, 0, 0)`;
	};

	const go = (index, { wrap = true } = {}) => {
		const next = wrap ? (index + total) % total : Math.max(0, Math.min(total - 1, index));
		current = next;
		if (count) {
			count.textContent = `${pad(current + 1)} / ${pad(total)}`;
		}
		slides.forEach((slide, i) => slide.setAttribute('aria-hidden', i === current ? 'false' : 'true'));
		shift(true);
	};

	root.querySelector('[data-slider-prev]')?.addEventListener('click', () => go(current - 1));
	root.querySelector('[data-slider-next]')?.addEventListener('click', () => go(current + 1));

	viewport?.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			go(current - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			go(current + 1);
		}
	});

	// Pointer drag with snap. No wrap on drag, as in the design.
	const onMove = (event) => {
		if (!drag) {
			return;
		}
		drag.moved = event.clientX - drag.x;
		track.style.transition = 'none';
		track.style.transform = `translate3d(${drag.base + drag.moved * 1.05}px, 0, 0)`;
	};

	const onEnd = () => {
		if (!drag) {
			return;
		}
		const jumps = Math.round(-drag.moved / Math.max(1, step() * 0.55));
		drag = null;
		track.classList.remove('is-dragging');
		go(current + (jumps || 0), { wrap: false });
	};

	track.addEventListener('pointerdown', (event) => {
		if (event.button !== 0) {
			return;
		}
		drag = { x: event.clientX, base: -current * step(), moved: 0 };
		track.classList.add('is-dragging');
		try {
			track.setPointerCapture(event.pointerId);
		} catch {
			// Pointer already gone; the drag still ends on pointerup/cancel.
		}
	});
	track.addEventListener('pointermove', onMove);
	track.addEventListener('pointerup', onEnd);
	track.addEventListener('pointercancel', onEnd);
	track.addEventListener('dragstart', (event) => event.preventDefault());

	window.addEventListener('resize', () => shift(false));
	REDUCED.addEventListener('change', () => shift(false));

	go(0);
	shift(false);
}

document.querySelectorAll('[data-slider]').forEach(initSlider);
