<?php
require_once("./Modules/DataCollection/classes/Fields/Base/class.ilDclBaseFieldModel.php");
require_once("./Modules/DataCollection/classes/Helpers/class.ilDclRecordQueryObject.php");

/**
 * Class ilDclBooleanFieldModel
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class ilDclFileuploadFieldModel extends ilDclBaseFieldModel {

	/**
	 * Returns a query-object for building the record-loader-sql-query
	 *
	 * @param string $direction
	 * @param boolean $sort_by_status The specific sort object is a status field
	 *
	 * @return null|ilDclRecordQueryObject
	 */
	public function getRecordQuerySortObject($direction = "asc", $sort_by_status = false) {
		global $ilDB;
		$join_str = "LEFT JOIN il_dcl_record_field AS sort_record_field_{$this->getId()} ON (sort_record_field_{$this->getId()}.record_id = record.id AND sort_record_field_{$this->getId()}.field_id = "
			. $ilDB->quote($this->getId(), 'integer') . ") ";
		$join_str .= "LEFT JOIN il_dcl_stloc{$this->getStorageLocation()}_value AS sort_stloc_{$this->getId()} ON (sort_stloc_{$this->getId()}.record_field_id = sort_record_field_{$this->getId()}.id) ";
		$join_str .= "LEFT JOIN object_data AS sort_object_data_{$this->getId()} ON (sort_object_data_{$this->getId()}.obj_id = sort_stloc_{$this->getId()}.value) ";
		$select_str = " sort_object_data_{$this->getId()}.title AS field_{$this->getId()},";

		$sql_obj = new ilDclRecordQueryObject();
		$sql_obj->setSelectStatement($select_str);
		$sql_obj->setJoinStatement($join_str);
		$sql_obj->setOrderStatement("field_{$this->getId()} ".$direction);

		return $sql_obj;

	}


	/**
	 * Returns a query-object for building the record-loader-sql-query
	 * @param string $filter_value
	 *
	 * @return null|ilDclRecordQueryObject
	 */
	public function getRecordQueryFilterObject($filter_value = "", ilDclBaseFieldModel $sort_field = null) {
		global $ilDB;

		$join_str =
			"INNER JOIN il_dcl_record_field AS filter_record_field_{$this->getId()} ON (filter_record_field_{$this->getId()}.record_id = record.id AND filter_record_field_{$this->getId()}.field_id = "
			. $ilDB->quote($this->getId(), 'integer') . ") ";
		$join_str .= "INNER JOIN il_dcl_stloc{$this->getStorageLocation()}_value AS filter_stloc_{$this->getId()} ON (filter_stloc_{$this->getId()}.record_field_id = filter_record_field_{$this->getId()}.id) ";
		$join_str .=
			"INNER JOIN object_data AS filter_object_data_{$this->getId()} ON (filter_object_data_{$this->getId()}.obj_id = filter_stloc_{$this->getId()}.value AND filter_object_data_{$this->getId()}.title LIKE "
			. $ilDB->quote("%$filter_value%", 'text') . ") ";

		$sql_obj = new ilDclRecordQueryObject();
		$sql_obj->setJoinStatement($join_str);

		return $sql_obj;
	}


	/**
	 * Returns supported file-extensions
	 * 
	 * @return array|string
	 */
	public function getSupportedExtensions() {

		if(!$this->hasProperty(ilDclBaseFieldModel::PROP_SUPPORTED_FILE_TYPES)) {
			return "*";
		}

		$file_types = $this->getProperty(ilDclBaseFieldModel::PROP_SUPPORTED_FILE_TYPES);

		$file_types = explode(",", $file_types);
		$trim_function = function($value) {
			return trim(strtolower($value));
		};
		return array_map($trim_function, $file_types);
	}

	/**
	 * @inheritDoc
	 */
	public function parseFieldCreationFormPropertyValue($property_id, $value) {
		if($property_id == ilDclBaseFieldModel::PROP_SUPPORTED_FILE_TYPES) {
			$supported_extensions = explode(",", $value);

			$supported_extensions = array_map(trim, $supported_extensions);
		}
		return $supported_extensions;
	}

	/**
	 * @inheritDoc
	 */
	public function getValidFieldProperties() {
		return array(ilDclBaseFieldModel::PROP_SUPPORTED_FILE_TYPES);
	}

	/**
	 * @return bool
	 */
	public function allowFilterInListView() {
		return false;
	}

}