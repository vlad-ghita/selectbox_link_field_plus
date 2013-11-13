(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-icons').each(function () {
			var $view = $(this);
			sblp.views[$view.attr('id')] = new sblp.SBLPView_Icons($view);

			$view.parents(".field-selectbox_link_plus").css('width', '152%');
		});

	});

})(jQuery);
