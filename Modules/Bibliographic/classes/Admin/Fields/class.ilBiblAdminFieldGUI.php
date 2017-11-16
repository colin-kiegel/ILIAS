<?php
/**
 * Class ilBiblAdminFieldGUI
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

class ilBiblAdminFieldGUI {

	const FIELD_IDENTIFIER = 'field_id';
	const IS_RIS_FIELD = 'is_ris_field';
	const CMD_STANDARD = 'content';
	const CMD_CANCEL = 'cancel';
	const CMD_EDIT = 'edit';
	const CMD_UPDATE = 'update';
	const CMD_DELETE = 'delete';
	const CMD_APPLY_FILTER = 'applyFilter';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_SHOW_RIS = 'showRis';
	const CMD_SHOW_BIBTEX = 'showBibTex';
	const CMD_SAVE = 'save';

	/**
	 * @var ilObjBibliographicAdmin
	 */
	protected $object;
	/**
	 * @var \ILIAS\DI\Container
	 */
	protected $dic;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	public function __construct() {
		global $DIC;
		$this->dic = $DIC;
		$this->tpl = $this->dic['tpl'];
		$this->tabs = $DIC->tabs();
		$this->ctrl = $this->dic->ctrl();
		$this->tpl = $this->dic['tpl'];
		$this->object = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
	}

	public function executeCommand() {

		$nextClass = $this->ctrl->getNextClass();
		switch ($nextClass) {
			default:
				$this->tabs->activateTab(ilObjBibliographicAdminGUI::TAB_FIELDS);
				$this->performCommand();
		}
	}

	protected function performCommand() {
		$cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
		switch ($cmd) {
			case self::CMD_STANDARD:
				$this->content($_GET['content_type']);
				break;
			case self::CMD_EDIT:
			case self::CMD_UPDATE:
			case self::CMD_DELETE:
			case self::CMD_APPLY_FILTER:
			case self::CMD_RESET_FILTER:
			case self::CMD_SHOW_RIS:
			case self::CMD_SHOW_BIBTEX:
			case self::CMD_SAVE:
				if ($this->dic->access()->checkAccess('write', "", $this->object->getRefId())) {
					$this->{$cmd}();
					break;
				} else {
					ilUtil::sendFailure($this->dic->language()->txt("no_permission"), true);
					break;
				}
		}
	}

	protected function setSubTabs($a_active_tab) {
		$this->dic['ilTabs']->addSubTab(ilBiblField::DATA_TYPE_RIS, $this->dic->language()->txt('ris'), $this->ctrl->getLinkTargetByClass(array(
			'ilObjBibliographicAdminGUI',
			ilBiblAdminFieldGUI::class), ilBiblAdminFieldGUI::CMD_SHOW_RIS)

		);
		$this->dic['ilTabs']->activateSubTab('ris');

		$this->dic['ilTabs']->addSubTab(ilBiblField::DATA_TYPE_BIBTEX, $this->dic->language()->txt('bibtex'), $this->ctrl->getLinkTargetByClass(array(
			'ilObjBibliographicAdminGUI',
			ilBiblAdminFieldGUI::class), ilBiblAdminFieldGUI::CMD_SHOW_BIBTEX)
		);
		$this->dic['ilTabs']->activateSubTab($a_active_tab);
	}

	public function showRis() {
		$this->setSubTabs(ilBiblField::DATA_TYPE_RIS);
		//$this->ctrl->setParameterByClass(ilBiblAdminFieldTableGUI::class, self::IS_RIS_FIELD, 1);
		$this->content(ilBiblField::DATA_TYPE_RIS);
	}

	public function showBibTex() {
		$this->setSubTabs(ilBiblField::DATA_TYPE_BIBTEX);
		//$this->ctrl->setParameterByClass(ilBiblAdminFieldTableGUI::class, self::IS_RIS_FIELD, 0);
		$this->content(ilBiblField::DATA_TYPE_BIBTEX);
	}

	public function content($data_type = ilBiblField::DATA_TYPE_RIS) {
		if(isset($_GET['content_type'])) {
			$this->setSubTabs($_GET['content_type']);
			$data_type = $_GET['content_type'];
		}
		$this->ctrl->saveParameterByClass(ilBiblAdminFieldTableGUI::class, self::FIELD_IDENTIFIER);
		$ilBiblAdminFieldTableGUI = new ilBiblAdminFieldTableGUI($this, self::CMD_STANDARD, $this->object, $data_type);
		$this->tpl->setContent($ilBiblAdminFieldTableGUI->getHTML());

		/*		$data = ilBiblField::getAvailableFieldsForObjId($this->object->getId());

				$this->tpl->setContent("<pre>".print_r($data, true). "</pre>");*/
	}

	protected function add() { // Formular für neues Anlegen
		//$this->il_bibl_field = new ilBiblField($_GET[self::FIELD_IDENTIFIER]);

		//$this->tabs->activateTab(self::CMD_STANDARD);
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, new ilBiblField());
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());

	}

	protected function create() { // verarbeiten von add()

	}

	public function edit() { // Formular zum Bearbeiten eines bestehenden Eintrages
		//$this->tabs->activateTab(self::CMD_STANDARD);
		$field = $this->dic->http()->request()->getQueryParams()[self::FIELD_IDENTIFIER];
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, ilBiblField::findOrFail($field));
		$ilBiblSettingsFilterFormGUI->fillForm();
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());
	}


	public function update() { // Verarbeiten
		$field = $this->dic->http()->request()->getQueryParams()[self::FIELD_IDENTIFIER];

		//$this->tabs->activateTab(self::CMD_STANDARD);
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, ilBiblField::findOrFail($field));
		if ($ilBiblSettingsFilterFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->dic->language()->txt('changes_saved_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$ilBiblSettingsFilterFormGUI->setValuesByPost();
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());
	}

	protected function applyFilter() {
		$ilBiblAdminFieldTableGUI = new ilBiblAdminFieldTableGUI($this, self::CMD_STANDARD, $this->object);
		$ilBiblAdminFieldTableGUI->writeFilterToSession();
		$this->ctrl->redirect($this, self::CMD_STANDARD);
	}


	protected function resetFilter() {
		$ilBiblAdminFieldTableGUI = new ilBiblAdminFieldTableGUI($this, self::CMD_STANDARD, $this->object);
		$ilBiblAdminFieldTableGUI->resetFilter();
		$ilBiblAdminFieldTableGUI->resetOffset();
		$this->ctrl->redirect($this, self::CMD_STANDARD);
	}

	public function save() {
		foreach($_POST['row_values'] as $id => $data) {
			if(!empty($data['position'])) {
				if(!ilBiblField::where(array('id' =>$id))->hasSets()) {
					$il_bibl_field = new ilBiblField();
					$il_bibl_field->setIdentifier($_POST['row_values'][$id]['identifier']);
					$il_bibl_field->setDataType($_POST['row_values'][$id]['data_type']);
					$il_bibl_field->setPosition($_POST['row_values'][$id]['position']);
					$il_bibl_field->setIsStandardField($_POST['row_values'][$id]['is_standard_field']);
					$il_bibl_field->setObjectId($this->object->getId());
					$il_bibl_field->store();
				}
			}
		}
		ilUtil::sendSuccess($this->dic->language()->txt("changes_successfully_saved"));

		$this->ctrl->setParameter($this, 'content_type', $_POST['row_values'][$id]['data_type']);
		$this->ctrl->redirect($this, self::CMD_STANDARD);
	}

}