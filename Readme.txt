This module is NOT for direct use. 
It is part of the Page Course Format implementation.

Increment 4.5.0004 (2025120100)
===================================
block_page_module: do not request the format_page renderer outside a format_page course page (fixes noformatpagecourse exception on site-level and report pages)

Increment 4.5.0005 (2025120100)
===================================
Fix php8.3 compatibility related to undeclared class attributes.

Increment 4.5.0006 (2025120100)
===================================
block_page_module: do not rebuild the course cache at each view for course modules that do not belong to the course or do not exist anymore (rebuild at most once per request, and only for a cm of the current course). Ignore page references pointing to pages of another course.

Increment 4.5.0007 (2025120100)
===================================
A faulty page item hook (mod/<name>/pageitem.php or format_page plugin) can no more break the whole course page : any error thrown by the hook is logged and the block falls back to the standard module rendering.

Increment 4.5.0008 (2025120100)
===================================
Fix "Cannot access protected property block_page_module::$cm" : the context attributes (cm, module, moduleinstance, course, coursepage, baseurl, modinfo, coursemodinfo, completioninfo) were made protected by the php8.3 compatibility fix of 4.5.0005, but they are read by the pageitem hooks of the activity modules (customlabel, learningtimecheck, etc.) through the block reference. They are public again.