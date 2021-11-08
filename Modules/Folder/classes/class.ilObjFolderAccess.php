<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

/**
 * Class ilObjFileAccess
 *
 * @author 	Stefan Meyer <meyer@leifos.com>
 */
class ilObjFolderAccess extends ilObjectAccess
{
    private static ilSetting $folderSettings;

    private static function getFolderSettings() : ilSetting
    {
        if (is_null(ilObjFolderAccess::$folderSettings)) {
            ilObjFolderAccess::$folderSettings = new ilSetting('fold');
        }
        return ilObjFolderAccess::$folderSettings;
    }

    public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
    {
        if ($a_cmd == "download" &&
            !ilObjFolderAccess::hasDownloadAction($a_ref_id)) {
            return false;
        }
        return true;
    }

    public static function _getCommands()
    {
        $commands = array();
        $commands[] = array("permission" => "read", "cmd" => "view", "lang_var" => "show", "default" => true);

        // why here, why read permission? it just needs info_screen_enabled = true in ilObjCategoryListGUI (alex, 30.7.2008)
        // this is not consistent, with all other objects...
        //$commands[] = array("permission" => "read", "cmd" => "showSummary", "lang_var" => "info_short", "enable_anonymous" => "false");
        $commands[] = array("permission" => "read", "cmd" => "download", "lang_var" => "download"); // #18805
        // BEGIN WebDAV: Mount Webfolder.
        if (ilDAVActivationChecker::_isActive()) {
            if (ilWebDAVUtil::getInstance()->isLocalPasswordInstructionRequired()) {
                $commands[] = array('permission' => 'read', 'cmd' => 'showPasswordInstruction', 'lang_var' => 'mount_webfolder', 'enable_anonymous' => 'false');
            } else {
                $commands[] = array("permission" => "read", "cmd" => "mount_webfolder", "lang_var" => "mount_webfolder", "enable_anonymous" => "false");
            }
        }
        $commands[] = array("permission" => "write", "cmd" => "enableAdministrationPanel", "lang_var" => "edit_content");
        $commands[] = array("permission" => "write", "cmd" => "edit", "lang_var" => "settings");
        
        return $commands;
    }

    
    private static function hasDownloadAction(int $ref_id) : bool
    {
        $settings = ilObjFolderAccess::getFolderSettings();
        if ($settings->get("enable_download_folder", 0) != 1) {
            return false;
        }
        return true;
    }

    public static function _checkGoto($a_target)
    {
        global $DIC;

        $ilAccess = $DIC->access();

        $t_arr = explode("_", $a_target);

        if ($t_arr[0] != "fold" || ((int) $t_arr[1]) <= 0) {
            return false;
        }

        if ($ilAccess->checkAccess("read", "", $t_arr[1]) ||
            $ilAccess->checkAccess("visible", "", $t_arr[1])) {
            return true;
        }
        return false;
    }
}
