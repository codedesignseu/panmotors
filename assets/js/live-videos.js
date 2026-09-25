/**
 * Pan Motors Live video tiles.
 *
 * Hook: video[data-live-video][data-src]. Each video gets its src when it nears the viewport,
 * plays while at least 35% visible and pauses when it leaves, as in the design.
 * Reduced motion: never loads or plays; the poster stays.
 */

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)');

function initLiveVideos() {
	const videos = document.querySelectorAll('video[data-live-video][data-src]');

	if (!videos.length || !('IntersectionObserver' in window)) {
		return;
	}

	const load = (video) => {
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
				if (entry.isIntersecting && !REDUCED.matches) {
					load(entry.target);
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
