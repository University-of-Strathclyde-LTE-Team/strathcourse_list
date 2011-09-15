README
======

This a replacement for the My Courses block in Moodle 1.9.

Features
--------

* User Preference to display either list of courses user is enrolled on or a search box
* Observes Admin settings to display admin's courses or all courses
* Can fetch a list of classes for a user from another Moodle server for accessing archived classes
(this is Strathclyde specific feature and requires an web service to be available on the archive server).

Installation
------------
1. Download
2. Copy to <moodledir>/blocks/strathcourse_list
3. Goto moodle notifications page (/admin/)
4. Course List (Strathclyde) can be added as block to any page

Replacing Moodle My Courses Block
---------------------------------
The following SQL statements *should* cause all My Courses blocks to change...use at your own risk

1. Get the ID of the Strathcourse_list block
    SELECT id FROM `classes_michael`.`mdl_block` where name ='strathcourse_list';
2. Get the ID of the regular Course list block
    SELECT id FROM `classes_michael`.`mdl_block` where name ='course_list';
3. Update all instances of course_list blocks to be strathcourse_list blocks
    UPDATE mdl_block_instance bi SET bi.blockid = <id from 1.> WHERE bi.blockid = <id from 2.>;

You should repeat this with the mdl_block_pinned table if you want to update sticky blocks.