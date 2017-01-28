WP Project Manager - Frontend
Version: 1.0
Requirements: Minimum version 0.4 of "WP Project Manager" is required.
==============================

Brings "WP Project Manager" functionality in frontend.

Usage:
==============================
1. [cpm] - Use this shortcode in any page, it'll display all the projects.
2. [cpm id="PROJECT_ID"] - To show a specific project, replace the PROJECT_ID with your project ID.


FAQ:
==============================

Q. How do I know the project ID?
--------------------------------
A. Hover your mouse over a project. You will see a URL something like this:
    http://example/wp-admin/admin.php?page=cpm_projects&tab=project&action=single&pid=1750

    1750 is your project ID.

Q. If I use [cpm] shortcode, can everyone see every project?
----------------------------------------------------------------
A. No, users can see only the projects they are assigned to.

Q. Why project styles in frontend is broken?
---------------------------------------------
A. Different themes displays the styles differently. We can't guarantee it will show exactly the same
    as the backend. If the styles are broken, you've to fix it yourself. Frontend style is tested with
    Twenty Eleven and Twenty Twelve theme and it works fine.

Q. When I reply from the admin panel, URLs in the E-Mail points to admin panel project URL.
------------------------------------------------------------------------------------------------
A. If you make a comment or create a new message or assign a task to someone from admin panel, all 
    the URLs in E-Mail will point to the admin panel URL by default. Frontend plugin will not alter
    those URLs. Responses made from frontend will only have the frontend pointing URLs.

Q. How do I get the Frontend plugin update?
---------------------------------------------
A. When a new version of the plugin will release, you'll get a update notification on your admin panel.
    You'll be able to download from http://wedevs.com/ if you purchased the plugin.


Changelog:
==============================

v1.0
---------
* initial version released