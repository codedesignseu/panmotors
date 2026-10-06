/**
 * About Pan Motors video (pm/about, Media type: Video).
 *
 * Upload ([data-about-video] with a <video> and [data-about-toggle]): always muted. Plays only
 * while the section is in view and pauses when it leaves. The pause / play button stops it for
 * good (until pressed again). Reduced motion: never starts by itself; the poster shows with the
 * play button, and pressing it plays the video. The video fades in over the poster once it plays.
 *
 * YouTube or Vimeo ([data-about-embed]): nothing is loaded from YouTube or Vimeo (they set
 * cookies) until the visitor presses the play button on the poster. The player iframe waits in a
 * <template> in the page; it replaces the poster and takes focus.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initUpload(frame) {
	const video = frame.querySelector('video');
	const toggle = frame.querySelector('[data-about-toggle]');
	if (!video || !toggle) {
		return;
	}

	let inView = false;
	let stopped = REDUCED.matches; // Pressed pause, or reduced motion: wait for the play button.
	video.muted = true;

	const label = () => {
		const playing = !video.paused;
		toggle.setAttribute('aria-label', playing ? toggle.dataset.pause : toggle.dataset.play);
		toggle.classList.toggle('is-playing', playing);
	};

	const play = () => {
		if (stopped || !inView) {
			return;
		}
		const attempt = video.play();
		if (attempt && attempt.catch) {
			attempt.catch(label);
		}
	};

	video.addEventListener('playing', () => {
		video.classList.add('is-playing');
		label();
	});
	video.addEventListener('pause', label);

	toggle.addEventListener('click', () => {
		if (video.paused) {
			stopped = false;
			inView = true; // The button is on screen.
			play();
		} else {
			stopped = true;
			video.pause();
		}
	});

	REDUCED.addEventListener('change', (event) => {
		if (event.matches) {
			stopped = true;
			video.pause();
		}
	});

	new IntersectionObserver(([entry]) => {
		inView = entry.isIntersecting;
		if (inView) {
			play();
		} else {
			video.pause();
		}
	}).observe(frame.closest('section') || frame);

	toggle.hidden = false;
	label();
}

function initEmbed(frame) {
	const button = frame.querySelector('[data-about-play]');
	const template = frame.querySelector('template[data-about-frame]');
	if (!button || !template) {
		return;
	}
	button.hidden = false;
	button.addEventListener('click', () => {
		const iframe = template.content.firstElementChild.cloneNode(true);
		frame.querySelectorAll('img, [data-about-play]').forEach((el) => el.remove());
		frame.append(iframe);
		frame.classList.add('is-loaded');
		iframe.focus();
	});
}

document.querySelectorAll('[data-about-video]').forEach(initUpload);
document.querySelectorAll('[data-about-embed]').forEach(initEmbed);
