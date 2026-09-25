/**
 * Pan Motors Live video tiles.
 *
 * Hook: video[data-live-video][data-src]. Each video gets its poster (data-poster) and src when it
 * nears the viewport, so nothing below the fold loads early. It plays while at least 35% visible
 * and pauses when it leaves, as in the design.
 * Reduced motion: never loads or plays; the poster only.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initLiveVideos() {
	const videos = document.querySelectorAll('video[data-live-video][data-src]');

	if (!videos.length) {
		return;
	}

	if (!('IntersectionObserver' in window)) {
		videos.forEach((video) => {
			if (video.dataset.poster) {
				video.poster = video.dataset.poster;
			}
		});
		return;
	}

	const showPoster = (video) => {
		if (video.dataset.poster && !video.getAttribute('poster')) {
			video.poster = video.dataset.poster;
		}
	};

	const load = (video) => {
		showPoster(video);
		if (!video.getAttribute('src')) {
			video.src = video.dataset.src;
		}
	};

	const play = (video) => {
		if (REDUCED.matches) {
			return;
		}
		load(video);
		const attempt = video.play();
		if (attempt && attempt.catch) {
			attempt.catch(() => {});
		}
	};

	// Set src a little before the tile scrolls in.
	const nearby = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					// Reduced motion: poster only, never the video.
					if (REDUCED.matches) {
						showPoster(entry.target);
					} else {
						load(entry.target);
					}
					nearby.unobserve(entry.target);
				}
			});
		},
		{ rootMargin: '300px 0px' }
	);

	const visible = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					play(entry.target);
				} else {
					entry.target.pause();
				}
			});
		},
		{ threshold: 0.35 }
	);

	videos.forEach((video) => {
		video.muted = true;
		nearby.observe(video);
		visible.observe(video);
	});

	REDUCED.addEventListener('change', (event) => {
		if (event.matches) {
			videos.forEach((video) => video.pause());
		}
	});
}

initLiveVideos();
