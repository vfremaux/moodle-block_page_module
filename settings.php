<?php

$settings->add(new admin_setting_configcheckbox('pageindividualisationfeature', get_string('pageindividualisationfeature', 'block_page_module'),
                   get_string('configpageindividualisationfeature', 'block_page_module'), false));

$settings->add(new admin_setting_configcheckbox('individualizewithtimes', get_string('individualizewithtimes', 'block_page_module'),
                   get_string('configindividualizewithtimes', 'block_page_module'), true));
