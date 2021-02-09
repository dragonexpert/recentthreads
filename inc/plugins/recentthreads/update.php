<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_update()
{
    global $db, $lang, $mybb;
    if ($mybb->input['action'] != "update_recentthreads")
    {
        return;
    }
    $lang->load("recentthreads");
    $have_already = array();
    $query = $db->simple_select("settings", "*", "name LIKE 'recentthread%'");

    while ($old_setting = $db->fetch_array($query))
    {
        $have_already[] = $old_setting['name'];
    }
    $db->free_result($query);

    $settings_json = json_decode(file_get_contents("settings.json", true), true);
    $q2 = $db->simple_select("settinggroups", "*", "name='recentthreads'");
    $settinggroup = $db->fetch_array($q2);
    $db->free_result($q2);
    $gid = $settinggroup['gid'];
    if(!$gid)
    {
        // Add a fallback in case somehow they lost the setting group.
        $new_setting_group = array(
            "name" => "recentthreads",
            "title" => "Recent Threads Settings",
            "description" => "Customize various aspects of recent threads",
            "disporder" => 77,
            "isdefault" => 0
        );

        $gid = $db->insert_query("settinggroups", $new_setting_group);
    }

    foreach ($settings_json as $key)
    {
        if (!in_array($key['name'], $have_already))
        {
            $new_setting[] = array(
                "name" => $db->escape_string($key['name']),
                "title" => $db->escape_string($key['title']),
                "description" => $db->escape_string($key['description']),
                "optionscode" => $db->escape_string($key['optionscode']),
                "value" => $db->escape_string($key['value']),
                "disporder" => (int) $key['disporder'],
                "gid" => $gid
            );
        }
    }

    $db->insert_query_multiple("settings", $new_setting);
    rebuild_settings();

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

    foreach ($update_templates as $key)
    {
        $db->update_query("templates", array("title" => $key['title']), "title='" . $key['old_title'] . "'");
    }

    flash_message($lang->admin_log_config_plugins_update_recentthreads, "success");
    admin_redirect("index.php?module=config-plugins");
    flash_message($lang->recentthread_current, "error");
    admin_redirect("index.php?module=config-plugins");
}
