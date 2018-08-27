<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';

/**
 * List all completed course for current user
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @ingroup ModulesCourse
 */
class ilCourseVerificationTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 *
	 * @param ilObject $a_parent_obj
	 * @param string $a_parent_cmd
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = "")
	{
		global $DIC;

		$ilCtrl = $DIC['ilCtrl'];
		
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->addColumn($this->lng->txt("title"), "title");
		$this->addColumn($this->lng->txt("passed"), "passed");
		$this->addColumn($this->lng->txt("action"), "");

		$this->setTitle($this->lng->txt("crsv_create"));
		$this->setDescription($this->lng->txt("crsv_create_info"));
		
		$this->setRowTemplate("tpl.crs_verification_row.html", "Modules/Course");
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));

		$this->getItems();
	}

	/**
	 * Get all completed tests
	 */
	protected function getItems()
	{
		global $DIC;

		$ilUser = $DIC['ilUser'];
		
		$data = array();

		$userId = $ilUser->getId();
		$obj_ids = ilCourseParticipants::_getMembershipByType($userId, "crs");
		if($obj_ids) {
			ilCourseCertificateAdapter::_preloadListData($userId, $obj_ids);

			foreach($obj_ids as $crs_id) {
				// #11210 - only available certificates!
				if(ilCourseCertificateAdapter::_hasUserCertificate($userId, $crs_id)) {
					$courseObject = ilObjectFactory::getInstanceByObjId($crs_id);
					$factory = new ilCertificateFactory();

					$certificate = $factory->create($courseObject);

					if($certificate->isComplete()) {
						$data[] = array("id" => $crs_id,
							"title" => ilObject::_lookupTitle($crs_id),
							"passed" => true);
					}
				}
			}
		}

		$this->setData($data);
	}

	/**
	 * Fill template row
	 * 
	 * @param array $a_set
	 */
	protected function fillRow($a_set)
	{
		global $DIC;

		$ilCtrl = $DIC['ilCtrl'];

		$this->tpl->setVariable("TITLE", $a_set["title"]);
		$this->tpl->setVariable("PASSED", ($a_set["passed"]) ? $this->lng->txt("yes") :
			$this->lng->txt("no"));
		
		if($a_set["passed"])
		{
			$ilCtrl->setParameter($this->parent_obj, "crs_id", $a_set["id"]);
			$action = $ilCtrl->getLinkTarget($this->parent_obj, "save");
			$this->tpl->setVariable("URL_SELECT", $action);
			$this->tpl->setVariable("TXT_SELECT", $this->lng->txt("select"));
		}
	}
}

?>
