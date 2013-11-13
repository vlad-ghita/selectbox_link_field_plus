(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-multilingual_autocomplete').each(function () {
			var $view = $(this);
			sblp.views[$view.attr('id')] = new sblp.SBLPView_Multilingual_Autocomplete($view);
		});

	});

})(jQuery);
