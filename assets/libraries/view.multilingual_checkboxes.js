(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-multilingual_checkboxes').each(function () {
			var $view = $(this);
			sblp.views[$view.attr('id')] = new sblp.SBLPView_Multilingual_Checkboxes($view);
		});

	});

})(jQuery);
