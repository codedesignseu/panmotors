/**
 * About section scroll fade, as in the design.
 *
 * Hook: [data-heritage-fade]. As the section enters (over 80% of the viewport height), --fade
 * goes 0 → 1 (background ink → paper) and --tfade flips 0 → 1 at the halfway point (text
 * paper → ink). Updates once per frame with requestAnimationFrame.
 * Reduced motion: no fade, the section stays dark.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initHeritageFade() {
	const section = document.querySelector('[data-heritage-fade]');

	if (!section) {
		return;
	}

	let frame = 0;

	const update = () => {
		frame = 0;
		if (REDUCED.matches) {
			section.style.setProperty('--fade', '0');
			section.style.setProperty('--tfade', '0');
			return;
		}
		const top = section.getBoundingClientRect().top;
		const span = window.innerHeight * 0.8;
		const progress = Math.max(0, Math.min(1, (window.innerHeight - top) / span));
		section.style.setProperty('--fade', progress.toFixed(3));
		section.style.setProperty('--tfade', progress < 0.5 ? '0' : '1');
	};

	const schedule = () => {
		if (!frame) {
			frame = requestAnimationFrame(update);
		}
	};

	window.addEventListener('scroll', schedule, { passive: true });
	window.addEventListener('resize', schedule);
	REDUCED.addEventListener('change', schedule);
	update();
}

initHeritageFade();
