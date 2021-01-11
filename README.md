# Recent Threads On Index


Adds a section to your index page for recent threads.

**Installation**

1) Upload the entire zip file to your forums directory.  All files should extract to the proper location.  Note that the directory Recent Threads in the inc/plugins folder needs to be uploaded as well.

2) Install from the Admin CP.

3) Verify that the variable {$recentthread_headerinclude} is in the head section of your index template.  Verify that the variable {$recentthreadtable} is below {$forums}, unless you want it elsewhere on the page.

4) Configure the settings for recent threads.

Compatibility: 1.8.1 or higher.  This is due to functions that 1.8 uses in order to format avatars.

**Upgrading**
If you are using a version that is 16 or lower, you will need to uninstall then reinstall the plugin in order to get all the necessary changes due to major file structure changes.  If you are using version 17 or higher, you can upgrade by either clicking 'Run Upgrade Script' or 'Run Maintenance Script'.  Future builds will likely not include changes in either the upgrade script or maintenance script beginning with version 20 due to making them be automated instead of being manually written for each upgrade.  The only changes would be if a database change occurs since that is not logged anywhere.

**Customization**

The following two variables are not currently used, but will work in case you want them:  
{$lastpostdate} - This uses the default date format on the last post.  
{$lastposttime} - This uses the default time format on the last post.  

These variables exist if you have the setting to show thread creation date on:  
{$create_date} - Shows the date a thread was created.  
{$create_time} - Shows the time a thread was created.  
{$create_string} - Created on {$create_date} at {$create_time}.  This variable is defined in inc/languages/english/recentthreads.lang.php.  

Additional Variables:  
{$folder} - What class the thread should be.  
{$folder_label} - The title of the folder to be used on a title tag.  
{$icon} - The icon the thread has.  
{$recenttprefix} - THe formatted prefix of the thread.  
{$recentthread_breadcrumbs} - Either the breadcrumbs of the forums that the thread resides in or just the forum it resides in depending on your settings.  
{$lastavatar} - The last posters avatar formatted. Uses template recentthread_avatar.  
{$posteravatar} - The thread author's avatar formatted. Uses template recentthread_last_avatar.  
{$thread['author']} - The thread creators profile link.  Formatted according to usergroup if option is set.  

By default the page will update the recent threads every 30 seconds and stop refreshing after 15 minutes of no activity.  This can be altered under templates -> your theme -> recentthread_headerinclude.  On the line that begins with var refresher = Change 30000 to the number of milliseconds you want it to refresh at.  60000 = 1 minute.  The next line controls how many milliseconds before it will stop refreshing at.  5 minutes = 300000; 10 minutes = 600000.  Note that you do not use commas.  
  
Please note that if you want to apply changes to templates across all themes, you should edit inc/plugins/recentthreads/templates.json.  You should only edit the attribute "template"; the other attributes must stay the same or stuff will break.  If you need to use double quotes in the template, you need to escape it with the '\' character or errors will occur.  These are the templates that are loaded when you install.  Starting in version 20, there will also be a way to update all your recent thread templates to whatever is in the .json file.

If you wish to move the thread list to the top, go to templates -> your theme -> index templates -> index and move {$recentthreadtable} to where you want it.  

There are currently 16 settings that you can configure to make your experience how you want it.  This includes an option to work with XThreads.

**Showing On Other Pages**  
  
Due to popular request, the plugin has now been updated to show the recent threads on any page.  This example will show you how to display it on forumdisplay.php.  Follow similar steps for other pages.
1) Go to Recent Thread Settings.  
2) Change the value of Show On These Pages to be  
index.php  
forumdisplay.php  
3) Go to Templates -> Templates -> Your Theme -> Forum Display Templates -> forumdisplay.
4) Below {$headerinclude} add {$recentthread_headerinclude}.
5) The place you want to show the threads put \<div id="recentthreads">{$recentthreadtable}\</div>

**Support**
For support with the plugin, please post in [this thread](https://community.mybb.com/thread-159857.html).  This way the Github repository is kept clean.  Please note that if your forum does not meet eligibility requirements, you may not receive support.  Prior to posting in the thread, try running the maintenance script because that is able to fix a lot of issues.

**Bugs**
Please report bugs in [this thread](https://community.mybb.com/thread-159857.html) first.  This way I can see if it is a new issue.  Please make sure to post your forum url and that guests are able to view Recent Threads.  If it is confirmed to be an actual bug, myself or Eldenroot will create an issue for it.

**Enhancements**
If you have an idea for an enhancement, please create an issue.  Be as specific as possible and try and search closed issues because certain things I am not willing to do.

**Donations**  
This plugin will continue to remain free.  If you would like to make a donation to help, you can do so [here](https://paypal.me/MarkJanssen?locale.x=en_US)
