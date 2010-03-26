<?php
	/**
	 * Class for the editing and addition of CSV importers.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 * @author Timothy Cleaver
	 * @version 0.0.1
	 */
	class CsvImporterEditor {
		/**
		 * The number of lines to display in an example.
		 */
		const EXAMPLE_ROWS = 3;

		/**
		 * The number of example columns to display in an example.
		 */
		const EXAMPLE_COLUMNS = 5;

		/**
		 * The importer on which the editor is operating.
		 */
		private $importer;

		/**
		 * The cache of the headers extracted from the file.
		 */
		private $headers;

		/**
		 * Construct an editor for a particular importer.
		 *
		 * @param array $importer
		 *	the current importer structure.
		 */
		public function __construct(array $importer) {
			$this->importer = $importer;
			// we need to cache both the versions with and without headers
			$this->headers = array(true => $this->headers(true), false => $this->headers(false));
		}

		/**
		 * Construct the view to edit a new importer.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the editor is to be rendered.
		 */
		public function create($page) {
			$page->setPageType('form');
			// because we are uploading a file from the users machine, we must
			// ensure that it can be accepted by the form.
			$page->Form->setAttribute('enctype', 'multipart/form-data');
			$page->addStylesheetToHead(URL . '/extensions/csvimporter/assets/csvimporter-editor.css', 'screen', 100);
			$page->addStylesheetToHead(URL . '/symphony/assets/tables.css', 'screen', 100);
			$page->addScriptToHead(URL . '/extensions/csvimporter/assets/csvimporter.js', 101);

			$page->setTitle(__('Symphony') . ' &ndash; ' . __('New CSV Importer'));
			$page->appendSubheading(__('New CSV Importer'));
			
			$page->Form->appendChild($this->essentials());
			$page->Form->appendChild($this->source());
			// if a file has been specified then the user can select a
			// section
			if (isset($this->importer['source']['name'])) {
				$page->Form->appendChild($this->destination());
				$page->Form->appendChild($this->mappings());
			}
			$page->Form->appendChild($this->footer(false));
		}

		/**
		 * Construct the view to edit an existing importer.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the editor is to be rendered.
		 * @param string status (optional)
		 *	an optional status which indicates the previous action taken.
		 * @param array $errors (optional)
		 *	an optional set of errors that must be included on the edit page.
		 *	this defaults to null.
		 */
		public function edit($page, $status, $errors=null) {
			$page->setPageType('form');
			// because we are uploading a file from the users machine, we must
			// ensure that it can be accepted by the form.
			$page->Form->setAttribute('enctype', 'multipart/form-data');
			$page->addStylesheetToHead(URL . '/extensions/csvimporter/assets/csvimporter-editor.css', 'screen', 100);
			$page->addStylesheetToHead(URL . '/symphony/assets/tables.css', 'screen', 100);
			$page->addScriptToHead(URL . '/extensions/csvimporter/assets/csvimporter.js', 101);

			$page->setTitle(__('Symphony') . ' &ndash; ' . __('Edit CSV Importer'));
			$page->appendSubheading(__('Edit CSV Importer'));

			$this->valid($errors);
			$this->status($page, $status, $errors);
			$page->Form->appendChild($this->essentials($errors));
			$page->Form->appendChild($this->source($errors));
			// if a file has been specified then the user can select a
			// section
			if (isset($this->importer['source']['name'])) {
				$page->Form->appendChild($this->destination());
				$page->Form->appendChild($this->mappings());
			}
			$page->Form->appendChild($this->footer(true));
		}

		/**
		 * Validate the importer data.
		 *
		 * @param array $errors (optional)
		 *	the optional error array to populate with validation errors.
		 * @return boolean
		 *	true if the post data validated successfully, false otherwise.
		 */
		protected function valid(&$errors=null) {
			// name
			if (!isset($this->importer['about']['name']) or empty($this->importer['about']['name'])) {
				$errors['name'] = 'This importer must have a name.';
			}
			// file info
			if (empty($this->importer['source'])) {
				$errors['source'][] = 'A saved importer must have file information';
			}
			if (!isset($this->importer['source']['name'])) {
				$errors['source'][] = 'A saved importer must have a viewable filename';
			}
			if (!isset($this->importer['source']['path'])) {
				$errors['source'][] = 'A saved importer must have a valid file path';
			}
			if (!file_exists($this->importer['source']['path'])) {
				$errors['source'][] = 'The file path specified does not exist';
			}
			if (!is_readable($this->importer['source']['path'])) {
				$errors['source'][] = 'The file path specified canno be read';
			}
			// if there are no errors then the post data is valid.
			return empty($errors);
		}

		/**
		 * Construct any status messages and add them to the page. Status consists of
		 * validation error messages or the success messages of the previous action.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the status should be shown.
		 * @param string status
		 *	the previous action status.
		 * @param array $errors (optional)
		 *	an optional array of errors.
		 */
		protected function status($page, $status, $errors=null) {
			if (!empty($errors)) {
				$page->pageAlert(__('An error occurred while processing this form <a href="#error">See below for defaults.</a>'), Alert::ERROR);
			}
			$arguments = array(
							__('CSV Importer'), 
							DateTimeObj::get(__SYM_TIME_FORMAT__),
							URL . '/symphony/extension/csvimporter/importers/new/',
							URL . '/symphony/extension/csvimporter/importers/',
							__('CSV Importers')
						);
			switch ($status) {
				case 'save': $page->pageAlert(__('%1$s updated at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>', $arguments), Alert::SUCCESS); break;
				case 'created': $page->pageAlert(__('%1$s created at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>', $arguments), Alert::SUCCESS); break;
			}
		}

		/**
		 * Construct the html representation of the essential elements of the
		 * importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function essentials($errors=null) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			$group->appendChild($this->name($errors));
			$group->appendChild($this->description($errors));
			// add the author info if there is any
			if (isset($this->importer['about']['author'])) {
				$group->appendChild($this->author());
			}
			// add the file information is there is any
			if (isset($this->importer['about']['file'])) {
				$group->appendChild($this->file());
			}
			// add the creation date if there is any
			if (isset($this->importer['about']['created'])) {
				$group->appendChild($this->created());
			}
			$fieldset->appendChild($group);
			return $fieldset;
		}

		/**
		 * Construct the html input for the name of this csv importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function name($errors=null) {
			$name = Widget::Label(__('Name'));
			$name->appendChild(Widget::Input('fields[about][name]', $this->importer['about']['name']));
			
			if (isset($errors['name'])) {
				$name = Widget::wrapFormElementWithError($name, $errors['name']);
			}
			return $name;
		}

		/**
		 * Construct the html input for the description of this csv importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function description($errors=null) {
			$description = Widget::Label(__('Description <i>Optional</i>'));
			$description->appendChild(Widget::Input('fields[about][description]', $this->importer['about']['description']));
			
			if (isset($errors['description'])) {
				$description = Widget::wrapFormElementWithError($description, $errors['description']);
			}
			return $description;
		}

		/**
		 * Construct the hidden author details so that when a form is submitted
		 * the author data is saved also.
		 *
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function author() {
			$group = new XMLElement('p');
			$group->appendChild(Widget::Input('fields[about][author][name]', $this->importer['about']['author']['name'], 'hidden'));
			$group->appendChild(Widget::Input('fields[about][author][website]', $this->importer['about']['author']['website'], 'hidden'));
			$group->appendChild(Widget::Input('fields[about][author][email]', $this->importer['about']['author']['email'], 'hidden'));
			return $group;
		}

		/**
		 * Construct the hidden file details so that when a form is submitted
		 * the file data is saved also.
		 *
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function file() {
			$group = new XMLElement('p');
			$group->appendChild(Widget::Input('fields[about][file]', $this->importer['about']['file'], 'hidden'));
			return $group;
		}

		/**
		 * Construct the hidden created time details so that when a form is submitted
		 * the created time data is saved also.
		 *
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function created() {
			$group = new XMLElement('p');
			$group->appendChild(Widget::Input('fields[about][created]', $this->importer['about']['created'], 'hidden'));
			return $group;
		}

		/**
		 * Construct the html representation of the source elements of the importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function source($errors=null) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Source')));

			$fieldset->appendChild($this->filename($errors));
			// only add the help and setter for header information if there is no file already set
			if (!isset($this->importer['source']['name'])) {
				$fieldset->appendChild($this->filenameHelp());
			}
			$fieldset->appendChild($this->filenameHeader($this->importer));
			// only add the example if a file has been uploaded
			if (isset($this->importer['source']['name'])) {
				$fieldset->appendChild($this->filenameExamples());
			}
			return $fieldset;
		}

		/**
		 * Construct the html representation of the source element of the
		 * importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function filename($errors=null) {
			$label = Widget::Label(__('File'));
			if (isset($this->importer['source']['name'])) {
				$label->setValue(__('File: ' .  $this->importer['source']['name']));
				$label->appendChild(Widget::Input('fields[source][name]', $this->importer['source']['name'], 'hidden'));
				$label->appendChild(Widget::Input('fields[source][tmp_name]', $this->importer['source']['path'], 'hidden'));
			} else {
				$label->appendChild(Widget::Input('fields[source]', null, 'file'));
			}

			if (isset($errors['source'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['source']);
			}
			return $label;
		}

		/**
		 * Construct the html to query the user as to whether the input file contains
		 * column headers.
		 *
		 * @return Widget
		 *	the html widget to query the user for the header status of the file.
		 */
		protected function filenameHeader() {
			$label = Widget::Label(__('This file contains an initial line of header information'));
			$input = Widget::Input('fields[header]', 'true', 'checkbox');
			if ($this->importer['source']['header'] == true) {
				$input->setAttribute('checked', 'checked');
			};
			$input->setAttribute('id', 'csv-header-toggle');
			$label->appendChild($input);
			return $label;
		}

		/**
		 * Construct the alternative parsing examples.
		 *
		 * @return Widget
		 *	the html list of the html formatted examples.
		 */
		protected function filenameExamples() {
			$examples = new XMLElement('ol');
			$examples->setAttribute('class', 'csv-example-list');
			// generate an example for both with and without the header set
			foreach (array(true, false) as $header) {
				$example = new XMLElement('li');
				$example->setAttribute('class', "csv-example-" . ($header ? 'true' : 'false'));
				$label = Widget::Label(__($header ? 'With Header' : 'Without Header'));
				$label->appendChild($this->filenameExample($header));
				$example->appendChild($label);
				$examples->appendChild($example);
			}
			return $examples;
		}

		/**
		 * Construct a table which demonstrates an example extraction of the csv data
		 * from the input file. This will allow the user to map the columns more successfully
		 * to fields since they can see what each solumn corresponds to.
		 *
		 * @param boolean header
		 *	if true the example is generated as though the file has a header, if false it
		 *	autogenerates column headers. defaults to false.
		 * @return Widget
		 *	the constructed example.
		 */
		protected function filenameExample($header) {
			$data = array();
			if (($handle = fopen($this->importer['source']['path'], "r")) !== false) {
				// if the user has stated that there is a header, throw away the first line
				if ($header) {
					fgets($handle);
				}
				// grab up to the first/next 3 lines of the file (if there are fewer than
				// three then it grabs what is there)
				for ($count = 0; (($row = fgetcsv($handle, 1000, ",")) !== false and $count < self::EXAMPLE_ROWS); $count++) {
					// if the row is longer that the example then truncate it and append elipses
					if (count($row) > self::EXAMPLE_COLUMNS) {
						$row = array_slice($row, 0, self::EXAMPLE_COLUMNS);
						$row[] = '&#x2026;';
					}
					$data[] = Widget::TableRow(array_map(array('Widget', 'TableData'), $row));
				}
				// add a row of elipses if we truncated the example
				if ($count >= self::EXAMPLE_ROWS) {
					$data[] = Widget::TableRow(array_map(array('Widget', 'TableData'), array_fill(0, min(count($row), self::EXAMPLE_COLUMNS), '&#x22ee;')));
				}
				// make sure to close the file.
				fclose($handle);
			}
			$headers = $this->headers[$header];
			if (count($headers) > self::EXAMPLE_COLUMNS) {
				$headers = array_slice($headers, 0, self::EXAMPLE_COLUMNS);
				$headers[] = '&#x2026;';
			}
			$headers = array_map(create_function('$header', 'return array($header, "", "");'), $headers);
			return Widget::Table(Widget::TableHead($headers), null, Widget::TableBody($data));
		}

		/**
		 * Construct the html representation of the source url help element of the
		 * importer.
		 *
		 * @param array $errors (optional)
		 *	an optional error array as constructed by validating the _POST data.
		 * @return Widget
		 *	the html representation of the essential elements.
		 */
		protected function filenameHelp() {
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Select the file of the comma separated value (CSV) document you want to process.'));
			return $help;
		}

		/**
		 * Construct the footer of the page.
		 *
		 * @param boolean $deletable
		 *	true if this entry is deletable, false otherwise.
		 * @return Widget
		 *	the footer html widget.
		 */
		protected function footer($deletable) {
			$footer = new XMLElement('div');
			$footer->setAttribute('class', 'actions');
			$footer->appendChild($this->save());
			$footer->appendChild($this->run());
			if ($deletable) {
				$footer->appendChild($this->delete());
			}
			return $footer;
		}

		/**
		 * Construct the save button. The label on this button changes depending
		 * on the stage of the form the user is currently at. At first it requests
		 * the user upload the file, then it requests the user select a section
		 * and finally it requests the user save the importer.
		 *
		 * @return Widget
		 *	the save button html widget.
		 */
		protected function save() {
			// if there is already a file uploaded use save importer instead
			if (isset($this->importer['source']['name'])) {
				return Widget::Input('action[save]', __('Save Importer'), 'submit', array('accesskey' => 's'));
			}
			return Widget::Input('action[upload]', __('Upload File'), 'submit', array('accesskey' => 's'));
		}

		/**
		 * Construct the run button.
		 *
		 * @return Widget
		 *	the save button html widget.
		 */
		protected function run() {
			$button = Widget::Input('action[run]', __('Run'), 'submit');
			$button->setAttribute('accesskey', 'r');
			$button->setAttribute('class', 'run');
			// the button is only enabled after the user has uploaded a file
			if (isset($this->importer['source']['name'])) {
				$button->setAttribute('disabled', 'disabled');
			}
			return $button;
		}

		/**
		 * Construct the delete button.
		 *
		 * @return Widget
		 *	the delete button html widget.
		 */
		protected function delete() {
			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array(
				'name'		=> 'action[delete]',
				'class'		=> 'confirm delete',
				'title'		=> __('Delete this XML Importer')
			));
			return $button;
		}

		/**
		 * Construct the destination component of the form.
		 *
		 * @return Widget
		 *	the section selection component.
		 */
		protected function destination() {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Destination')));
			
			$fieldset->appendChild($this->section($this->importer));
			return $fieldset;
		}

		/**
		 * Construct the section selection component of the form.
		 *
		 * @return Widget
		 *	the section selection component.
		 */
		protected function section() {
			$sectionManager = new SectionManager($this->_Parent);
			$options = array();
			foreach ($sectionManager->fetch(NULL, 'ASC', 'name') as $section) {
				$options[] = array($section->get('id'), $this->importer['section']['id'] == $section->get('id'), $section->get('name'));
			}
			$label = Widget::Label(__('Section'));
			$select = Widget::Select('fields[section]', $options);
			$select->setAttribute('id', 'csv-section-toggle');
			$label->appendChild($select);
			return $label;
		}

		/**
		 * Construct the mappings section of the form.
		 *
		 * @return Widget
		 *	the mappings component.
		 */
		protected function mappings() {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Mappings')));
			// because the user can dynamically set whether the first line of the file is
			// a header or not, we need to list the set of mappings for each alternative.
			$headings = new XMLElement('ol');
			$headings->setAttribute('class', 'csv-heading-list');
			foreach (array(true, false) as $heading) {
				$item = new XMLElement('li');
				$item->setAttribute('class', "csv-heading-" . ($heading ? 'true' : 'false'));
				$item->appendChild($this->sectionsMapping($heading));
				$headings->appendChild($item);
			}
			$fieldset->appendChild($headings);
			return $fieldset;
		}

		/**
		 * Construct the widget for a given heading selection.
		 *
		 * @param boolean header
		 *	true if the first line of the file defines the set of columns, false
		 *	otherwise.
		 * @return Widget
		 *	the mapping component.
		 */
		protected function sectionsMapping($header) {
			// because we are dynamically selecting the field drop down based on the selected
			// section, we must provide the mappings for each possible section, not any selected one.
			// the javascript will then show/hide the correct set of fields based on this.
			$sectionManager = new SectionManager($this->_Parent);
			$sections = new XMLElement('ol');
			$sections->setAttribute('class', 'csv-section-list');
			foreach ($sectionManager->fetch(NULL, 'ASC', 'name') as $section) {
				$item = new XMLElement('li');
				$item->setAttribute('class', "csv-section-{$section->get('id')}");
				$item->appendChild($this->sectionMapping($header, $section->get('id')));
				$sections->appendChild($item);
			}
			return $sections;
		}

		/**
		 * Construct the widget for a given section mapping.
		 *
		 * @param boolean header
		 *	true if the mapping should be constructed as though the first line of the 
		 *	file defines its heading, false otherwise.
		 * @param integer section
		 *	the section of the id for which we are constructing the set of mapping
		 *	inputs.
		 * @return Widget
		 *	the mapping component.
		 */
		protected function sectionMapping($header, $section) {
			// because we are generating the set of mappings dynamically, we store the
			// list of these as an ordered list. each entry is indexed by its position
			// in the ordered list and the "template" empty entry is at index -1.
			$list = new XMLElement('ol');
			$list->setAttribute('class', 'csv-mapping-duplicator');
			if (isset($this->importer['mappings']) and is_array($this->importer['mappings'])) {
				foreach ($this->importer['mappings'] as $index => $value) {
					$item = new XMLElement('li');
					$item->appendChild(new XMLElement('h4', __('Mapping')));
					$item->appendChild($this->mapping($header, $section, $index));
					$list->appendChild($item);
				}
			}
			// add the template entry
			$item = new XMLElement('li');
			$item->setAttribute('class', 'template');
			$item->appendChild(new XMLElement('h4', __('Mapping')));
			$item->appendChild(Widget::Input('fields[mapping][-1][field]', null, 'hidden'));
			$item->appendChild($this->mapping($header, $section, -1));
			$list->appendChild($item);
			return $list;
		}

		/**
		 * Construct a single mapping entry form component. This allows a user to
		 * select a column from the uploaded file and match it to one of the
		 * unmapped fields of the selected section.
		 *
		 * @param boolean header
		 *	true if the first line of the file is the source of the headers, false
		 *	otherwise.
		 * @param integer $section
		 *	the id of the section. because we are creating a set of mappings for all
		 *	possible sections, not just a selected one, we need to know the section
		 *	to use for this mapping.
		 * @param integer $index (optional)
		 *	the index of this mapping. because we are creating a dynamic number of
		 *	mappings, each is indexed. this is the index of the mapping to display.
		 *	this defaults to 0.
		 * @return Widget
		 *	the section selection component.
		 */
		protected function mapping($header, $section, $index=0) {
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			$group->appendChild($this->columns($header, $index));
			$group->appendChild($this->fields($section, $index));
			return $group;
		}

		/**
		 * Construct a selector for the columns in the csv file.
		 *
		 * @param integer $index
		 *	the index of this column field instance.
		 * @return Widget
		 *	the section selection component.
		 */
		protected function columns($header, $index) {
			$label = Widget::Label('Column');
			$options = array();
			foreach ($this->headers[$header] as $i => $column) {
				$options[] = array($i, $i == $this->importer['mappings'][$index]['column'], $column);
			}
			$label->appendChild(Widget::Select("fields[mapping][{$index}][column]", $options));
			return $label;
		}

		/**
		 * Extract the column headings from the csv file.
		 *
		 * @param boolean existing
		 *	true if the first line should be used as the header values, false if
		 *	column names should be autogenerated. this is independent of the current
		 *	settings of the importer.
		 * @return array
		 *	the array of column headings. if there is a problem reading the file or
		 *	parsing its contents, the result will be an empty array.
		 */
		protected function headers($header) {
			$data = array();
			if (!isset($this->importer['source']['path'])) {
				return $data;
			}
			if (($handle = fopen($this->importer['source']['path'], "r")) !== false) {
				$data = fgetcsv($handle, 1000, ",");
				// make sure to close the file.
				fclose($handle);
			}
			// if the user has said there are no headers in the file then we impose the headers
			// "column 1" ... "column n"
			if (!$header) {
				$data = array_map(create_function('$key', 'return "Column {$key}";'), array_keys($data));
			}
			return $data;
		}

		/**
		 * Construct a selector for the fields in the selected section.
		 *
		 * @param integer section
		 *	the id of the section to construct the field selector for.
		 * @param integer index
		 *	the index of this field field instance.
		 * @return Widget
		 *	the field selection component.
		 */
		protected function fields($section, $index) {
			$label = Widget::Label('Field');
			$options = array();
			$sectionManager = new SectionManager($this->_Parent);
			foreach ($sectionManager->fetch($section)->fetchFields() as $field) {
				$selected = false;
				if (isset($this->importer['mappings'])) {
					if (isset($this->importer['mappings'][$index])) {
						$selected = $field->get('id') == $this->importer['mappings'][$index]['field'];
					}
				}
				$options[] = array($field->get('id'), $selected, $field->get('label'));
			}
			$label->appendChild(Widget::Select("fields[mapping][{$index}][field]", $options));
			return $label;
		}
	}

