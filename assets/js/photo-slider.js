/**
 * Photo slider (Showroom "Inside", _design/v2/showroom.html).
 *
 * Every photo, blurred copy, caption and counter is in the HTML; this only moves "current".
 * Change as in the design: the photo fades out, at 320ms the next one (and its blurred copy,
 * caption, counter and dot) takes over and fades in (.6s) from the level the last one reached.
 * Reduced motion: instant.
 * Inputs: the arrows (both pairs), the dots, a drag or swipe of more than 50px on the photo
 * (vertical page scrolling stays with the browser: touch-action pan-y) and the arrow keys while
 * focus is inside the slider. All wrap around.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');
const SWAP_MS = 320;
const SWIPE_PX = 50;

function initSlider(root) {
	const photos = [...root.querySelectorAll('.pm-slider__photo')];
	const backs = [...root.querySelectorAll('.pm-slider__bg')];
	const parts = [...root.querySelectorAll('[data-slide-part]')];
	const dots = [...root.querySelectorAll('[data-slide-dot]')];
	const stage = root.querySelector('[data-slide-stage]');
	const total = photos.length;

	if (total < 2 || !stage) {
		return;
	}

	let current = 0;
	let timer = 0;

	const show = (index) => {
		photos.forEach((img, i) => {
			img.classList.remove('is-leaving');
			img.classList.toggle('is-current', i === index);
			img.setAttribute('aria-hidden', i === index ? 'false' : 'true');
		});
		backs.forEach((img, i) => img.classList.toggle('is-current', i === index));
		parts.forEach((el) => {
			const on = Number(el.dataset.slidePart) === index;
			el.hidden = !on;
			el.classList.toggle('is-current', on);
		});
		dots.forEach((dot, i) => dot.setAttribute('aria-current', i === index ? 'true' : 'false'));
	};

	const go = (index) => {
		const next = (index + total) % total;
		if (next === current) {
			return;
		}
		window.clearTimeout(timer);
		const leaving = photos[current];
		current = next;
		if (REDUCED.matches) {
			show(next);
			return;
		}
		leaving.classList.add('is-leaving');
		timer = window.setTimeout(() => {
			// The design swaps the image mid-fade: the next photo rises from where the last one got to.
			const from = getComputedStyle(leaving).opacity;
			const incoming = photos[next];
			incoming.style.transition = 'none';
			incoming.style.opacity = from;
			show(next);
			void incoming.offsetWidth; // Commit the start value before fading in.
			incoming.style.transition = '';
			incoming.style.opacity = '';
		}, SWAP_MS);
	};

	root.querySelectorAll('[data-slide-prev]').forEach((b) => b.addEventListener('click', () => go(current - 1)));
	root.querySelectorAll('[data-slide-next]').forEach((b) => b.addEventListener('click', () => go(current + 1)));
	dots.forEach((dot) => dot.addEventListener('click', () => go(Number(dot.dataset.slideDot))));

	root.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
			event.preventDefault();
			go(current + (event.key === 'ArrowRight' ? 1 : -1));
		}
	});

	// Drag with a mouse, swipe with a finger or pen. Horizontal only: the page still scrolls.
	let x0 = null;
	stage.addEventListener('pointerdown', (event) => {
		x0 = event.clientX;
		stage.classList.add('is-dragging');
	});
	const end = (event) => {
		if (x0 === null) {
			return;
		}
		const dx = event.clientX - x0;
		x0 = null;
		stage.classList.remove('is-dragging');
		if (Math.abs(dx) > SWIPE_PX) {
			go(current + (dx < 0 ? 1 : -1));
		}
	};
	stage.addEventListener('pointerup', end);
	stage.addEventListener('pointercancel', () => {
		x0 = null;
		stage.classList.remove('is-dragging');
	});
	stage.addEventListener('pointerleave', end);
}

document.querySelectorAll('[data-slider]').forEach(initSlider);
