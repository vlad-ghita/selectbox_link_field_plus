(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-content_entries').each(function () {
			var $view = $(this);
			sblp.views[$view.attr('id')] = new sblp.SBLPView_DEV_Content_Entries($view);
		});

	});

})(jQuery);
