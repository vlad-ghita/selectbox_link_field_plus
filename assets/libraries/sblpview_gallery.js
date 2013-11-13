(function ($, undefined) {

	sblp.SBLPView_Gallery = sblp.SBLPView.extend({

		init: function ($view) {
			this._super($view, {
				source_list: 'div.image'
			});

			if ($view.data('alert'))
				alert("No links could be found. Are you sure you have selected a field of the type 'upload' for the relation in the Selectbox Link Plus Field?");

			// listen to clicks
			$view.on('click', "div.sblp-gallery div.image a.thumb", function (e) {
				var $parent = $(this).parent();
				var id = $parent.attr("rel");

				if ($view.data('multiple')) {
					$parent.toggleClass("selected");
					if ($parent.hasClass("selected")) {
						$view.find("select.target option[value=" + id + "]").attr("selected", "selected");
					}
					else {
						$view.find("select.target option[value=" + id + "]").removeAttr("selected");
					}
				}
				else {
					$view.find("div.sblp-gallery div.image").removeClass("selected");
					$view.find("select.target option").removeAttr("selected");
					$view.find("select.target option[value=" + id + "]").attr("selected", "selected");
					$parent.addClass("selected");
				}

				return false;
			});

			// initialize
			this.update();
		},

		update: function () {
			var view = this;

			// add visual effects
			view.$view.find("select.target option:selected").each(function () {
				view.$view.find("div.image[rel=" + $(this).val() + "]").addClass("selected");
			});

			// initialize
			if (view.$view.data('multiple')) {
				// Load the sorting order-state:
				this.loadSorting();

				view.$view.find("div.sblp-gallery div.container").sortable({items: "div.image", update: function () {
					// Update the option list according to the div items:
					view.sortItems();
				}});

				view.$view.disableSelection();
			}

			// Show all:
			view.$view.find("input[name=show_created]")
				.change(function () {
					if ($(this).attr("checked")) {
						// Show everything:
						view.$view.find("label").show();
					} else {
						// Only show the selected items:
						view.$view.find("label").hide();
						view.$view.find("label:has(input:checked)").show();
					}
				})
				.trigger('change');
		}

	})

})(jQuery);
