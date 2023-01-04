![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/catalyst/moodle-local_sitenotice/ci.yml?branch=MOODLE_39_STABLE)

# Site Notice
This plugin displays notices to users.

## Features
 * Site wide notices displayed in a modal pop up.
 * Ability to limit your notices to a specific cohort.
 * Re display a notice in a configurable period of time.
 * Set a start date and expiry date for a notice
 * Keep displaying a notice until a specific course is completed.
 * Optionally request users to accept a notice, or they will be logged out from LMS.
 * Force users to be logged out after seeing a notice.
 * Reporting on who accepted / dismissed a notice. 

## Configuration 

### Enable the plugin
Site administration > Site Notice > Settings: "Enabled"

### Allow updating notices
Site administration > Site Notice > Settings: "Allow notice update"
If the config is enabled, user will be able to update existing notice.

### Allow deleting notices
Site administration > Site Notice > Settings: "Allow notice deletion"
If the config is enabled, user will be able to delete existing notice.

### Clean up other related data when deleting a notice
Site administration > Site Notice > Settings: "Clean up info related to the deleted notice"
If the config is enabled (and "notice deletion" is also allowed), when deleting a notice, other records related to the notice
 in hyperlinks, hyperlinks history, acknowledgement, user last view will also be deleted.

## Usage

### Create new notice
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on "Create New Notice"
* Enter Title
* Enter Content
* Set up reset interval ('reset every') if required. The notice will be displayed to user again once the specified period elapses.
* Set up start and end dates if required. Set "Is perpetual" to "Yes" and start/end dates will become available.
* Requires Acknowledgement: If enabled, the user will need to accept the notice before they can continue to use the LMS site.
* Forece logout: If enabled, the user will be logged out of the site after closing the notice.
If the user does not accept the notice, he/she will be logged out of the site.
* Set up target cohort(s). 

### Edit Notice (Requires "Allow notice update")
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the gear icon to view existing notice.

### Disabled Notice
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the 'eye' icon to disable/enable notice

### Reset Notice
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the 'load/reload' icon to reset notice

### Delete Notice (Requires "Allow notice deletion")
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the 'trash' icon to delete notice

### View/Download Notice Acknowledgement Report
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the 'chart' icon to view notice report
* Apply "Date range" filter if required
* Choose a file format and click on download button

### View/Download Notice Dismiss Report
* Go to Manage Notices: Site administration > Site Notice > Manage Notice
* Click on the 'risk' icon to view notice report
* Apply "Date range" filter if required
* Choose a file format and click on download button

# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/


# Contributing and Support

Issues, and pull requests using github are welcome and encouraged!

https://github.com/catalyst/moodle-local_sitenotice/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us

