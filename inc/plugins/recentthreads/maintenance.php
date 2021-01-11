<?php
/*
 * This file is part of the Recent Threads On Index Plugin for MyBB.
 * This file is used for repairing inconsistencies in settings, database integrity, and templates.
 * Copyright Mark Janssen 2021
 */
if (!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_maintenance()
{
    global $db, $lang, $mybb;
    if ($mybb->input['action'] != "maintenance_recentthreads")
    {
        return;
    }
    $lang->load("recentthreads");
    $settinggroupquery = $db->simple_select("settinggroups", "*", "name='recentthreads'");
    $settinggroupinfo = $db->fetch_array($settinggroupquery);
    $flash_message = "";
    $db->free_result($settinggroupquery);
    if(array_key_exists("gid", $settinggroupinfo))
    {
        $gid = $settinggroupinfo['gid'];
    }
    else
    {
        $new_setting_group = array(
            "name" => "recentthreads",
            "title" => "Recent Threads Settings",
            "description" => "Customize various aspects of recent threads",
            "disporder" => 77,
            "isdefault" => 0
        );
        $gid = $db->insert_query("settinggroups", $new_setting_group);
    }
    $have_already = array();
    // All settings start with recentthread_
    $query = $db->simple_select("settings", "*", "name LIKE 'recentthread_%'");

    while ($old_setting = $db->fetch_array($query))
    {
        $have_already[] = $old_setting['name'];
    }
    $db->free_result($query);
    // Create a variable to see if we inserted any settings.
    $settingschanged = 0;

    $json_settings = json_decode(file_get_contents("settings.json", true), true);
    foreach ($json_settings as $key)
    {
        if(!in_array($key['name'], $have_already))
        {
            $new_setting[] = array(
                "name" => $db->escape_string($key['name']),
                "title" => $db->escape_string($key['title']),
                "description" => $db->escape_string($key['description']),
                "optionscode" => $db->escape_string($key['optionscode']),
                "disporder" => (int) $key['disporder'],
                "value" => $db->escape_string($key['value']),
                "gid" => $gid
            );
            ++$settingschanged;
        }
    }

    if($settingschanged > 0)
    {
        $db->insert_query_multiple("settings", $new_setting);
        rebuild_settings();
        $flash_message .= "<br />Inserted " . $settingschanged . " settings.";
    }
    // Now database checks
    if(!$db->field_exists("recentthread_show", "users"))
    {
        $db->add_column("users", "recentthread_show", "INT NOT NULL DEFAULT 1");
        $flash_message .= "<br />Fixed database structure.";
    }
    // Check if they have the template group.  If not run the full script.
    $templategroupquery = $db->simple_select("templategroups", "*", "prefix='recentthread'");
    $templategroupinfo = $db->fetch_array($templategroupquery);
    $db->free_result($templategroupquery);
    if(!$templategroupinfo['gid'])
    {
        require_once "templates.php";
        recentthread_templates_install();
        $flash_message .= "<br />Added templates.  Please check you don't have duplicates.";
    }
    
    // Modify the names of old templates to the new one
    $update_templates[] = array(
        "title" => "recentthread_misc",
        "old_title" => "misc_recentthreads"
    );

    $update_templates[] = array(
        "title" => "recentthread_misc_misc_mod_column",
        "old_title" => "misc_recentthreads_mod_col"
    );

    $update_templates[] = array(
        "title" => "recentthread_misc_mod_header",
        "old_title" => "misc_recentthreads_mod_header"
    );

    $update_templates[] = array(
        "title" => "recentthread_misc_moderation",
        "old_title" => "misc_recentthreads_moderation"
    );

    foreach($update_templates as $key)
    {
        $db->update_query("templates", array("title" => $key['title']), "title='" . $key['old_title'] . "'");
    }

    if(!$flash_message)
    {
        $flash_message = "Everything looks good.";
    }
    flash_message($flash_message, "success");
    admin_redirect("index.php?module=config-plugins");
}
