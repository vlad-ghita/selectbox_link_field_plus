(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-widgets').each(function () {
			var $view = $(this);
			sblp.views[$view.attr('id')] = new sblp.SBLPView_Widgets($view);
		});

	});

})(jQuery);
