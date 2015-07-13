/* global pagenow, ajaxurl, postboxes, wpActiveEditor:true */
var ajaxWidgets, ajaxPopulateWidgets;

jQuery(document).ready( function($) {

	// These widgets are sometimes populated via ajax
	ajaxWidgets = ['dashboard_primary'];

	ajaxPopulateWidgets = function(el) {
		function show(i, id) {
			var p, e = $('#' + id + ' div.inside:visible').find('.widget-loading');
			if ( e.length ) {
				p = e.parent();
				setTimeout( function(){
					p.load( ajaxurl + '?action=dashboard-widgets&widget=' + id + '&pagenow=' + pagenow, '', function() {
						p.hide().slideDown('normal', function(){
							$(this).css('display', '');
						});
					});
				}, i * 500 );
			}
		}

		if ( el ) {
			el = el.toString();
			if ( $.inArray(el, ajaxWidgets) !== -1 ) {
				show(0, el);
			}
		} else {
			$.each( ajaxWidgets, show );
		}
	};
	ajaxPopulateWidgets();

	postboxes.add_postbox_toggles(pagenow, { pbshow: ajaxPopulateWidgets } );

	$( '.meta-box-sortables' ).sortable( 'option', 'containment', '#wpwrap' );

	function autoResizeTextarea() {
		if ( document.documentMode && document.documentMode < 9 ) {
			return;
		}

		// Add a hidden div. We'll copy over the text from the textarea to measure its height.
		$('body').append( '<div class="quick-draft-textarea-clone" style="display: none;"></div>' );

		var clone = $('.quick-draft-textarea-clone'),
			editor = $('#content'),
			editorHeight = editor.height(),
			// 100px roughly accounts for browser chrome and allows the
			// save draft button to show on-screen at the same time.
			editorMaxHeight = $(window).height() - 100;

		// Match up textarea and clone div as much as possible.
		// Padding cannot be reliably retrieved using shorthand in all browsers.
		clone.css({
			'font-family': editor.css('font-family'),
			'font-size':   editor.css('font-size'),
			'line-height': editor.css('line-height'),
			'padding-bottom': editor.css('paddingBottom'),
			'padding-left': editor.css('paddingLeft'),
			'padding-right': editor.css('paddingRight'),
			'padding-top': editor.css('paddingTop'),
			'white-space': 'pre-wrap',
			'word-wrap': 'break-word',
			'display': 'none'
		});

		// propertychange is for IE < 9
		editor.on('focus input propertychange', function() {
			var $this = $(this),
				// &nbsp; is to ensure that the height of a final trailing newline is included.
				textareaContent = $this.val() + '&nbsp;',
				// 2px is for border-top & border-bottom
				cloneHeight = clone.css('width', $this.css('width')).text(textareaContent).outerHeight() + 2;

			// Default to having scrollbars
			editor.css('overflow-y', 'auto');

			// Only change the height if it has indeed changed and both heights are below the max.
			if ( cloneHeight === editorHeight || ( cloneHeight >= editorMaxHeight && editorHeight >= editorMaxHeight ) ) {
				return;
			}

			// Don't allow editor to exceed height of window.
			// This is also bound in CSS to a max-height of 1300px to be extra safe.
			if ( cloneHeight > editorMaxHeight ) {
				editorHeight = editorMaxHeight;
			} else {
				editorHeight = cloneHeight;
			}

			// No scrollbars as we change height, not for IE < 9
			editor.css('overflow', 'hidden');

			$this.css('height', editorHeight + 'px');
		});
	}

} );
