<?php
	/**
	 * (c) 2011
	 * Author: Giel Berkers
	 * Date: 10-10-11
	 * Time: 14:19
	 */



	require_once(EXTENSIONS.'/selectbox_link_field_plus/views/view.php');



	// The class name must be 'SBLPView_[filename - view. and .php (ucfirst)]':
	Class SBLPView_Photo_Gallery extends SBLPView
	{
		private static $assets_loaded = false;



		public function getName(){
			return __("Photo gallery (select a field of the type 'upload')");
		}

		public function getHandle(){
			return 'photo_gallery';
		}

		/**
		 * Generates the create new functionality.
		 *
		 * @param XMLElement               $wrapper
		 *	 The XMLElement wrapper in which the view is placed
		 * @param fieldSelectBox_Link_plus $field
		 *	 The field instance
		 * @param int $entry_id
		 *   Current entry ID
		 */
		public function generateCreate(XMLElement &$wrapper, fieldSelectBox_Link_plus $field, $entry_id = null){
			if( $field->get('enable_create') == 1 ){

				// new entry
				if( $entry_id === null ){
					$buttons = new XMLElement('span', null, array('class' => 'sblp-buttons'));
					$buttons->appendChild(new XMLElement('p', __('This field will be enabled after you create the entry.'), array('class' => 'help')));

					$wrapper->appendChild($buttons);
				}

				else{
					$related_sections = $field->findRelatedSections();

					$lang = Administration::instance()->Author->get('language');
					foreach( $related_sections as &$s ){
						$s->set('name', $s->get("slh_t-$lang"));
					}

					usort($related_sections, function($a, $b){
						return strcasecmp($a->get('name'), $b->get('name'));
					});

					$create_options = array();

					$buttons = new XMLElement('span', __('New entry in'), array('class' => 'sblp-buttons'));
					foreach( $related_sections as $idx => $section ){
						/** @var $section Section */
						$create_options[] = array(URL.'/xandercms/publish/'.$section->get('handle').'/new/', $idx == 0, $section->get("name"));
					}

					$buttons->appendChild(Widget::Select('sblp_section_selector_'.$field->get('id'), $create_options, array('class' => 'sblp-section-selector')));
					$buttons->appendChild(Widget::Anchor(__("Create"), URL.'/xandercms/publish/'.$related_sections[0]->get('handle').'/new/', null, 'create button sblp-add'));

					$wrapper->appendChild($buttons);
				}
			}
		}

		public function generateShowCreated(XMLElement &$wrapper){
			$div = new XMLElement('span', null, array('class' => 'hide-others'));
			$input = Widget::Input('show_created', null, 'checkbox');
			$div->setValue(__('%s Show all', array($input->generate())));
			$wrapper->appendChild($div);
		}

		public function generateView(XMLElement &$wrapper, $fieldname, $options, fieldSelectBox_Link_plus $field){
			$lang = Administration::instance()->Author->get('language');
			foreach( $options as &$s ){
				if( isset($s['id']) ){
					$section = SectionManager::fetch($s['id']);
					$name = $section->get("slh_t-$lang");
					if( !empty($name) ) $s['label'] = $name;
					$s['handle'] = $section->get('handle');
				}
			}

			// sort sections & entries
			usort($options, function($a, $b){
				return strcasecmp($a['label'], $b['label']);
			});

			foreach( $options as &$key_sections )
				if( array_key_exists('options', $key_sections))
					usort($key_sections['options'], function($a, $b){
						return strcasecmp($a[2], $b[2]);
					});

			parent::generateView($wrapper, $fieldname, $options, $field);

			$alert = false;
			$thumbSize = 150;

			// Create the gallery:
			$gallery = new XMLElement('div', null, array('class' => 'sblp-photo_gallery'));
			$this->generateShowCreated($gallery);
			foreach( $options as $optGroup ){
				$container = new XMLElement('div', null, array('class' => 'container'));

				if( isset($optGroup['label']) ){
					$suffix = $field->get('allow_multiple_selection') == 'yes' ? ' <em>'.__('(drag to reorder)').'</em>' : '';
					$container->appendChild(new XMLElement('h3', $optGroup['label'].$suffix));

					foreach( $optGroup['options'] as $option ){
						$section = SectionManager::fetch($optGroup['id']);

						$id = $option[0];
						$value = $option[2];
						$attr = array(
							'rel' => $id,
							'class' => 'image',
							'style' => "width:{$thumbSize}px; height:{$thumbSize}px;",
							'data-section' => $section->get('handle')
						);

						// item
						preg_match('/<*a[^>]*href*=*["\']?([^"\']*)/', html_entity_decode($value), $matches);
						$href = str_replace(URL.'/workspace/', '', $matches[1]);
						if( empty($href) ){
							// If no href could be found, the field selected for the relation probably isn't of the type 'upload':
							// In this case, show a message to the user:
							$alert = true;
						}
						$img = '<img src="'.URL.'/image/2/'.$thumbSize.'/'.$thumbSize.'/5/'.$href.'" alt="thumb" width="'.$thumbSize.'" height="'.$thumbSize.'" />';

						// edit & delete
						$actions = '';
						if( $field->get('enable_edit') == 1 ){
							$actions .= '<a href="javascript:void(0)" class="edit" title="'.__('Edit this item').'"><i class="icon icon-pencil"></i></a>';
						}
						if( $field->get('enable_delete') == 1 ){
							$actions .= '<a href="javascript:void(0)" class="delete" title="'.__('Delete this item').'" style="left:'.($thumbSize-20).'px;"><i class="icon icon-trash"></i></a>';
						}
						$actions .= '<a href="javascript:void(0)" title="'.$href.'" class="thumb">'.$img.'</a>';

						$container->appendChild(new XMLElement('div', $actions, $attr));
					}
				}
				$gallery->appendChild($container);
			}
			$wrapper->appendChild($gallery);

			// send some data to JS
			$wrapper->setAttribute('data-alert', $alert ? 'true' : 'false');
			$wrapper->setAttribute('data-multiple', $field->get('allow_multiple_selection') == 'yes' ? 'true' : 'false');

			// append assets only once
			self::appendAssets();
		}



		public static function appendAssets(){
			if( self::$assets_loaded === false
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			){

				self::$assets_loaded = true;

				$page = Administration::instance()->Page;

				$page->addStylesheetToHead(URL."/extensions/selectbox_link_field_plus/assets/styles/view.photo_gallery.css");
				$page->addScriptToHead(URL."/extensions/selectbox_link_field_plus/assets/libraries/sblpview_photo_gallery.js");
				$page->addScriptToHead(URL."/extensions/selectbox_link_field_plus/assets/libraries/view.photo_gallery.js");
			}
		}
	}
