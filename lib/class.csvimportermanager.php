<?php
	/**
	 * The CSV importer manager class is responsible for the identification
	 * of the installed importers on disk, the construction of each importer
	 * and placement into a pool of importers.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 * @author Timothy Cleaver.
	 */
	class CsvImporterManager extends Manager {
		/**
		 * Construct a new csv importer manager.
		 *
		 * @param mixed $parent
		 *	the parent of this.
		 */
		public function __construct($parent) {
			parent::__construct($parent);
		}

		/**
		 * Accessor to the paths that this manager will look into for installed
		 * importers. We use a function to ensure that the constructed array
		 * cannot be permanently modified by clients. This is necessary because
		 * php does not support constant immutable arrays.
		 *
		 * @return array
		 *	an array of file paths that this importer manager will search for
		 *	importers.
		 */
		protected function paths() {
			$extensionManager = new ExtensionManager($_Parent);
			$extensions = $extensionManager->listInstalledHandles();
			// add the prefix and suffix to the paths for each extension to generate
			// their full paths
			$paths = array_map(create_function('$path', 'return EXTENSIONS . "/" . $path . "/csv-importers/";'), $extensionManager->listInstalledHandles());
			// make sure any importers constructed via the symphony are available
			// such importers are stored in the workspace
			$paths[] = WORKSPACE . "/csv-importers/";
			return array_filter($paths, create_function('$path', 'return @is_dir($path);'));
		}

		/**
		 * Generate the handle name from a filename (not a full path).
		 *
		 * @param string $name
		 *	the name of the file to generate the handle from.
		 * @return string
		 *	the constructed handle.
		 */
		public function __getHandleFromFilename($name) {
			// case insensitively remove any csv-importer prefix and .php suffix to
			// construct the handle.
			return preg_replace(array('/^csv-importer./i', '/.php$/i'), '', $name);
		}

		/**
		 * Generate the driver pathname given an input csv importer name.
		 *
		 * @param string $name
		 *	the name of the importer to construct the class name for.
		 * @return string
		 *	the constructed classname.
		 */
		public function __getDriverPath($name) {
			return $this->__getClassPath($name) . "/csv-importer.$name.php";
		}

		/**
		 * Generate the classname from the name of an input csv importer.
		 *
		 * @param string $name
		 *	the name of the csv importer to construct the class name for.
		 * @return
		 *	the constructed class name.
		 */
		public function __getClassName($name) {
			return 'csvimporter' . str_replace('-', '_', $name);
		}

		/**
		 * Given a csv importer name, determine the path in which the file that
		 * contains that particular csv importer resides.
		 *
		 * @param string $name
		 *	the csv importer to locate the full path of.
		 * @return array
		 *	an array the paths that contain a csv importer file instance with the
		 *	input name. if the name cannot be found, this returns an empty array.
		 */
		public function __getClassPath($name) {
			// filter the paths to those that contains a csv-importer.$name.php file
			// and return the first match
			return current(array_filter($this->paths(), create_function('$path', 'return @is_file($path . "/csv-importer.' . $name . '.php");')));
		}

		/**
		 * Construct an instance of an input csv importer. The constructed instance
		 * is added to the pool of csv importers.
		 *
		 * @param string $name
		 *	the name of the csv importer to construct
		 * @return boolean|reference
		 *	if the name does not correspond to a valid csv importer then false,
		 *	otherwise a reference to the constructed importer.
		 */
        public function create($name) {
			$classname = $this->__getClassName($name);
			$path = $this->__getDriverPath($name);
			
			if (!@is_file($path)) {
				return false;
			}
			
			if (!class_exists($classname)) {
				require_once($path);
			}
			
			$this->_pool[] =& new $classname($this->_Parent);

			return end($this->_pool);
        }

		/**
		 * Generate an array of importers. Each importer is a structure containing
		 * all the metadata pertaining to the importer.
		 *
		 * @return array
		 *	the array of available importer structures.
		 */
		public function listAll() {
			// create an array of file matching globs
			$globs = array_map(create_function('$path', 'return $path . "csv-importer.*.php";'), $this->paths());
			// apply the glob function to the globs which gives us the filenames that
			// match the glob for each glob. flatten this so we have a flat array of
			// just the filenames
			$matches = array_flatten(array_map(glob, $globs));
			// apply the data method and append the metadata
			// to the result
			return array_map(array($this, data), array_map(basename, $matches));
		}

		/**
		 * Generate an array of data for an input class full path.
		 *
		 * @param string $filename
		 *	the filename of the class to generate the metadata structure for.
		 * @return array
		 *	an array of metadata for the input path. this will return an empty
		 *	array for files that cannot be processed or for which there is no
		 *	metadata.
		 */
		protected function data($filename) {
			$handle = $this->__getHandleFromFilename($filename);
			$classname = $this->__getClassName($handle);
			require_once($this->__getDriverPath($handle));
			return @call_user_func(array(&$classname, 'data'));
		}

		/**
		 * Get the file path to store the generated php file in.
		 *
		 * @param array $importer
		 *	the importer for which the file path is to be generated.
		 * @return string
		 *	the file path to write the file for the input importer to.
		 */
		private function phppath($importer) {
			// if the importer isn't already saved under the extensions directory
			// then save it in the workspace, otherwise leave it where it is.
			if ($importer['about']['file'] == '' or strpos($importer['about']['file'], EXTENSIONS) !== 0) {
				return sprintf('%s/csv-importers/csv-importer.%s.php', WORKSPACE, $importer['handle']);
			}
			return $importer['about']['file'];
		}

		/**
		 * Get the file path to store the uploaded template csv file in.
		 *
		 * @param array $importer
		 *	the importer for which the csv file path is to be generated.
		 * @return string
		 *	the file path to write the file for the input importer to.
		 */
		private function csvpath($importer) {
			// if the source path is under the extensions directory then leave it
			// there
			if (strpos($importer['source']['path'], EXTENSIONS) === 0) {
				return $importer['source']['path'];
			}
			// construct the source data (setting filename allows callers to access this as well)
			return sprintf('%s/csv-importers/%s.csv', WORKSPACE, basename($importer['about']['name']));
		}

		/**
		 * Generate the php file contents based on the input importer.
		 *
		 * @param array $importer
		 *	the importer to generate the file content for.
		 * @return string
		 *	the file data.
		 */
		private function phpcontent($importer) {
			$template = file_get_contents(EXTENSIONS . '/csvimporter/templates/csv-importer.php');

			$source = array(
				'path' => $this->csvpath($importer),
				'header' => $importer['source']['header'] ? 'true' : 'false',
				'name' => $importer['source']['name']
			);
			$extensionManager = new ExtensionManager($this);
			$version = $extensionManager->fetchInstalledVersion('csvimporter');
			return sprintf($template,
				str_replace(' ', '', ucwords($importer['handle'])),							// handle
				var_export($importer['about']['name'], true),								// name
				var_export($importer['about']['author']['name'], true),						// author
				var_export($importer['about']['author']['website'], true),					// website
				var_export($importer['about']['author']['email'], true),					// email
				var_export($importer['about']['description'], true),						// description
				var_export($importer['about']['created'], true),							// create date
				DateTimeObj::getGMT('c'),													// update date
				$version,																	// extension version
				strtr(var_export($importer['mappings'], true), "\n", " "),					// the mappings (single line)
				strtr(var_export($importer['section'], true), "\n", " "),					// the section (single line)
				strtr(var_export($source, true), "\n", " ")									// the source data (single line)
			);
		}

		/**
		 * Store a constructed importer by writing the source for a php class with
		 * accessors for the attributes of this importer to disk. The format of
		 * importer is an array, the key 'about' contains an array of metadata in key
		 * value pairs pertaining to the importer, the key 'mappings' contains an
		 * array of columns to fields, the key 'section' defines the section to which
		 * the fields refer and the key 'source' the current location on the
		 * filesystem where the source sv resides. The source file will be copied to
		 * a location under the workspace for later editing of the importer.
		 *
		 * @param array $importer
		 *	the array structure of the importer to store.
		 * @param string $csvfilepath
		 *	the location of the source file after this has been stored.
		 * @param array $errors (optional)
		 *	the array into which error messages should be placed. defaults to null.
		 * @return boolean
		 *	true if the importer was successfully saved, false otherwise.
		 */
		public function store($importer, &$csvfilepath, &$errors=null) {
			$filemode = $this->_Parent->Configuration->get('write_mode', 'file');
			$dirmode = $this->_Parent->Configuration->get('write_mode', 'directory');
			$filename = $this->phppath($importer);
			$dirname = dirname($filename);
			
			// make sure the directory exists
			if (!is_dir($dirname)) {
				General::realiseDirectory($dirname, $dirmode);
			}
			
			// make sure new file can be written
			if (!is_writable($dirname) or (file_exists($filename) and !is_writable($filename))) {
				$errors[] = __('Cannot save formatter, path is not writable.');
				return false;
			}

			// move the file if it isn't where it should be
			if ($importer['source']['path'] != $this->csvpath($importer)) {
				if (!rename($importer['source']['path'], $this->csvpath($importer))) {
					return false;
				}
			}

			// write file to disk
			if (!General::writeFile($filename, $this->phpcontent($importer), $filemode)) {
				$errors[] = 'Could not write importer file';
				// if we failed to write the php file then we need to cleanup the csv file
				if ($importer['source']['path'] != $this->csvpath($importer)) {
					if (!rename($this->csvpath($importer), $importer['source']['path'])) {
						return false;
					}
					// revert the input filename variable
					$csvfilepath = $importer['source']['path'];
				}
				return false;
			}
			// update the input csv filename variable
			$csvfilepath = $this->csvpath($importer);
			return true;
		}

		/**
		 * Delete an importer. This will delete both the php file and the template csv
		 * file.
		 *
		 * @param array $importer
		 *	the importer array structure to delete the backing files for.
		 * @return boolean
		 *	true on success, false otherwise.
		 */
		public function remove($importer) {
			return General::deleteFile($importer['about']['file']) and General::deleteFile($importer['source']['path']);
		}

		/**
		 * Delete an importer. This will delete both the php file and the template csv
		 * file.
		 *
		 * @param string name
		 *	the name of the importer to delete.
		 * @return boolean
		 *	true on success, false otherwise.
		 */
		public function delete($name) {
			$this->remove($this->create($name)->data());
		}
	}

	/**
	 * Flatten a multi-dimensional array into a zero indexed array. This is a
	 * utility function and not a method of the csv importer manager.
	 *
	 * @param array $array
	 *	the array to flatten
	 * @param boolean $flat (optional)
	 *	the result accumulator.
	 * @return array
	 *	the flattened array
	 */
	function array_flatten($array, $flat = array(), $depth=0) {
		if (!is_array($array) or empty($array)) {
			return $flat;
		}

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$flat = array_flatten($value, $flat, $depth);
			} else {
				$flat[] = $value;
			}
		}
		return $flat;
	}
?>
