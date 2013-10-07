(function ($, undefined) {

	sblp.SBLPView_Widgets = sblp.SBLPView.extend({

		init: function ($view) {
			this._super($view, {
				source_list: 'label'
			});

			// initialize
			this.update();
		},

		update: function () {
			var view = this;
			var is_dev = view.$view.data('dev');

			view.$view.find("select.target option:selected").each(function () {
				view.$view.find("div.sblp-widgets input[value=" + $(this).val() + "]").attr("checked", "checked");
			});

			if (is_dev) {
				view.$view.find("div.sblp-widgets input").change(function (e) {
					view.$view.find("select.target option").removeAttr("selected");
					view.$view.find("input:checked").each(function () {
						var id = $(this).val();
						view.$view.find("select.target option[value=" + id + "]").attr("selected", "selected");
					});
				});

				var options = [];
				view.$view.find("select.target option").each(function (i) {
					options[i] = { name: $(this).text(), id: $(this).attr("value") };
				});

				view.$view.parents('.field-selectbox_link_plus').find("input.sblp-widgets")
					.autocomplete(options, {
						multiple     : true,
						matchContains: true,
						formatItem   : function (row, i, max) {
							return row.name;
						},
						formatMatch  : function (row, i, max) {
							return row.name;
						}
					})
					.result(function (event, data, formatted) {

						var option = view.$view.find("div.sblp-widgets input[value=" + data.id + "]");

						option.attr("checked", "checked");
						option.parent().parent().parent().show();

						view.$view.find("select.target option[value=" + data.id + "]").attr("selected", "selected");

						$(this).val("");
					});
			}

			if (view.$view.data('multiple')) {
				// Load the sorting order-state:
				this.loadSorting();

				view.$view.find("div.sblp-widgets div.container").sortable({items: "label", update: function () {
					// Update the option list according to the div items:
					view.sortItems();
				}});

				view.$view.disableSelection();
			}

			// Show all:
			var $hide_others = view.$view.find(".hide-others");

			$hide_others.find("input[name=show_created]")
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

			if (!is_dev) {
				$hide_others.hide();

				if (view.$view.find("label:has(input:checked)").length == 0)
					view.$view.parents('.field-selectbox_link_plus').hide();

				// fix label margin-bottom
				view.$view.siblings('label').css('margin-bottom', '0');
			}
		}

	})

})(jQuery);
