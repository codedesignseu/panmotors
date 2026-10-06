/**
 * Event edit screen (pm_event, block editor).
 *
 * - The size hint and "required" note under the Main image panel (pmEventEditor.hint, from PHP,
 *   translation-ready).
 * - WordPress keeps the meta boxes (Event details, Photographs) in a pane at the bottom of the
 *   editor, closed until someone opens it. On this screen it starts open, so a new event shows its
 *   fields straight away. Only while the user has never opened or closed the pane themselves.
 */
(function (wp, data) {
	if (!wp || !wp.hooks || !wp.element || !data) {
		return;
	}
	const el = wp.element.createElement;
	wp.hooks.addFilter('editor.PostFeaturedImage', 'panmotors/event-main-image', (Original) => (props) =>
		el(wp.element.Fragment, null, el(Original, props), el('p', { className: 'components-base-control__help', style: { marginTop: '8px' } }, data.hint))
	);

	wp.domReady(() => {
		const prefs = wp.data && wp.data.select('core/preferences');
		if (prefs && undefined === prefs.get('core/edit-post', 'metaBoxesMainIsOpen')) {
			wp.data.dispatch('core/preferences').set('core/edit-post', 'metaBoxesMainIsOpen', true);
			// Half the screen: the title and story above stay in view (an unset height fills it all).
			if (undefined === prefs.get('core/edit-post', 'metaBoxesMainOpenHeight')) {
				wp.data.dispatch('core/preferences').set('core/edit-post', 'metaBoxesMainOpenHeight', Math.round(window.innerHeight * 0.5));
			}
		}
	});
})(window.wp, window.pmEventEditor);
