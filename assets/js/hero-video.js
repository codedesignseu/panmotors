/**
 * Hero video: starts playback, fades the video in over the poster, sound toggle.
 *
 * Hooks: [data-hero-video] section with a <video>, and the [data-hero-sound] button.
 * Reduced motion: never plays (poster only), and stops if the setting changes mid-visit.
 * Pauses while the hero is off screen.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initHeroVideo() {
	const hero = document.querySelector('[data-hero-video]');
	const video = hero && hero.querySelector('video');

	if (!video) {
		return;
	}

	const sound = hero.querySelector('[data-hero-sound]');
	let inView = true;

	const setSound = (on) => {
		video.muted = !on;
		if (sound) {
			sound.setAttribute('aria-pressed', String(on));
			sound.textContent = on ? sound.dataset.on : sound.dataset.off;
		}
	};

	const play = () => {
		if (REDUCED.matches || !inView) {
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
		setSound(false);
	};

	video.addEventListener('playing', () => video.classList.add('is-playing'));

	if (sound) {
		sound.addEventListener('click', () => {
			setSound(sound.getAttribute('aria-pressed') !== 'true');
			if (video.paused) {
				play();
			}
		});
	}

	REDUCED.addEventListener('change', (event) => (event.matches ? stop() : play()));

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
