/**
 * Scroll reveal, as in the design: adds .in to [data-rise] and [data-rise-l] once 15% is in view.
 *
 * Stagger (i % 3) * 90ms across the page. Each element is revealed once, then unobserved.
 * The hidden state is CSS under html.pm-js only, so content is visible without JS.
 * Reduced motion: everything is revealed immediately (CSS also drops the animation).
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initReveal() {
	const items = document.querySelectorAll('[data-rise], [data-rise-l]');

	if (!items.length) {
		return;
	}

	if (REDUCED.matches || !('IntersectionObserver' in window)) {
		items.forEach((el) => el.classList.add('in'));
		return;
	}

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('in');
					observer.unobserve(entry.target);
				}
			});
		},
		{ threshold: 0.15 }
	);

	items.forEach((el, i) => {
		el.style.animationDelay = `${(i % 3) * 90}ms`;
		observer.observe(el);
	});
}

initReveal();
