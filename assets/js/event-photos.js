/**
 * Event Photographs (_design/events/event.html): photos in a moving track.
 *
 * Hook: [data-event-photos] with [data-photos-track], [data-photos-viewport], [data-photos-prev],
 * [data-photos-next] and the counters [data-photos-part]. Every photo and counter is in the HTML;
 * this moves the track (1.05s, the site ease) and switches which counter shows. Prev / next wrap
 * around. A drag or swipe of more than 50px changes the photo, as on the Showroom slider (vertical
 * page scrolling stays with the browser: touch-action pan-y). The arrow keys work while the photos
 * have focus. Recalculated on resize. Reduced motion: instant.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');
const SWIPE_PX = 50;

function initPhotos(root) {
	const track = root.querySelector('[data-photos-track]');
	const viewport = root.querySelector('[data-photos-viewport]');
	const slides = track ? [...track.children] : [];
	const parts = [...root.querySelectorAll('[data-photos-part]')];
	const total = slides.length;

	if (!track || !viewport || total < 2) {
		return;
	}

	let current = 0;

	// Distance between slide starts: the slide width and the 18px gap.
	const step = () => slides[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);

	const place = (animate) => {
		track.classList.toggle('is-instant', !animate || REDUCED.matches);
		track.style.transform = `translate3d(${-current * step()}px, 0, 0)`;
	};

	const go = (index) => {
		current = (index + total) % total;
		slides.forEach((slide, i) => slide.setAttribute('aria-hidden', i === current ? 'false' : 'true'));
		parts.forEach((el) => {
			const on = Number(el.dataset.photosPart) === current;
			el.hidden = !on;
			el.classList.toggle('is-current', on);
		});
		place(true);
	};

	root.querySelector('[data-photos-prev]')?.addEventListener('click', () => go(current - 1));
	root.querySelector('[data-photos-next]')?.addEventListener('click', () => go(current + 1));

	viewport.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
			event.preventDefault();
			go(current + (event.key === 'ArrowRight' ? 1 : -1));
		}
	});

	let x0 = null;
	viewport.addEventListener('pointerdown', (event) => {
		x0 = event.clientX;
		viewport.classList.add('is-dragging');
	});
	const end = (event) => {
		if (x0 === null) {
			return;
		}
		const dx = event.clientX - x0;
		x0 = null;
		viewport.classList.remove('is-dragging');
		if (Math.abs(dx) > SWIPE_PX) {
			go(current + (dx < 0 ? 1 : -1));
		}
	};
	viewport.addEventListener('pointerup', end);
	viewport.addEventListener('pointerleave', end);
	viewport.addEventListener('pointercancel', () => {
		x0 = null;
		viewport.classList.remove('is-dragging');
	});
	viewport.addEventListener('dragstart', (event) => event.preventDefault());

	window.addEventListener('resize', () => place(false));
}

document.querySelectorAll('[data-event-photos]').forEach(initPhotos);
