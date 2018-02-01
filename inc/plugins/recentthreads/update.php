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
    $have_already = array();
    $query = $db->simple_select("settings", "*", "name IN('recentthread_show_create_date', 'recentthread_format_names', 'recentthread_pages_shown',
    'recentthread_use_breadcrumbs', 'recentthread_breadcrumb_separator')");

    while($old_setting = $db->fetch_array($query))
    {
        $have_already[] = $old_setting['name'];
    }
    if(count($have_already) != 5)
    {
        $q2 = $db->simple_select("settinggroups", "*", "name='recentthreads'");
        $settinggroup = $db->fetch_array($q2);
        $gid = $settinggroup['gid'];
        if (!in_array("recentthread_show_create_date", $have_already))
        {
            $new_setting[] = array(
                "name" => "recentthread_show_create_date",
                "title" => "Show Creation Time",
                "description" => "If set to yes, the thread start date will be shown.",
                "optionscode" => "yesno",
                "disporder" => 11,
                "value" => 1,
                "gid" => $gid
            );
        }
        if (!in_array("recentthread_format_names", $have_already))
        {
            $new_setting[] = array(
                "name" => "recentthread_format_names",
                "title" => "Format Usernames",
                "description" => "If set to yes, format the username of the thread starter and last poster.",
                "optionscode" => "yesno",
                "disporder" => 12,
                "value" => 1,
                "gid" => $gid
            );
        }
        if (!in_array("recentthread_pages_shown", $have_already))
        {
            $new_setting = array(
                "name" => "recentthread_pages_shown",
                "title" => "Show On These Pages",
                "description" => "The pages to show the recent threads on.  One entry per line.",
                "optionscode" => "textarea",
                "disporder" => 13,
                "value" => "index.php",
                "gid" => $gid
            );
        }
        if(!in_array("recentthread_use_breadcrumbs", $have_already))
        {
            $new_setting[] = array(
                "name" => "recentthread_use_breadcrumbs",
                "title" => "Use breadcrumbs for forum name",
                "description" => "If yes, a forum list will be shown.  Otherwise just the forum the thread resides in.",
                "optionscode" => "yesno",
                "disporder" => 14,
                "value" => 0,
                "gid" => $gid
            );
        }
        if(!in_array("recentthread_breadcrumb_separator", $have_already))
        {
            $new_setting[] = array(
                "name" => "recentthread_breadcrumb_separator",
                "title" => "Breadcrumb Separator",
                "description" => "The separator for the forum list.  No effect if breadcrumbs are disabled.  HTML allowed.",
                "optionscode" => "text",
                "disporder" => 15,
                "value" => " > ",
                "gid" => $gid
            );
        }

        $db->insert_query_multiple("settings", $new_setting);
        rebuild_settings();
        flash_message($lang->admin_log_config_plugins_update_recentthreads, "success");
        admin_redirect("index.php?module=config-plugins");
    }
    flash_message($lang->recentthread_current, "error");
    admin_redirect("index.php?module=config-plugins");
}
