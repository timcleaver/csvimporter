<?php
	/**
	 * Construct the csv index page. The index page lists the known csv importers
	 * and allows for their deletion. It also facilitates the addition of new
	 * importers. Importer discovery and deletion is handled via the csv importer
	 * manager class.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 * @author Timothy Cleaver
	 * @version 0.0.1
	 */
	class CsvImporterIndexer {
		/**
		 * The number of mappings to show in the table.
		 */
		const NUM_MAPPINGS = 3;

		/**
		 * Accessor to the index table columns structure. We use a function rather
		 * than a variable as php cannot declare and enforce immutable arrays.
		 *
		 * @return array
		 *	an array containing the column names.
		 */
		protected function columns() {
			return array('name', 'file', 'section', 'mappings', 'modified', 'author');
		}

		/**
		 * Construct the html representation of the importers index and append it
		 * to the input page.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the index should be rendered.
		 * @param array $errors
		 *	the set of errors to pass on to the user.
		 */
		public function index($page, $errors) {
			$page->setPageType('table');
			$page->addStylesheetToHead(URL . '/extensions/csvimporter/assets/csvimporter.css', 'screen', 100);
			$page->setTitle(__('Symphony') . ' &ndash; ' . __('CSV Importers'));
			// make sure the index will allow the upload of files.
			$page->Form->setAttribute('enctype', 'multipart/form-data');

			$this->status($page, $errors);

			$page->appendSubheading(__('CSV Importers'), Widget::Anchor(
				__('Create New'), "{$page->_Parent->getCurrentPageURL()}new/",
				__('Create a new CSV Importer'), 'create button'
			));
			// add the table of discovered importers
			$page->Form->appendChild(Widget::Table($this->tableHead($page), null, $this->tableBody($page)));
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$actions->appendChild(Widget::Select('with-selected', array(
					array(null, false, 'With Selected...'),
					array('delete', false, 'Delete', 'confirm'),
					array('run', false, 'Run')
				)
			));
			$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$page->Form->appendChild($actions);
		}

		/**
		 * Add any alerts to the page to indicate the status of previous actions.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the alerts should be rendered.
		 * @param array $errors
		 *	the error messages to alert the user to.
		 */
		private function status($page, $errors) {
			if (!isset($errors)) {
				return;
			}
			foreach ($errors as $error) {
				$page->pageAlert(__($error), Alert::ERROR);
			}
		}

		/**
		 * Create the formatted heading of the table to display the csv file importers.
		 * The column names are sourced from the columns method. It is assumed that all
		 * columns are sortable.
		 *
		 * @param AdministrationPage page
		 *	the page onto which the table head is to be rendered.
		 * @return Widget
		 *	the constructed table header widget.
		 */
		protected function tableHead($page) {
			$tableHeader = array();
			foreach ($this->columns() as $column) {
				$tableHeader[] = $this->columnHeader($page, $column);
			}
			return Widget::TableHead($tableHeader);
		}

		/**
		 * Create the html formatted header of a given column.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the column header should be rendered.
		 * @param string $column
		 *	the name of the column to create.
		 * @return Widget
		 *	the html widget to display the column header.
		 */
		protected function columnHeader($page, $column) {
			// we don't facilitate sorting by mappings
			if ($column == 'mappings') {
				return array('Mappings', 'col');
			}
			// construct the url for this column header. if the current table sort is this column
			// then the search direction is descending. otherwise it is ascending.
			if($_GET['sort'] == $column && $_GET['order'] == 'asc') {
				$order = 'desc';
			} else {
				$order = 'asc';
			}
			$url = $this->generateURL($page, array('sort' => $column, 'order' => $order));
			$anchor = Widget::Anchor(ucwords($column), $url, __(sprintf("Sort by %s column in %s order", $column, ( $order == 'desc' ? 'descending' : 'ascending' ))));
			if ($_GET['sort'] == $column) {
				$anchor->setAttribute('class', 'active');
			}
			return array($anchor, 'col');
		}

		/**
		 * Create the html formatted table body.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the table data is to be rendered.
		 * @return Widget
		 *	the html widget that contains the table body.
		 */
		protected function tableBody($page) {
			$csvImporterManager = new CsvImporterManager($_Parent);
			$importers = $this->sort($csvImporterManager->listAll(), $_GET['sort'], $_GET['order'] == 'desc');
			if (!is_array($importers) or empty($importers)) {
				return Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', null, count($this->columns()))));
			}
			$rows = array();
			foreach($importers as $importer) {
				$rows[] = $this->tableRow($page, $importer);
			}
			return Widget::TableBody($rows);
		}

		/**
		 * Sort the importers according to the input column and order.
		 *
		 * @param array $importers
		 *	the array of importers to sort.
		 * @param string $column
		 *	the name of the column by which to sort the importers
		 * @param boolean $descending
		 *	true if the importers are to be sorted in descending order,
		 *	false otherwise.
		 * @return array
		 *	the sorted array of importers.
		 */
		private function sort($importers, $column, $descending) {
			$sorter = null;
			switch ($column) {
				case 'file': $sorter = create_function('$a, $b', 'return strcmp($a[\'about\'][\'file\'], $b[\'about\'][\'file\']);'); break;
				case 'section': $sorter = create_function('$a, $b', 'return strcmp($a[\'section\'][\'name\'], $b[\'section\'][\'name\']);'); break;
				case 'modified':$sorter = create_function('$a, $b', 'return strtotime($a[\'about\'][\'updated\']) - strtotime($b[\'about\'][\'updated\']);'); break;
				case 'author':$sorter = create_function('$a, $b', 'return strcmp($a[\'about\'][\'author\'][\'name\'], $b[\'about\'][\'author\'][\'name\']);'); break;
				// since name is the default we use switch fallthrough for both cases.
				case 'name':
				default:$sorter = create_function('$a, $b', 'return strcmp($a[\'about\'][\'name\'], $b[\'about\'][\'name\']);'); break;
			}
			usort($importers, $sorter);
			if ($descending) {
				$importers = array_reverse($importers);
			}
			return $importers;
		}

		/**
		 * Create the html formatted table row for a given csv importer.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the table data is to be rendered.
		 * @param array $importer
		 *	an array of metadata pertaining to a given importer.
		 * @return Widget
		 *	the html widget that represents the formatted importer.
		 */
		protected function tableRow($page, $importer) {
			$row = array();
			foreach($this->columns() as $column) {
				$row[] = $this->tableData($page, $column, $importer);
			}
			return Widget::TableRow($row);
		}

		/**
		 * Call the correct table data formatting function for the input column.
		 *
		 * @param AdministrationPage $page
		 *	the page onto which the table data is to be rendered.
		 * @param string $column
		 *	the column to create the format data for.
		 * @param array $data
		 *	the data from which to source the values to format.
		 * @return Widget
		 *	the formatted column.
		 */
		protected function tableData($page, $column, $importer) {
			// map the columns to their formatting functions
			switch($column) {
				case 'name': return $this->tableDataName($page, $importer);
				case 'file': return $this->tableDataFile($importer);
				case 'section': return $this->tableDataSection($importer);
				case 'mappings': return $this->tableDataMappings($importer);
				case 'modified': return $this->tableDataModified($importer);
				case 'author': return $this->tableDataAuthor($importer);
			}
			// if nothing is supplied for the column, return a label containing ?
			return Widget::TableData("?");
		}

		/**
		 * Format the name of the importer as a html link element.
		 *
		 * @param AdministrationPage $page
		 *	the page relative to which the name table data should be rendered as a link.
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataName($page, $importer) {
			$name = Widget::TableData(Widget::Anchor($importer['about']['name'], "{$page->_Parent->getCurrentPageURL()}edit/{$importer['about']['name']}/"));
			// in order to select the items that the "with selected" action will operate
			// on when the index form is committed we need to add a checkbox to each of
			// the entries
			$name->appendChild(Widget::Input("items[{$importer['about']['name']}]", null, 'checkbox'));
			return $name;
		}

		/**
		 * Format the modified date of the importer as a html table data element.
		 *
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataModified($importer) {
			return Widget::TableData(DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($importer['about']['updated'])));
		}

		/**
		 * Format the included section of the importer as a html table data element.
		 *
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataSection($importer) {
			if (!isset($importer['section'])) {
				return Widget::TableData('None');
			}
			return Widget::TableData($importer['section']['name']);
		}

		/**
		 * Format the file of the importer as a html link element.
		 *
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataFile($importer) {
			if (!isset($importer['source'])) {
				return Widget::TableData('None');
			}
			$data = Widget::TableData($importer['source']['name']);
			$data->appendChild(Widget::Input("fields[{$importer['about']['name']}][source]", null, 'file'));
			return $data;
		}

		/**
		 * Format the author of the importer as a html element. If the author has
		 * supplied their email address then this will be formatted as a mailto:
		 * link.
		 *
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataAuthor($importer) {
			if (isset($importer['about']['author']['email'])) {
				return Widget::TableData(Widget::Anchor($importer['about']['author']['name'], 'mailto: ' . $importer['about']['author']['email']));
			}
			return Widget::TableData($importer['about']['author']['name']);
		}

		/**
		 * Format the set of mappings from columns to fields.
		 *
		 * @param array $importer
		 *	the array to source the data from.
		 * @return Widget
		 *	the resulting html widget.
		 */
		protected function tableDataMappings($importer) {
			$fieldManager = new FieldManager($this);
			// use a definition list to map columns to fields.
			$definitions = new XMLElement('dl');
			foreach (array_slice($importer['mappings'], 0, self::NUM_MAPPINGS) as $mapping) {
				$field = $fieldManager->fetch($mapping['field'], $importer['section']['id']);
				$headers = $this->headers($importer, $importer['header']);
				$definitions->appendChild(new XMLElement('dt', $headers[$mapping['column']]));
				$definitions->appendChild(new XMLElement('dd', $field->get('label')));
			}
			// if there are more than NUM_MAPPINGS elements mapped then add a truncated class to
			// the definition list
			if (count($importer['mappings']) > self::NUM_MAPPINGS) {
				$definitions->setAttribute('class', 'truncated');
			}
			return Widget::TableData($definitions);
		}

		/**
		 * Extract the column headings from the csv file. Copied from importer editor.
		 * Needs better abstraction.
		 *
		 * @param array $importer
		 *	the importer from which to extract the source file information
		 * @param boolean existing
		 *	true if the first line should be used as the header values, false if
		 *	column names should be autogenerated. this is independent of the current
		 *	settings of the importer.
		 * @return array
		 *	the array of column headings. if there is a problem reading the file or
		 *	parsing its contents, the result will be an empty array.
		 */
		protected function headers($importer, $header) {
			$data = array();
			if (($handle = fopen($importer['source']['path'], "r")) !== FALSE) {
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
		 * Construct the url of the current page. Any elements of the argument
		 * string whose keys match those in the input array will be updated.
		 * Any others will remain in the url. Any elements of the input array
		 * that are not already defined by the current url are appended to it.
		 *
		 * @param AdministrationPage $page
		 *	the current page from which the generated links should be relative.
		 * @param array[string]string $urlarguments
		 *	the url arguments to modify or add to the current url.
		 * @return string
		 *	the resulting url.
		 */
		protected function generateURL($page, $urlarguments=array()) {
			// create a function that will transform key value pairs into a string key=value
			$combiner = create_function('$key, $value', 'return $key . "=" . $value;');
			// filter get to remove the arguments symphony uses internally. do this by getting the
			// difference between get and an input array containing the keys symphony uses.
			$getarguments = array_diff_key($_GET, array('symphony-page' => 0, 'mode' => 0));
			// merge the existing url arguments with the input arguments. because we are
			// using string keyed arrays, the values from $urlarguments will overwrite
			// those in $_GET.
			$newarguments = array_merge($getarguments, $urlarguments);
			// map the combiner function over the new arguments. must use
			// array_keys to supply the keys as an argument to the combiner function
			// join the key value strings using ampersand.
			return $page->_Parent->getCurrentPageURL() . '?' . implode('&amp;', array_map($combiner, array_keys($newarguments), $newarguments));
		}
	}


