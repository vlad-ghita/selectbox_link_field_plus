<?php
	/**
	 * (c) 2011
	 * Author: Giel Berkers
	 * Date: 10-10-11
	 * Time: 14:19
	 */



	require_once(EXTENSIONS.'/selectbox_link_field_plus/views/view.php');



	// The class name must be 'SBLPView_[filename - view. and .php (ucfirst)]':
	Class SBLPView_Widgets extends SBLPView
	{
		private static $assets_loaded = false;



		public function getName(){
			return __( "Widgets" );
		}

		public function getHandle(){
			return 'widgets';
		}

		public function generateCreate(XMLElement &$wrapper, fieldSelectBox_Link_plus $field){
			if( $field->get( 'enable_create' ) == 1 && Administration::instance()->Author->isDeveloper() ){
				$related_sections = $field->findRelatedSections();

				$lang = Administration::instance()->Author->get( 'language' );
				foreach( $related_sections as &$s ){
					$s->set( 'name', $s->get( "slh_t-$lang" ) );
				}

				usort( $related_sections, function($a, $b){
					return strcasecmp( $a->get( 'name' ), $b->get( 'name' ) );
				} );

				$create_options = array();

				$buttons = new XMLElement('span', __( 'New entry in' ), array('class' => 'sblp-buttons'));
				foreach( $related_sections as $idx => $section ){
					/** @var $section Section */
					$create_options[] = array(
						URL.'/xandercms/publish/'.$section->get( 'handle' ).'/new/',
						$idx == 0,
						$section->get( "name" ),
						null,
						null,
						array('data-id' => $section->get( 'id' ))
					);
				}

				$buttons->appendChild( Widget::Select( 'sblp_section_selector_'.$field->get( 'id' ), $create_options, array('class' => 'sblp-section-selector') ) );
				$buttons->appendChild( Widget::Anchor( __( "Create" ), URL.'/xandercms/publish/'.$related_sections[0]->get( 'handle' ).'/new/', null, 'create button sblp-add' ) );

				$wrapper->appendChild( $buttons );
			}
		}

		public function generateView(XMLElement &$wrapper, $fieldname, $options, fieldSelectBox_Link_plus $field){
			$lang = Administration::instance()->Author->get( 'language' );
			foreach( $options as &$s ){
				if( isset($s['id']) ){
					$section = SectionManager::fetch( $s['id'] );
					$name = $section->get( "slh_t-$lang" );
					if( !empty($name) ) $s['label'] = $name;
					$s['handle'] = $section->get( 'handle' );
				}
			}

			// sort sections & entries
			usort( $options, function($a, $b){
				return strcasecmp( $a['label'], $b['label'] );
			} );

			foreach( $options as &$key_sections )
				if( array_key_exists( 'options', $key_sections ) )
					usort( $key_sections['options'], function($a, $b){
						return strcasecmp( $a[2], $b[2] );
					} );

			parent::generateView( $wrapper, $fieldname, $options, $field );

			if( Administration::instance()->Author->isDeveloper() ){
				$wrapper->appendChild( new XMLElement('input', null, array('type' => 'text', 'class' => 'sblp-widgets')) );
			}

			$lc = Lang::get();
			if( !FLang::validateLangCode($lc) ) $lc = FLang::getMainLang();

			// Show checkboxes:
			$checkboxes = new XMLElement('div', null, array('class' => 'sblp-widgets'));
			foreach( $options as $optGroup ){
				$container = new XMLElement('div', null, array('class' => 'container'));

				if( isset($optGroup['label']) ){

					if( Administration::instance()->Author->isDeveloper() ){
						$suffix = $field->get( 'allow_multiple_selection' ) ? ' <em>'.__( '(drag to reorder)' ).'</em>' : '';
						$container->appendChild( new XMLElement('h3', __( 'Content' ).$suffix) );
					}

					$this->generateShowCreated( $container );

					// In case of no multiple and not required:
					if( $field->get( 'allow_multiple_selection' ) == 'no' && $field->get( 'required' ) == 'no' ){
						$label = Widget::Label( '<em>'.__( 'Select none' ).'</em>', Widget::Input( 'sblp-checked-'.$field->get( 'id' ), '0', 'radio' ) );
						$container->appendChild( $label );
					}

					foreach( $optGroup['options'] as $option ){
						$section = SectionManager::fetch( $optGroup['id'] );

						$entry_id = $option[0];

						/** @var $entry Entry */
						$entry = EntryManager::fetch( $entry_id );
						if( is_array( $entry ) ) $entry = current( $entry );

						$value = '';

						$fid_title = FieldManager::fetchFieldIDFromElementName( 'title', $optGroup['id'] );
						$value_author = $entry->getData( $fid_title );
						$value_author = strip_tags( html_entity_decode( $value_author["value_formatted-$lc"] ) );
						$value .= "[$entry_id] $value_author";

						// item
						$label = Widget::Label();
						$attributes = array();
						if( !Administration::instance()->Author->isDeveloper() )
							$attributes['disabled'] = 'disabled';

						if( $field->get( 'allow_multiple_selection' ) == 'yes' ){
							$input = Widget::Input( 'sblp-checked-'.$field->get( 'id' ).'[]', (string) $entry_id, 'checkbox', $attributes );
						}
						else{
							$input = Widget::Input( 'sblp-checked-'.$field->get( 'id' ), (string) $entry_id, 'radio', $attributes );
						}
						$label->setValue( __( '%s <span class="text">%s</span>', array($input->generate(), $value) ) );
						$label->setAttributeArray( array(
							'title' => $value,
							'rel' => $entry_id,
							'data-section' => $section->get( 'handle' )
						) );

						// edit & delete
						$actions = '';
						if( $field->get( 'enable_edit' ) == 1 ){
							$actions .= '<a href="javascript:void(0)" class="edit"><i class="icon icon-edit"></i></a>';
						}
						if( $field->get( 'enable_delete' ) == 1 && Administration::instance()->Author->isDeveloper() ){
							$actions .= '<a href="javascript:void(0)" class="delete"><i class="icon icon-trash"></i></a>';
						}

						if( $actions !== '' ){
							$label->appendChild( new XMLElement('span', $actions, array('class' => 'sblp-widgets-actions')) );
						}

						$container->appendChild( $label );

					}
				}
				$checkboxes->appendChild( $container );
			}

			$wrapper->appendChild( $checkboxes );

			// send some data to JS
			$wrapper->setAttribute( 'data-multiple', $field->get( 'allow_multiple_selection' ) == 'yes' ? 'true' : 'false' );
			$wrapper->setAttribute( 'data-dev', Administration::instance()->Author->isDeveloper() ? 'true' : 'false' );

			// append assets only once
			self::appendAssets();
		}



		public static function appendAssets(){
			if( self::$assets_loaded === false
				&& class_exists( 'Administration' )
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			){

				self::$assets_loaded = true;

				$page = Administration::instance()->Page;

				$page->addStylesheetToHead( URL."/extensions/selectbox_link_field_plus/assets/styles/view.widgets.css" );
				$page->addScriptToHead( URL."/extensions/selectbox_link_field_plus/assets/libraries/jquery.autocomplete.js" );
				$page->addScriptToHead( URL."/extensions/selectbox_link_field_plus/assets/libraries/sblpview_widgets.js" );
				$page->addScriptToHead( URL."/extensions/selectbox_link_field_plus/assets/libraries/view.widgets.js" );
			}
		}
	}
