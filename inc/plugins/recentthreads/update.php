<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_update()
{
    global $db, $lang, $mybb;
    if($mybb->input['action'] != "update_recentthreads")
    {
        return;
    }
    $lang->load("recentthreads");
    $query = $db->simple_select("settings", "*", "name IN('recentthread_show_create_date', 'recentthread_format_names')");
    if($db->num_rows($query) == 0)
    {
        $q2 = $db->simple_select("settinggroups", "*", "name='recentthreads'");
        $settinggroup = $db->fetch_array($q2);
        $gid = $settinggroup['gid'];
        $new_setting[] = array(
            "name" => "recentthread_show_create_date",
            "title" => "Show Creation Time",
            "description" => "If set to yes, the thread start date will be shown.",
            "optionscode" => "yesno",
            "disporder" => 11,
            "value" => 1,
            "gid" => $gid
        );

        $new_setting[] = array(
            "name" => "recentthread_format_names",
            "title" => "Format Usernames",
            "description" => "If set to yes, format the username of the thread starter and last poster.",
            "optionscode" => "yesno",
            "disporder" => 12,
            "value" => 1,
            "gid" => $gid
        );

        $db->insert_query_multiple("settings", $new_setting);
        rebuild_settings();
        flash_message($lang->admin_log_config_plugins_update_recentthreads, "success");
        admin_redirect("index.php?module=config-plugins");
    }
    admin_redirect("index.php?module=config-plugins");
}
