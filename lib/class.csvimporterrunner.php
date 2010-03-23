<?php
	/**
	 * Run an importer.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 * @author Timothy Cleaver
	 * @version 0.0.1
	 */
	class CsvImporterRunner {
		/**
		 * Run the importer.
		 *
		 * @param int $author
		 *	the id of the current author.
		 * @param array $importer
		 *	the array structure that defines the importer to run.
		 * @param array &$errors
		 *	the array to append any errors encountered during execution
		 *	to.
		 * @return boolean
		 *	true if the import was successful, false otherwise.
		 */
		public function run($author, $importer, &$errors) {
			$entryManager = new EntryManager($this);
			$fieldManager = new FieldManager($this);
			$sectionManager = new SectionManager($this);
			// ensure the section still exists
			if ($sectionManager->fetch($importer['section']['id']) == false) {
				$errors[] = sprintf('The destination section: %d:%s no longer exists',
					$importer['section']['id'],
					$importer['section']['name']);
				return;
			}
			if (($handle = fopen($importer['source']['path'], "r")) === false) {
				$errors[] = sprintf('Could not open csv file: %s for import', $importer['source']['path']);
				return;
			}
			// if the file has a header then throw away the first line
			if ($importer['source']['header'] == true) {
				fgets($handle);
			}
			// loop through the input file. make sure the line accounts for the
			// header if there is one
			for ($line = ($importer['source']['header'] ? 1 : 0); (($data = fgetcsv($handle, 1000, ",")) !== false); $line++) {
				// if the current line is a blank line then ignore it
				if ($data['0'] == null) {
					continue;
				}
				// construct the entry for this file row
				$values = array();
				foreach ($importer['mappings'] as $mapping) {
					$entry = $entryManager->create();
					$entry->set('section_id', $importer['section']['id']);
					$entry->set('author_id', $author);
					$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
					$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));
					$field = $fieldManager->fetch($mapping['field']);
					if ($field == false) {
						$errors[] = sprintf('The destination field: %d no longer exists', $mapping['field']);
						// don't leak the file handle
						fclose($handle);
						return;
					}
					if (!isset($data[$mapping['column']])) {
						$errors[] = sprintf('Could not insert value from column: %d from line: %d of file: %s',
							$mapping['column'], $line, $importer['source']['path']);
						continue;
					}
					$values[$field->get('element_name')] = $data[$mapping['column']];
				}
				// save the entry
				$entry->setDataFromPost($values, $error, true);
				$entry->commit();
			}
			// make sure to close the file.
			fclose($handle);
		}
	}

