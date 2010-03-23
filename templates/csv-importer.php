<?php
	/**
	 * This class is automatically generated by the csv importer extension.
	 * It represents the details specific to a user defined csv importer.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 */
	class CsvImporter%s {
		/**
		 * Accessor to the data pertaining to this importer. Because we don't treat
		 * importers as object, but as a structured array, we provide a single accessor
		 * to the structured array.
		 *
		 * @return array
		 *	the importer data as an array of known structure.
		 */
		public function data() {
			return array(
				'about' =>
					array(
						'name'			=> %s,
						'author'		=> array(
							'name'			=> %s,
							'website'		=> %s,
							'email'			=> %s
						),
						'description'	=> %s,
						'file'			=> __FILE__,
						'created'		=> %s,
						'updated'		=> '%s',
						'version'		=> '%s'
					),
				'mappings'		=> %s,
				'section'		=> %s,
				'source'		=> %s
			);
		}
	}
	
?>
