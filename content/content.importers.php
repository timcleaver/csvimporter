<?php
	/**
	 * Class to control the addition, editing and deletions of CSV importers. This
	 * utilizes the csvimportermanager to identify, construct and delete importers.
	 * Editing and submission of importers is handled via the csvimportereditor.
	 * The construction and display of the index is handled via the
	 * csvimporterindexer.
	 *
	 * @package extensions
	 * @subpackage csvimporter
	 * @author Timothy Cleaver
	 * @version 0.0.1
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/csvimporter/lib/class.csvimportermanager.php');
	require_once(EXTENSIONS . '/csvimporter/lib/class.csvimportereditor.php');
	require_once(EXTENSIONS . '/csvimporter/lib/class.csvimporterindexer.php');
	require_once(EXTENSIONS . '/csvimporter/lib/class.csvimporterrunner.php');

	/*
	 * The class must be named according to: contentExtension{$Extension Name}{$Page}.
	 */
	class contentExtensionCsvImporterImporters extends AdministrationPage {
		/**
		 * Constructor.
		 *
		 * @param mixed $parent
		 *	the parent of this.
		 */
		public function __construct(&$parent){
			parent::__construct($parent);
		}

		/**
		 * This is the callback that symphony calls to produce the index content
		 * for this extension page.
		 */
		public function __viewIndex() {
			$csvImporterIndexer = new CsvImporterIndexer();
			$csvImporterIndexer->index($this, $_POST['errors']);
		}

		/**
		 * This is the callback symphony uses to view/construct new importers.
		 */
		public function __viewNew() {
			$csvImporterEditor = new CsvImporterEditor();
			if (isset($_POST['action']['save'])) {
				$context = $this->Context();
				$csvImporterEditor->edit($this, $this->fromForm(), $context[2], $_POST['errors']);
				return;
			}
			$csvImporterEditor->create($this, $this->fromForm());
		}

		/**
		 * This is the callback symphony uses to construct the csv importer ditor
		 * page. We call on the csv importer editor to display the page to do so.
		 */
		public function __viewEdit() {
			// if the user just deleted the current importer from its edit page then
			// we must redirect the user to the index page. the actual deletion was
			// handled by the action edit method.
			if (isset($_POST['action']['delete'])) {
				redirect(URL . '/symphony/extension/csvimporter/importers/');
				return;
			}
			$csvImporterManager = new CsvImporterManager($this->Parent);
			$context = $this->Context();
			$importer = $csvImporterManager->create($context[1])->data();

			$csvImporterEditor = new CsvImporterEditor();
			$csvImporterEditor->edit($this, $importer, $context[2], $_POST['errors']);
		}

		/**
		 * This is the callback symphony uses to handle the actions posted from the
		 * index.
		 */
		public function __actionIndex() {
			$csvImporterManager = new CsvImporterManager($this->_Parent);
			switch ($_POST['with-selected']) {
				case 'run':
					$csvImporterRunner = new CsvImporterRunner();
					// each selected importer will be stored in the items element
					// of the post data as an array mapping the importer name to
					// the value of the checkbox (on). thus, we extract the keys
					// from the _POST['items'] array and send the resulting array
					// to the importer runner to run the selected importers.
					foreach (array_keys($_POST['items']) as $handle) {
						// construct an instance of the importer
						$importer = $csvImporterManager->create($handle)->data();
						// we must replace the template csv details with those
						// of the just uploaded file
						$importer['source']['path'] = $_FILES['fields']['tmp_name'][$handle]['source'];
						$importer['source']['name'] = $_FILES['fields']['name'][$handle]['source'];
						$csvImporterRunner->run($this->_Parent->Author->get('id'), $importer, $_POST['errors']);
					}
				break;
				case 'delete':
					// each selected importer will be stored in the items element
					// of the post data as an array mapping the importer name to
					// the value of the checkbox (on). thus, we extract the keys
					// from the _POST['items'] array and send the resulting array
					// to the importer manager to delete the selected importers.
					array_map(array($csvImporterManager, 'delete'), array_keys($_POST['items']));
			}
		}

		/**
		 * This is the callback symphony uses to handle the save of a new importer.
		 */
		public function __actionNew() {
			// if the action is upload then we are only partway through the form so,
			// return the user to the editor
			if (isset($_POST['action']['upload'])) {
				return;
			}
			// extract the importer from the post data
			$importer = $this->fromForm();
			// if the action is to run the newly defined importer then do so
			if (isset($_POST['action']['run'])) {
				$csvImporterRunner = new CsvImporterRunner();
				// pass the errors in by reference so that they can be set and
				// the view can display them
				$csvImporterRunner->run($this->_Parent->Author->get('id'), $importer, $_POST['errors']);
			}
			$csvImporterManager = new CsvImporterManager($this->_Parent);
			// because the process of saving the file will change the loation of the
			// template csv file we must pass in the $_POST variable containing this
			// information so that the store function can modify it. similarly we
			// use the _POST['errors'] variable to hold any error data generated when
			// writing the file.
			$csvImporterManager->store($importer, $_POST['fields']['source']['tmp_name'], $_POST['errors']);
			// no need to call the editor here as the __viewNew will get
			// called as well. doing so will cause an error because the form
			// hasn't been constructed at the point the action is called.
		}

		/**
		 * This is the callback symphony uses to handle the save of an edited importer.
		 */
		public function __actionEdit() {
			// if the action is upload then we are only partway through the form so,
			// return the user to the editor
			if (isset($_POST['action']['upload'])) {
				return;
			}
			// if the action is to delete the saved importer then we use the importer
			// manager to delete the files and redirect the user back to the index.
			if (isset($_POST['action']['delete'])) {
				$csvImporterManager = new CsvImporterManager($this->_Parent);
				$csvImporterManager->remove($this->fromForm());
				return;
			}
			// if the action is to run the current importer then do so
			if (isset($_POST['action']['run'])) {
				$csvImporterRunner = new CsvImporterRunner();
				// pass the errors in by reference so that they can be set and
				// the view can display them
				$csvImporterRunner->run($this->_Parent->Author->get('id'), $this->fromForm(), $_POST['errors']);
				return;
			}
			$csvImporterManager = new CsvImporterManager($this->_Parent);
			// because the process of saving the file will change the loation of the
			// template csv file we must pass in the $_POST variable containing this
			// information so that the store function can modify it. similarly we
			// use the _POST['errors'] variable to hold any error data generated when
			// writing the file.
			$csvImporterManager->store($this->fromForm(), $_POST['fields']['source']['tmp_name'], $_POST['errors']);
			// no need to call the editor here as the __viewEdit will get
			// called as well. doing so will cause an error because the form
			// hasn't been constructed at the point the action is called.
		}

		/**
		 * Constrct an importer array structure from the $_POST and $_FILES
		 * globals. This is necessary to isolate the csvimportereditor from
		 * needing to know whether the data is coming from an existing importer
		 * or is new based only on the form data.
		 *
		 * @return array
		 *	an importer structure that reflects the current form processing status.
		 */
		protected function fromForm() {
			$importer = array('about' => array(
					'name'			=> General::sanitize($_POST['fields']['about']['name']),
					'description'	=> General::sanitize($_POST['fields']['about']['description']),
					'file'			=> "",
					'created'		=> DateTimeObj::getGMT('c'),
					'updated'		=> DateTimeObj::getGMT('c'),
				)
			);
			// if the creation time was specified in the post data overwrite the default above
			if (isset($_POST['fields']['about']['created'])) {
				$importer['about']['created'] = $_POST['fields']['about']['created'];
			}
			// if the file was specified in the post data then add this to the importer
			if (isset($_POST['fields']['about']['file'])) {
				$importer['about']['file'] = $_POST['fields']['about']['file'];
			}
			if (isset($_POST['fields']['about']['author'])) {
				$importer['about']['author'] = $_POST['fields']['about']['author'];
			} else {
				$importer['about']['author']['name'] = $this->_Parent->Author->getFullName();
				$importer['about']['author']['website'] = URL;
				$importer['about']['author']['email'] = $this->_Parent->Author->get('email');
			}
			if (isset($_FILES['fields']['tmp_name']['source']) and $_FILES['fields']['error']['source'] == UPLOAD_ERR_OK) {
				// because this is a multistage form construction we need the file data
				// to persist across multiple requests. thus, we store the accumulating
				// data in hidden fields in the form and move the file to a custom directory
				// to prevent php deleting it. we use the temp directory so that the file will
				// get cleaned up automatically if the user does not save the importer.
				$temp_name = tempnam(sys_get_temp_dir(), $_FILES['fields']['name']['source']);
				move_uploaded_file($_FILES['fields']['tmp_name']['source'], $temp_name);
				$importer['source']['path'] = $temp_name;
				$importer['source']['name'] = $_FILES['fields']['name']['source'];
			}
			if (isset($_POST['fields']['source']['tmp_name'])) {
				$importer['source']['path'] = $_POST['fields']['source']['tmp_name'];
				$importer['source']['name'] = $_POST['fields']['source']['name'];
			}
			// sanity check the input handle
			$context = $this->Context();
			if (isset($context[1])) {
				$importer['handle'] = $context[1];
			} else {
				$importer['handle'] = str_replace('-', '', Lang::createHandle($importer['about']['name']));
			}
			$importer['source']['header'] = $_POST['fields']['header'];
			$importer['section']['id'] = $_POST['fields']['section'];
			$sectionManager = new SectionManager($this->Parent);
			if (isset($importer['section']['id'])) {
				$importer['section']['name'] = $sectionManager->fetch($importer['section']['id'])->get('name');
			}
			$importer['mappings'] = $_POST['fields']['mapping'];
			return $importer;
		}
	}

