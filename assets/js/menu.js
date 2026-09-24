/**
 * Mobile menu: burger toggles the panel.
 *
 * Hooks: [data-menu-toggle] (button with aria-controls) and the panel it controls.
 * Closes on Escape, link click, and when the viewport grows past 1080px.
 * Locks page scroll and makes <main> and the footer inert while open, so focus stays in the menu.
 * Motion is handled in CSS (reduced motion included).
 */

const DESKTOP = window.matchMedia('(min-width: 1081px)');

function initMenu() {
	const toggle = document.querySelector('[data-menu-toggle]');
	const panel = toggle && document.getElementById(toggle.getAttribute('aria-controls'));

	if (!toggle || !panel) {
		return;
	}

	const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';
	const outside = [document.querySelector('main'), document.querySelector('body > footer')].filter(Boolean);
	const setOutsideInert = (value) => outside.forEach((el) => { el.inert = value; });

	const open = () => {
		toggle.setAttribute('aria-expanded', 'true');
		panel.inert = false;
		panel.classList.add('is-open');
		setOutsideInert(true);
		document.documentElement.classList.add('pm-menu-open');
	};

	const close = ({ returnFocus = true } = {}) => {
		if (!isOpen()) {
			return;
		}
		toggle.setAttribute('aria-expanded', 'false');
		panel.inert = true;
		panel.classList.remove('is-open');
		setOutsideInert(false);
		document.documentElement.classList.remove('pm-menu-open');
		if (returnFocus) {
			toggle.focus();
		}
	};

	toggle.addEventListener('click', () => (isOpen() ? close() : open()));

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen()) {
			close();
		}
	});

	// A link click jumps to its section, so focus follows the link, not the burger.
	panel.addEventListener('click', (event) => {
		if (event.target.closest('a')) {
			close({ returnFocus: false });
		}
	});

	DESKTOP.addEventListener('change', (event) => {
		if (event.matches) {
			close({ returnFocus: false });
		}
	});
}

initMenu();
