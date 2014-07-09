<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Course seraching GUI for Generali
*
* @author	Richard Klees <richard.klees@concepts-and-training.de>
* @version	$Id$
*/

require_once("Services/GEV/Desktop/classes/class.gevCourseHighlightsGUI.php");
require_once("Services/CaTUIComponents/classes/class.catHSpacerGUI.php");
require_once("Services/GEV/Desktop/classes/class.gevUserSelectorGUI.php");
require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
require_once("Services/CaTUIComponents/classes/class.catLegendGUI.php");
require_once("Services/GEV/Desktop/classes/class.gevCourseSearchTableGUI.php");

class gevCourseSearchGUI {
	public function __construct() {
		global $lng, $ilCtrl, $tpl, $ilUser;

		$this->lng = &$lng;
		$this->ctrl = &$ilCtrl;
		$this->tpl = &$tpl;
		$this->user_id = $ilUser->getId();
		$this->user_utils = gevUserUtils::getInstance($ilUser->getId());

		if ($this->user_utils->hasUserSelectorOnSearchGUI()) {
			$this->target_user_id = $_POST["target_user_id"]
								  ? $_POST["target_user_id"]
								  : $ilUser->getId();
		}
		else {
			$this->target_user_id = $ilUser->getId();
		}

		$this->tpl->getStandardTemplate();
	}

	public function executeCommand() {
		return $this->render();
	}

	public function render() {
		if ($this->user_utils->hasUserSelectorOnSearchGUI()) {
			$user_selector = new gevUserSelectorGUI($this->target_user_id);
			$user_selector->setUsers($this->user_utils->getEmployeesForCourseSearch())
						  ->setCaption("gev_crs_srch_usr_slctr_caption")
						  ->setAction($this->ctrl->getLinkTargetByClass("gevCourseSearchGUI"));
			$usrsel = $user_selector->render();
		}
		else {
			$usrsel = "";
		}

		$hls = new gevCourseHighlightsGUI($this->target_user_id);

		$spacer = new catHSpacerGUI();
		$spacer_out = $spacer->render();

		$crs_tbl = new gevCourseSearchTableGUI($this->target_user_id, $this);
		$crs_tbl->setTitle("gev_crs_srch_title")
				->setSubtitle( $this->target_user_id == $this->user_id
							 ? "gev_crs_srch_my_table_desc"
							 : "gev_crs_srch_theirs_table_desc"
							 )
				->setImage("GEV_img/ico-head-search.png")
				->setCommand("gev_crs_srch_limit", "www.google.de"); // TODO: set this properly

		return $usrsel
			 . ( ($hls->countHighlights() > 0)
			   ?   $hls->render()
			 	 . $spacer->render()
			   : ""
			   )
			 . $crs_tbl->getHTML()
			 ;
	}
}

?>