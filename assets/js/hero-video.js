/**
 * Hero video: starts playback (always muted), fades the video in over the poster.
 *
 * Hooks: [data-hero-video] section with a <video>.
 * Reduced motion: never plays (poster only), and stops if the setting changes mid-visit.
 * Pauses while the hero is off screen. Playback starts after the load event, so the video
 * download never competes with the first paint (it fades in over the poster anyway).
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initHeroVideo() {
	const hero = document.querySelector('[data-hero-video]');
	const video = hero && hero.querySelector('video');

	if (!video) {
		return;
	}

	let inView = true;
	let ready = document.readyState === 'complete';
	video.muted = true;

	const play = () => {
		if (REDUCED.matches || !inView || !ready) {
			return;
		}
		const attempt = video.play();
		if (attempt && attempt.catch) {
			attempt.catch(() => {});
		}
	};

	const stop = () => {
		video.pause();
		video.classList.remove('is-playing');
	};

	video.addEventListener('playing', () => video.classList.add('is-playing'));

	REDUCED.addEventListener('change', (event) => (event.matches ? stop() : play()));

	if (!ready) {
		window.addEventListener('load', () => {
			ready = true;
			play();
		}, { once: true });
	}

	new IntersectionObserver(([entry]) => {
		inView = entry.isIntersecting;
		if (inView) {
			play();
		} else {
			video.pause();
		}
	}).observe(hero);
}

initHeroVideo();
