moodle-block_page_tracker
=========================

Page tracker tracks page visits and provid smart monitoring and page backlinks

Version 2016030701

changes the defaults when adding a new instance.


Version 2013040100 adds :

Smarter management of page depth
Complete/Uncomplete level marker

Version 2016101105 (X.X.1)

Completely redefines the page tracking model adding a dedicating tracking 
table rather then scanning logs as new logstore log table has become too
heavy to deal with.

New page tracking now uses events to catch the page view event and add a track
mark to the tracking table.