(function ($, undefined) {

	$(sblp).on('sblp.initialization', function () {

		// register views
		$('div.sblp-view-photo_gallery').each(function () {
			var $view = $(this);

			$view.parents(".field-selectbox_link_plus").css('width', '152%');

			sblp.views[$view.attr('id')] = new sblp.SBLPView_Photo_Gallery($view);
		});

	});

})(jQuery);
