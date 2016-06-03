<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/Object/classes/class.ilObjectAccess.php");

/**
 * Class ilObjDataCollectionAccess
 *
 * @author  Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Marcel Raimann <mr@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id: class.ilObjDataCollectionAccess.php 15678 2008-01-06 20:40:55Z akill $
 *
 */
class ilObjDataCollectionAccess extends ilObjectAccess {

	/**
	 * get commands
	 *
	 * this method returns an array of all possible commands/permission combinations
	 *
	 * example:
	 * $commands = array
	 *    (
	 *        array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *        array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *    );
	 */
	static  function _getCommands() {
		$commands = array(
			array( "permission" => "read", "cmd" => "render", "lang_var" => "show", "default" => true ),
			array( "permission" => "write", "cmd" => "listRecords", "lang_var" => "edit_content" ),
			array( "permission" => "write", "cmd" => "edit", "lang_var" => "settings" )
		);

		return $commands;
	}


	/**
	 * check whether goto script will succeed
	 */
	static function _checkGoto($a_target) {
		global $ilAccess;

		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "dcl" || ((int)$t_arr[1]) <= 0) {
			return false;
		}

		if ($ilAccess->checkAccess("read", "", $t_arr[1])) {
			return true;
		}

		return false;
	}


	/**
	 * checks wether a user may invoke a command or not
	 * (this method is called by ilAccessHandler::checkAccess)
	 *
	 * @param    string $a_cmd        command (not permission!)
	 * @param    string $a_permission permission
	 * @param    int    $a_ref_id     reference id
	 * @param    int    $a_obj_id     object id
	 * @param    int    $a_user_id    user id (if not provided, current user is taken)
	 *
	 * @return    boolean        true, if everything is ok
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "") {
		global $ilUser, $lng, $rbacsystem, $ilAccess;

		if ($a_user_id == "") {
			$a_user_id = $ilUser->getId();
		}
		switch ($a_cmd) {
			case "view":

				if (! ilObjDataCollectionAccess::_lookupOnline($a_obj_id)
					&& ! $rbacsystem->checkAccessOfUser($a_user_id, 'write', $a_ref_id)
				) {
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));

					return false;
				}
				break;

			// for permission query feature
			case "infoScreen":
				if (! ilObjDataCollectionAccess::_lookupOnline($a_obj_id)) {
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
				} else {
					$ilAccess->addInfoItem(IL_STATUS_MESSAGE, $lng->txt("online"));
				}
				break;
		}
		switch ($a_permission) {
			case "read":
			case "visible":
				if (! ilObjDataCollectionAccess::_lookupOnline($a_obj_id)
					&& (! $rbacsystem->checkAccessOfUser($a_user_id, 'write', $a_ref_id))
				) {
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));

					return false;
				}
				break;
		}

		return true;
	}


	/**
	 * Check wether datacollection is online
	 *
	 * @param    int $a_id wiki id
	 */
	public static function _lookupOnline($a_id) {
		global $ilDB;

		$q = "SELECT * FROM il_dcl_data WHERE id = " . $ilDB->quote($a_id, "integer");
		$dcl_set = $ilDB->query($q);
		$dcl_rec = $ilDB->fetchAssoc($dcl_set);

		return $dcl_rec["is_online"];
	}

	//
	// DataCollection specific Access-Checks
	//

	/**
	 * @param $data_collection_id
	 *
	 * @depracated use checkActionForId instead
	 * @return bool
	 */
	public static function checkAccessForDataCollectionId($data_collection_id) {
		global $ilAccess;

		$perm = false;
		$references = ilObject2::_getAllReferences($data_collection_id);

		if ($ilAccess->checkAccess("add_entry", "", array_shift($references))) {
			$perm = true;
		}

		return $perm;
	}


	/**
	 * @param $action
	 * @param $obj_id
	 *
	 * @return bool
	 */
	public static function checkActionForObjId($action, $obj_id) {
		foreach (ilObject2::_getAllReferences($obj_id) as $ref_id) {
			if (self::checkActionForRefId($action, $ref_id)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $action
	 * @param $ref_id
	 *
	 * @return bool
	 */
	public static function checkActionForRefId($action, $ref_id) {
		global $ilAccess;

		/**
		 * @var $ilAccess ilAccessHandler
		 */

		return $ilAccess->checkAccess($action, "", $ref_id);
	}


	/**
	 * @param $ref int the reference id of the datacollection object to check.
	 *
	 * @return bool whether or not the current user has admin/write access to the referenced datacollection
	 */
	public static function hasWriteAccess($ref) {
		global $ilAccess;

		return $ilAccess->checkAccess("write", "", $ref);
	}


	/**
	 * Has permission to view and edit all entries event when he is not the owner
	 *
	 * @param $ref
	 *
	 * @return mixed
	 */
	public static function hasEditAccess($ref) {
		global $ilAccess;

		return $ilAccess->checkAccess("edit_content", "", $ref);
	}


	/**
	 * @param $ref int the reference id of the datacollection object to check.
	 *
	 * @return bool whether or not the current user has admin/write access to the referenced datacollection
	 */
	public static function hasAddRecordAccess($ref) {
		global $ilAccess;

		return $ilAccess->checkAccess("add_entry", "", $ref);
	}


	/**
	 * @param $ref int the reference id of the datacollection object to check.
	 *
	 * @return bool whether or not the current user has read access to the referenced datacollection
	 */
	public static function hasReadAccess($ref) {
		global $ilAccess;

		return $ilAccess->checkAccess("read", "", $ref);
	}

	/**
	 * @param integer|ilDclTableView $tableview can be object or id
	 * @return bool
	 */
	public static function hasAccessToTableView($tableview)
	{
		global $rbacreview, $ilUser;
		if (is_numeric($tableview)) {
			$tableview = ilDclTableView::find($tableview);
		}
		$assigned_roles = $rbacreview->assignedGlobalRoles($ilUser->getId());
		$allowed_roles = $tableview->getRoles();

		foreach ($assigned_roles as $role_id)
		{
			if (in_array($role_id, $allowed_roles))
			{
				return true;
			}
		}
		return false;
	}
	
	
}

?>
