# Recent Threads On Index


Adds a section to your index page for recent threads.

**Installation**

1) Upload inc/plugins/recentthread.php to your inc/plugins directory.

2) Install from the Admin CP.

3) Verify that the variable {$recentthread_headerinclude} is in the head section of your index template.  Verify that the variable {$recentthreadtable} is below {$forums}, unless you want it elsewhere on the page.

4) Configure the settings for recent threads.

Compatibility: 1.8.1 or higher.  This is due to functions that 1.8 uses in order to format avatars.

**Upgrading**
There is not an upgrade script to switch to version 16.  This is due to major file structure changes.  The good news is that now it is much easier to edit since everything is separated based on what it does. Starting with version 17, there will once again be an upgrade script available from version 16.  You will need to uninstall then reinstall.

**Customization**

The following two variables are not currently used, but will work in case you want them:  
{$lastpostdate} - This uses the default date format on the last post.  
{$lastposttime} - This uses the default time format on the last post.

By default the page will update the recent threads every 30 seconds and stop refreshing ater 15 minutes of no activity.  This can be altered under templates -> your theme -> recentthread_headerinclude.  On the line that begins with var refresher = Change 30000 to the number of milliseconds you want it to refresh at.  60000 = 1 minute.  The next line controls how many milliseconds before it will stop refreshing at.  5 minutes = 300000; 10 minutes = 600000.  Note that you do not use commas.  

If you wish to move the thread list to the top, go to templates -> your theme -> index templates -> index and move {$recentthreadtable} to where you want it.  

There are currently 10 settings that you can configure to make your experience how you want it.  This includes an option to work with XThreads.
