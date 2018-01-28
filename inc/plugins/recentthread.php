<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

$my_plugins = $cache->read("plugins");
if(array_key_exists("recentthread", $my_plugins['active']))
{
    require_once "recentthreads/hooks.php";
    $plugins->add_hook("index_end", "recentthread_list_threads");
    $plugins->add_hook("global_start", "recentthread_get_templates");
    $plugins->add_hook("global_intermediate", "recentthread_global_intermediate");
    $plugins->add_hook("xmlhttp", "recentthread_refresh_threads");
    $plugins->add_hook("usercp_options_start", "recentthread_usercp_options_start");
    $plugins->add_hook("usercp_do_options_start", "recentthread_usercp_do_options_end");

    if(defined("IN_ADMINCP"))
    {
        // Due to the massive structural changes, no upgrade script from before version 16.
        // $plugins->add_hook("admin_config_plugins_begin", "recentthread_update");
        $plugins->add_hook("admin_config_settings_begin", "recentthread_admin_config_settings_begin");
        $plugins->add_hook("admin_tools_adminlog_begin", "recentthread_admin_tools_adminlog_begin");
        $plugins->add_hook("admin_tools_get_admin_log_action", "recenttthread_admin_tools_get_admin_log_action");
        $plugins->add_hook("admin_style_templates", "recentthread_admin_style_templates");
    }
}

function recentthread_info()
{
    global $lang;
    $lang->load("recentthreads");
    $donationlink = "https://www.paypal.me/MarkJanssen";
    $updatelink = "index.php?module=config-plugins&action=update_recentthreads";
    return array(
        "name"	=> $lang->recentthreads,
        "description" => $lang->sprintf($lang->recentthreads_desc, $donationlink, $updatelink),
        "author" => "Mark Janssen",
        "version" => "16.0",
        "codename" 	=> "recentthreads",
        "compatibility"	=> "18*"
    );
}

function recentthread_install()
{
    global $mybb, $lang;
    if($mybb->version_code < 1801)
    {
        flash_message($lang->recentthread_cant_install, "error");
        admin_redirect("index.php?module=config-plugins");
    }
    require_once "recentthreads/db.php";
    recentthread_db_install();
    require_once "recentthreads/settings.php";
    recentthread_settings_install();
}

function recentthread_is_installed()
{
    require_once "recentthreads/db.php";
    return recentthread_db_is_installed();
}

function recentthread_activate()
{
    require_once "recentthreads/templates.php";
    recentthread_templates_install();
    require_once "recentthreads/theme.php";
    recentthread_theme_install();
}

function recentthread_deactivate()
{
    require_once "recentthreads/templates.php";
    recentthread_templates_uninstall();
    require_once "recentthreads/theme.php";
    recentthread_theme_uninstall();
}

function recentthread_uninstall()
{
    require_once "recentthreads/db.php";
    recentthread_db_uninstall();
    require_once "recentthreads/settings.php";
    recentthread_settings_uninstall();
}
