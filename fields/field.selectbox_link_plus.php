<?php
	/**
	 * (c) 2011
	 * Author: Giel Berkers
	 * Date: 10-10-11
	 * Time: 10:46
	 */

	if (!defined('__IN_SYMPHONY__')) {
		die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	}

	require_once(EXTENSIONS . '/selectbox_link_field/fields/field.selectbox_link.php');
	require_once(EXTENSIONS . '/selectbox_link_field_plus/extension.driver.php');

	Class fieldSelectBox_Link_plus extends fieldSelectBox_Link {
		private static $cache = array();

		/*------------------------------------------------------------------------------------------------*/
		/* Definition  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();

			$this->_name            = __('Select Box Link +');
			$this->_required        = true;
			$this->_showassociation = true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/* Settings  */
		/*------------------------------------------------------------------------------------------------*/

		public function findDefaults(array &$settings) {
			if (!isset($settings['allow_multiple_selection'])) {
				$settings['allow_multiple_selection'] = 'no';
			}

			if (!isset($settings['show_association'])) {
				$settings['show_association'] = 'yes';
			}

			if (!isset($settings['limit'])) {
				$settings['limit'] = 20;
			}

			if (!isset($settings['enable_create'])) {
				$settings['enable_create'] = 1;
			}

			if (!isset($settings['enable_edit'])) {
				$settings['enable_edit'] = 1;
			}

			if (!isset($settings['enable_delete'])) {
				$settings['enable_delete'] = 1;
			}

			if (!isset($settings['related_field_id'])) {
				$settings['related_field_id'] = array();
			}
		}

		/**
		 * Display the settings panel
		 *
		 * @param      $wrapper
		 * @param null $errors
		 *
		 * @return void
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			// Just load the regular settings panel:
			parent::displaySettingsPanel($wrapper, $errors);

			$fieldset = new XMLElement('div', null, array('class' => 'two columns'));

			// Parent created
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields[' . $this->get('sortorder') . '][show_created]', 'yes', 'checkbox');
			if ($this->get('show_created') == 1) {
				$input->setAttribute('checked', 'checked');
			}
			$label->setValue(__('<br />%s Only show entries created by the parent entry', array($input->generate())));
			$fieldset->appendChild($label);

			// View picker
			$label   = Widget::Label(__('View'), null, 'column');
			$options = array();
			$files   = glob(EXTENSIONS . '/selectbox_link_field_plus/views/view.*.php');
			foreach ($files as $file) {
				require_once($file);
				$handle     = str_replace(array('view.', '.php'), '', basename($file));
				$class_name = 'SBLPView_' . ucfirst($handle);
				/** @var $view SBLPView */
				$view      = new $class_name;
				$options[] = array($handle, $this->get('view') == $handle, $view->getName());
			}
			$label->appendChild(Widget::Select('fields[' . $this->get('sortorder') . '][view]', $options));
			$fieldset->appendChild($label);

			$wrapper->insertChildAt(4, $fieldset);

			$fieldset = new XMLElement('div', null, array('class' => 'three columns'));

			// Create button
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields[' . $this->get('sortorder') . '][enable_create]', 'yes', 'checkbox');
			if ($this->get('enable_create') == 1 || $this->get('enable_create') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			$label->setValue(__('<br />%s Enable Create button', array($input->generate())));
			$fieldset->appendChild($label);

			// Edit button
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields[' . $this->get('sortorder') . '][enable_edit]', 'yes', 'checkbox');
			if ($this->get('enable_edit') == 1 || $this->get('enable_edit') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			$label->setValue(__('<br />%s Enable Edit button', array($input->generate())));
			$fieldset->appendChild($label);

			// Delete button
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields[' . $this->get('sortorder') . '][enable_delete]', 'yes', 'checkbox');
			if ($this->get('enable_delete') == 1 || $this->get('enable_delete') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			$label->setValue(__('<br />%s Enable Delete button', array($input->generate())));
			$fieldset->appendChild($label);

			$wrapper->insertChildAt(5, $fieldset);
		}

		/**
		 * Save the settings panel
		 *
		 * @return bool
		 */
		public function commit() {
			if (!parent::commit()) {
				return false;
			}

			$id = $this->get('id');

			if ($id === false) {
				return false;
			}

			if ($this->get('related_field_id') != '') {
				$settings['related_field_id'] = $this->get('related_field_id');
			}
			$settings['allow_multiple_selection'] = $this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no';
			$settings['show_association']         = $this->get('show_association') == 'yes' ? 'yes' : 'no';
			$settings['limit']                    = max(1, (int) $this->get('limit'));
			$settings['related_field_id']         = implode(',', $this->get('related_field_id'));
			$settings['view']                     = $this->get('view');
			$settings['show_created']             = $this->get('show_created') == 'yes' ? 1 : 0;
			$settings['enable_create']            = $this->get('enable_create') == 'yes' ? 1 : 0;
			$settings['enable_edit']              = $this->get('enable_edit') == 'yes' ? 1 : 0;
			$settings['enable_delete']            = $this->get('enable_delete') == 'yes' ? 1 : 0;

			FieldManager::saveSettings($id, $settings);

			SectionManager::removeSectionAssociation($id);

			foreach ($this->get('related_field_id') as $field_id) {
				SectionManager::createSectionAssociation(null, $id, $field_id, $this->get('show_association') == 'yes' ? true : false);
			}

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/* Publish  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Display the publish panel
		 *
		 * @param XMLElement $wrapper
		 * @param null       $data
		 * @param null       $error
		 * @param null       $prefix
		 * @param null       $postfix
		 * @param null       $entry_id
		 *
		 * @return void
		 */
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			Extension_Selectbox_Link_Field_Plus::appendAssets();

			$fieldname = 'fields' . $prefix . '[' . $this->get('element_name') . ']' . $postfix;
			if ($this->get('allow_multiple_selection') == 'yes') {
				$fieldname .= '[]';
			}
			$label = Widget::Label($this->get('label'));

			// Load the correct View:
			require_once(EXTENSIONS . '/selectbox_link_field_plus/views/view.' . $this->get('view') . '.php');
			$class_name = 'SBLPView_' . ucfirst($this->get('view'));
			/** @var $view SBLPView */
			$view = new $class_name;

			$view_wrapper = new XMLElement('div', null, array(
				'id'      => 'sblp-view-' . $this->get('id'),
				'class'   => 'sblp-view sblp-view-' . $view->getHandle(),
				'data-id' => $this->get('id')
			));

			// edge case when new entry, required field but can't select entries
			if ($this->get('show_created') === '1' && is_null($entry_id)) {
				$view_wrapper->appendChild(new XMLElement('p', __('This field will be enabled after you create the entry.'), array('class' => 'help')));
			}
			else {
				// Create new
				$view->generateCreate($label, $this, $entry_id);

				// Find entries
				$entry_ids = array();
				if (!is_null($data['relation_id'])) {
					if (!is_array($data['relation_id'])) {
						$entry_ids = array($data['relation_id']);
					}
					else {
						$entry_ids = array_values($data['relation_id']);
					}
				}

				$options = array();
				if ($this->get('required') != 'yes') {
					$options[] = array(null, false, null);
				}

				$states = $this->findOptions($entry_ids);

				if (!empty($states)) {
					foreach ($states as $s) {
						$group = array('label' => $s['name'], 'options' => array(), 'id' => $s['section']);
						foreach ($s['values'] as $id => $v) {
							if ($this->get('show_created') == 1) {
								// Check if this entry is created by it's parent:
								if (Symphony::Database()->fetchVar('count', 0, sprintf('SELECT COUNT(*) AS `count` FROM `tbl_sblp_created` WHERE `entry_id` = %d AND `created_id` = %d;', $entry_id, $id)) == 0) {
									// skip this one:
									continue;
								}
							}
							$group['options'][] = array($id, in_array($id, $entry_ids), General::sanitize($v));
						}
						$options[] = $group;
					}
				}

				$view->generateView($view_wrapper, $fieldname, $options, $this);
			}

			$field_wrapper = new XMLElement('div');
			$field_wrapper->appendChild($label);
			$field_wrapper->appendChild($view_wrapper);

			if (!is_null($error)) {
				$wrapper->appendChild(Widget::Error($field_wrapper, $error));
			}
			else {
				$wrapper->appendChild($field_wrapper);
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/* Input  */
		/*------------------------------------------------------------------------------------------------*/

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$result = parent::checkPostFieldData($data, $message, $entry_id);

			// if new entry && show_created, bypass required option
			if ($this->get('show_created') === '1'
				&& $result === self::__MISSING_FIELDS__
				&& is_null($entry_id)
			) {
				$message = '';

				return self::__OK__;
			}

			return $result;
		}



		/*------------------------------------------------------------------------------------------------*/
		/* Output  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Override the appendFormattedElement to put the entries in the datasource in the same order as they were stored
		 * for sorting purposes.
		 *
		 * @param      $wrapper
		 * @param      $data
		 * @param bool $encode
		 *
		 * @return
		 */
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false) {
			if (!is_array($data) || empty($data) || is_null($data['relation_id'])) {
				return;
			}

			$list = new XMLElement($this->get('element_name'));

			if (!is_array($data['relation_id'])) {
				$data['relation_id'] = array($data['relation_id']);
			}

			$related_values = $this->findRelatedValues($data['relation_id']);

			// This is the only adjustment from it's native function:
			$new_related_values = array();
			foreach ($related_values as $relation) {
				$new_related_values[$relation['id']] = $relation;
			}
			$related_values = array();
			foreach ($data['relation_id'] as $id) {
				$related_values[] = $new_related_values[$id];
			}
			// End of the only adjustment //

			foreach ($related_values as $relation) {
				$item = new XMLElement('item');
				$item->setAttribute('id', $relation['id']);
				$item->setAttribute('handle', Lang::createHandle($relation['value']));
				$item->setAttribute('section-handle', $relation['section_handle']);
				$item->setAttribute('section-name', General::sanitize($relation['section_name']));
				$item->setValue(General::sanitize($relation['value']));
				$list->appendChild($item);
			}

			$wrapper->appendChild($list);
		}



		public function appendFieldSchema(XMLElement $wrapper) {
		}



		/*------------------------------------------------------------------------------------------------*/
		/* Utilities */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Find related sections.
		 *
		 * @return array
		 */
		public function findRelatedSections() {
			$related_fields   = $this->get('related_field_id');
			$related_sections = array();
			foreach ($related_fields as $id) {
				$field = FieldManager::fetch($id);
				if (is_array($field)) {
					$field = current($field);
				}
				if (!$field instanceof Field) {
					continue;
				}

				$related_sections[] = SectionManager::fetch($field->get('parent_section'));
			}

			return $related_sections;
		}

		protected function findRelatedValues(array $relation_id = array()) {
			// 1. Get the field instances from the SBL's related_field_id's
			// FieldManager->fetch doesn't take an array of ID's (unlike other managers)
			// so instead we'll instead build a custom where to emulate the same result
			// We also cache the result of this where to prevent subsequent calls to this
			// field repeating the same query.
			$where = ' AND id IN (' . implode(',', $this->get('related_field_id')) . ') ';
			$hash  = md5($where);
			if (!isset(self::$cache[$hash]['fields'])) {
				$fields = FieldManager::fetch(null, null, 'ASC', 'sortorder', null, null, $where);
				if (!is_array($fields)) {
					$fields = array($fields);
				}

				self::$cache[$hash]['fields'] = $fields;
			}
			else {
				$fields = self::$cache[$hash]['fields'];
			}

			if (empty($fields)) {
				return array();
			}

			// 2. Find all the provided `relation_id`'s related section
			// We also cache the result using the `relation_id` as identifier
			// to prevent unnecessary queries
			$relation_id = array_filter($relation_id);
			if (empty($relation_id)) {
				return array();
			}

			$hash = serialize($relation_id) . $this->get('element_name');
			if (class_exists('FLang')) {
				$hash .= FLang::getLangCode();
			}
			$hash = md5($hash);

			if (!isset(self::$cache[$hash]['relation_data'])) {
				$relation_ids = Symphony::Database()->fetch(sprintf("
					SELECT e.id, e.section_id, s.name, s.handle
					FROM `tbl_entries` AS `e`
					LEFT JOIN `tbl_sections` AS `s` ON (s.id = e.section_id)
					WHERE e.id IN (%s)
					",
					implode(',', $relation_id)
				));

				// 3. Group the `relation_id`'s by section_id
				$section_ids  = array();
				$section_info = array();
				foreach ($relation_ids as $relation_information) {
					$section_ids[$relation_information['section_id']][] = $relation_information['id'];

					if (!array_key_exists($relation_information['section_id'], $section_info)) {
						$section_info[$relation_information['section_id']] = array(
							'name'   => $relation_information['name'],
							'handle' => $relation_information['handle']
						);
					}
				}

				// 4. Foreach Group, use the EntryManager to fetch the entry information
				// using the schema option to only return data for the related field
				$relation_data = array();
				foreach ($section_ids as $section_id => $entry_data) {
					$schema = array();
					// Get schema
					foreach ($fields as $field) {
						if ($field->get('parent_section') == $section_id) {
							$schema = array($field->get('element_name'));
							break;
						}
					}

					$section = SectionManager::fetch($section_id);
					EntryManager::setFetchSorting($section->getSortingField(), $section->getSortingOrder());
					$entries = EntryManager::fetch(array_values($entry_data), $section_id, null, null, null, null, false, true, $schema);

					foreach ($entries as $entry) {
						$field_data = $entry->getData($field->get('id'));

						if (is_array($field_data) === false || empty($field_data)) {
							continue;
						}

						// The field is exportable:
						if (
							$field instanceof ExportableField
							&& in_array(ExportableField::UNFORMATTED, $field->getExportModes())
						) {
							$value = $field->prepareExportValue(
								$field_data, ExportableField::UNFORMATTED, $entry->get('id')
							);
						}

						// Nasty hack:
						else {
							$value = $field->getParameterPoolValue(
								$field_data, $entry->get('id')
							);
						}

						$relation_data[] = array(
							'id'             => $entry->get('id'),
							'section_handle' => $section_info[$section_id]['handle'],
							'section_name'   => $section_info[$section_id]['name'],
							'value'          => $value
						);
					}
				}

				self::$cache[$hash]['relation_data'] = $relation_data;
			}
			else {
				$relation_data = self::$cache[$hash]['relation_data'];
			}

			// 6. Return the resulting array containing the id, section_handle, section_name and value
			return $relation_data;
		}
	}
