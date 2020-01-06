# Site Notice
This plugin displays notices to users.

## Configuration 

### Enable the plugin
Site administration > Plugins > Local plugins > Site Notice > Settings: "Enabled"

### Allow updating notices
Site administration > Plugins > Local plugins > Site Notice > Settings: "Allow notice update"
If the config is enabled, user will be able to update existing notice.


### Allow deleting notices
Site administration > Plugins > Local plugins > Site Notice > Settings: "Allow notice deletion"
If the config is enabled, user will be able to delete existing notice.

### Clean up other related data when deleting a notice
Site administration > Plugins > Local plugins > Site Notice > Settings: "Clean up info related to the deleted notice"
If the config is enabled (and "notice deletion" is also allowed), when deleting a notice, other records related to the notice
 in hyperlinks, hyperlinks history, acknowledgement, user last view will also be deleted.

## Usage

### Create new notice
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on "Create New Notice"
* Enter Title
* Enter Content
* Set up reset interval ('reset every') if required. The notice will be displayed to user again once the specified period elapses.
* Requires Acknowledgement. If enabled, the user will need to accept the notice before they can continue to use the LMS site.
If the user does not accept the notice, he/she will be logged out of the site.
* Set up target audience (cohort) 

### Edit Notice (Requires "Allow notice update")
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on the gear icon to view existing notice.

### Disabled Notice
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on the 'eye' icon to disable/enable notice

### Reset Notice
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on the 'load/reload' icon to reset notice

### Delete Notice (Requires "Allow notice deletion")
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on the 'trash' icon to delete notice

### View/Download Notice Report
* Go to Manage Notices: Site administration > Plugins > Local plugins > Site Notice > Manage Notice
* Click on the 'chart' icon to view notice report
* Apply "Date range" filter if required
* Click on "Download in text format" to download csv report of the notice
