/**
 * Mark the save button's row while it sticks to the bottom of the screen.
 *
 * It sticks 1px past the edge, so it is stuck exactly when it is cut off at
 * the bottom. The 0 threshold also catches it scrolling out of view above.
 *
 * @param bar Row holding the save button.
 * @return Stops watching.
 */
export function watchStuck( bar: HTMLElement | null ): () => void {
	if ( ! bar || 'function' !== typeof window.IntersectionObserver ) {
		return () => {};
	}

	const observer = new window.IntersectionObserver(
		( [ entry ] ) => {
			if ( ! entry ) {
				return;
			}

			const bottom = entry.rootBounds?.bottom ?? window.innerHeight;

			bar.classList.toggle(
				'bmw-is-stuck',
				entry.intersectionRatio < 1 &&
					entry.boundingClientRect.bottom > bottom
			);
		},
		{ threshold: [ 0, 1 ] }
	);

	observer.observe( bar );

	return () => observer.disconnect();
}
