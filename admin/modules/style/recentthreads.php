<?php
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
if($mybb->input['action'] == "download_templates")
{
    $file = "templates.json";
    if(file_exists($file))
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        flash_message($lang->recentthreads_file_downloaded_success, "success");
        admin_redirect("index.php?module=style-recentthreads");
        exit;
    }
    else
    {
        flash_message($lang->recentthreads_file_doesnt_exist, "error");
        admin_redirect("index.php?module=style-recentthreads");
        exit;
    }
}
$page->output_header("Recent Thread Template Tool");

$sub_tabs['main'] = array(
    "title" => $lang->recentthreads_main,
    "link" => "index.php?module=style-recentthreads",
    "description" => ""
);

$sub_tabs['update_templates'] = array(
    "title" => $lang->recentthreads_update_templates,
    "link" => "index.php?module=style-recentthreads&action=update_templates",
    "description" => ""
);

$sub_tabs['export_templates'] = array(
    "title" => $lang->recentthreads_export_templates,
    "link" => "index.php?module=style-recentthreads&action=export_templates",
    "description" => ""
);

$sub_tabs['download_templates'] = array(
    "title" => $lang->recentthreads_download_templates,
    "link" => "index.php?module=style-recentthreads&action=download_templates",
    "description" => ""
);


if($mybb->input['action'] == "update_templates")
{
    $page->output_nav_tabs($sub_tabs, "update_templates");

    $template_json = json_decode(file_get_contents(MYBB_ROOT . "/inc/plugins/recentthreads/templates.json", true), true);

    // Perform a check in case JSON is not valid.
    if(!is_null($template_json))
    {
        foreach ($template_json as $my_template)
        {
            $update_template = array(
                "template" => $db->escape_string($my_template['template']),
                "dateline" => TIME_NOW
            );
            $title = $db->escape_string($my_template['title']);
            $db->update_query("templates", $update_template, "title='" . $title . "'");
        }
        flash_message($lang->recentthreads_templates_updated, "success");
        admin_redirect("index.php?module=style-recentthreads");
    }
    else
    {
        flash_message(json_last_error_msg(), "error");
        admin_redirect("index.php?module=style-recentthreads");
    }
}
if($mybb->input['action'] == "export_templates")
{
    $page->output_nav_tabs($sub_tabs, "export_templates");

    if($mybb->request_method == "post")
    {
        $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
        $themequery = $db->simple_select("themes", "*", "tid=" . $tid);
        $theme = $db->fetch_array($themequery);
        $db->free_result($themequery);
        $theme['properties'] = my_unserialize($theme['properties']);
        $sid = $theme['properties']['templateset'];

        // Now we have the sid of the templates so we can retrieve them.
        $template_query = $db->simple_select("templates", "*", "sid = " . $sid . " AND title LIKE 'recentthread%'");
        $regular_array = array();
        while($template = $db->fetch_array($template_query))
        {
            $regular_array[$template['title']] = array(
                "title" => $template['title'],
                "template" => $template['template']
            );
        }
        $json_array = json_encode($regular_array);
        if($json_array !== false)
        {
            $fstream = fopen("templates.json", "w+", false);
            fwrite($fstream, $json_array);
            fclose($fstream);
            flash_message($lang->recentthreads_export_success, "success");
            admin_redirect("index.php?module=style-recentthreads");
        }
        else
        {
            // The json encode failed.
            flash_message($lang->recentthreads_export_error, "error");
            admin_redirect("index.php?module=style-recentthreads");
        }
    }
    else
    {
        // Figure out what theme they want to export the templates for.
        $query = $db->simple_select("themes", "*");
        $themearray = array();
        while($theme = $db->fetch_array($query))
        {
            $themearray[$theme['tid']] = $theme['name'];
        }
        $db->free_result($query);
        $form = new DefaultForm("index.php?module=style-recentthreads&action=export_templates", "post");
        $form_container = new FormContainer("export_templates");
        $form_container->output_row("Theme ", "Which theme would you like to export?", $form->generate_select_box("tid", $themearray, 1), "tid");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button($lang->recentthreads_export_templates)));
        $form->end();
    }
}

if(!$mybb->input['action'])
{
    $page->output_nav_tabs($sub_tabs, "main");
}
$page->output_footer();
