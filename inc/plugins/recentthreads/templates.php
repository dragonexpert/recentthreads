<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_templates_install()
{
    global $db, $config;
    $new_template_group = array(
        "prefix" => "recentthread",
        "title" => "<lang:recentthreads_template>",
        "isdefault" => 0
    );

    $db->insert_query("templategroups", $new_template_group);

    // Now go through each of the themes
    $themequery = $db->simple_select("themes", "*");
    $sids = array();
    $first = true;
    $template_json = json_decode(file_get_contents("templates.json", true), true);
    while($theme = $db->fetch_array($themequery))
    {
        //$my_template = $my_new_template = array();
        $properties = unserialize($theme['properties']);
        $sid = $properties['templateset'];
        if(!in_array($sid, $sids))
        {
            array_push($sids, $sid);

            foreach ($template_json as $key)
            {
                $my_template[] = array(
                    "title" => $db->escape_string($key['title']),
                    "template" => $db->escape_string($key['template']),
                    "sid" => $sid,
                    "version" => '1824',
                    "dateline" => TIME_NOW);
               // $db->insert_query('templates', $my_template);
                if($first)
                {
                    $my_new_template[] = array(
                        "title" => $db->escape_string($key['title']),
                        "template" => $db->escape_string($key['template']),
                        "sid" => -2,
                        "version" => "1824",
                        "dateline" => TIME_NOW
                    );
                  //  $db->insert_query("templates", $my_new_template);
                }
            }
            // Now that that theme is done, insert all templates for that theme
            $db->insert_query_multiple("templates", $my_template);
            $db->insert_query_multiple("templates", $my_new_template);
            unset($my_template, $my_new_template);
            $first = false;
        }
    }


    // Stylesheet updates are required
    require_once MYBB_ROOT . $config['admin_dir'] . "/inc/functions_themes.php";
    $stylesheetquery = $db->simple_select("themestylesheets", "*", "name='thread_status.css'");
    while($stylesheet = $db->fetch_array($stylesheetquery))
    {
        $attachedto = explode("|", $stylesheet['attachedto']);
        if(!in_array("index.php", $attachedto))
        {
            $update_stylesheet['attachedto'] = $stylesheet['attachedto'] . "|index.php";
            $db->update_query("themestylesheets", $update_stylesheet, "sid=" . $stylesheet['sid']);
            update_theme_stylesheet_list($stylesheet['tid'], false, false);
        }
    }

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    $indexquery = $db->simple_select("templates", "*", "title IN('index', 'usercp_options')");
    $indexdone = $usercpdone = false;
    while($template_info = $db->fetch_array($indexquery))
    {
        if(!strpos($template_info['template'], "{\$recentthreadtable}") && $template_info['title'] == "index" && !$indexdone)
        {
            find_replace_templatesets('index', "#" . preg_quote('{$forums}') . "#i", "{\$forums}\n<div id=\"recentthreads\">{\$recentthreadtable}</div>");
            find_replace_templatesets('index', "#" . preg_quote('{$headerinclude}') . "#i", "{\$headerinclude}\n{\$recentthread_headerinclude}");
            $indexdone = true;
        }
        if(!strpos($template_info['template'], "{\$recentthread_option}") && $template_info['title'] == "usercp_options" && !$usercpdone)
        {
            find_replace_templatesets('usercp_options', "#" . preg_quote('{$board_style}') . "#i", "{\$recentthread_option}\n{\$board_style}");
            $usercpdone = true;
        }
    }
    $db->free_result($indexquery);
}

function recentthread_templates_uninstall()
{
    global $db;
    $db->delete_query("templates", "title LIKE 'recentthread%'");
    $db->delete_query("templategroups", "prefix='recentthread'");
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets('index', "#" . preg_quote("\n{\$recentthread_headerinclude}") . "#i", '');
    find_replace_templatesets('index', "#" . preg_quote("\n<div id=\"recentthreads\">{\$recentthreadtable}</div>") . "#i", '');
    find_replace_templatesets('usercp_options', "#" . preg_quote("{\$recentthread_option}\n") . "#i", '');
}
