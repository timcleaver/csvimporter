<?php
	/**
	 * An extension designed to import selected values from a comma separated
	 * value (csv) file into defined symphony sections.
	 *
	 * @package extensions
	 * @subpackage cvsimporter
	 * @author Timothy Cleaver
	 * @version 0.0.1
	 */
	class Extension_CsvImporter extends Extension {
		/**
		 * Accessor to the metadata of this extension.
		 *
		 * @return array
		 *	a structured array of metadata pertaining to this extension.
		 */
		public function about() {
			return array(
				'name'			=> 'CSV Importer',
				'version'		=> '0.0.1',
				'release-date'	=> '2010-03-10',
				'author'		=> array(
					'name'			=> 'Timothy Cleaver'
				),
				'description' => 'Import data from comman separated value (CSV) documents directly into Symphony.'
			);
		}

		/**
		 * Access any customized navigation entries for this extension. Each entry
		 * in the returned array consists of the heading under which the entry is
		 * to be displayed, the name/display of the link to show and the url of the
		 * link to display when the navigation item is clicked on. The url is
		 * resolved relative to the directory in which the extension resides.
		 *
		 * @return array
		 *	an array of navigation entries.
		 */
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> 'Blueprints',
					'name'		=> 'CSV Importers',
					'link'		=> '/importers/'
				)
			);
		}
	}

