/**
 * Showroom photo crossfade, as in the design.
 *
 * Hooks: [data-showroom] with stacked .pm-showroom__photo and .pm-showroom__bg images,
 * [data-slider-prev] / [data-slider-next] (desktop and mobile pairs), [data-slider-count],
 * [data-slider-caption] and a focusable [data-slider-viewport] for the arrow keys.
 * Toggles .is-active on the current photo and its blurred copy; CSS does the 0.75s fade
 * (instant under reduced motion). Arrows wrap.
 */

function initShowroom(root) {
	const photos = Array.from(root.querySelectorAll('.pm-showroom__photo'));
	const backs = Array.from(root.querySelectorAll('.pm-showroom__bg'));
	const count = root.querySelector('[data-slider-count]');
	const caption = root.querySelector('[data-slider-caption]');
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
	};

	root.querySelectorAll('[data-slider-prev]').forEach((btn) => btn.addEventListener('click', () => go(current - 1)));
	root.querySelectorAll('[data-slider-next]').forEach((btn) => btn.addEventListener('click', () => go(current + 1)));

	root.querySelector('[data-slider-viewport]')?.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			go(current - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			go(current + 1);
		}
	});
}

document.querySelectorAll('[data-showroom]').forEach(initShowroom);
