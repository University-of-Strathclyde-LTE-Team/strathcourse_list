<?PHP 
/*
 This file is part of block_strathcourse_list.

    block_strathcourse_list is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    block_strathcourse_list is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with block_strathcourse_list.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Parts of this file may be based on Moodle code
 * Copyright © 1999 onwards, Martin Dougiamas
 * and many other contributors.
 * GNU Public License
 */

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/auth/pegasus/auth.php');
require_once($CFG->libdir . '/filelib.php');
define('BLOCK_STRATHCOURSE_LIST_PROG_SITE','/^[\d]{4}-[\d]{1}-[\d]{1}/');

class block_strathcourse_list extends block_list {
    var $degree_course_instance =array();
    var $userIsLta= false;
    var $showArchives= false;
    function init() {
        global $USER;
        $this->title = get_string('courses').'(Strathclyde)';
        $this->version = 2011091400;
        
        /*
	    if ($r = get_record('role','shortname','lta') ) {
			$this->userIsLta = user_has_role_assignment($USER->id, $r->id); 
        }*/
        
        if (!empty($_POST['block_strathcourse_list_updatepref'])) {
        	$ui_opt = optional_param('block_strathcourse_list_search_ui',false);
        	//echo "UI:$ui_opt.";
       		switch(strtolower($ui_opt)) {
	        	case 'on':
	        		set_user_preference('block_strathcourse_list_showsearch_ui', true);
	        		break;
	        	default:
	        		set_user_preference('block_strathcourse_list_showsearch_ui', false);
	        		break;
	        }
	        $archive_opt = optional_param('block_strathcourse_list_show_archives',false);
			switch(strtolower($archive_opt)) {
	        	case 'on':
	        		set_user_preference('block_strathcourse_list_show_archives', true);
	        		break;
	        	default:
	        		set_user_preference('block_strathcourse_list_show_archives', false);
	        		break;
	        }
        }
        $this->userIsLta = get_user_preferences('block_strathcourse_list_showsearch_ui',false);
        $this->showArchives=get_user_preferences('block_strathcourse_list_show_archives',false);
    }
    
    function has_config() {
        return true;
    }
    function specialization() {
        global $CFG;
        //$this->title = "<img src=\"$CFG->wwwroot/blocks/strathcourse_list/course.gif\" class=\"strathcourse\" alt=\"".get_string("coursecategory")."\" />Courses";            
        if ($this->userIsLta) {
            $this->title= get_string('ltacourses','block_strathcourse_list');
        }
        else {
            $this->title= get_string('mycourses');
        }
    }
    function hide_header() {
        return false;//$this->degree_course_instance;
    }   
     
    function get_content() {
        global $THEME, $CFG, $USER;

        if($this->content !== NULL) {
            return $this->content;
        }
		$strSavePrefs = get_string('savepreferences');
		$strHideList = get_string('hide_course_list','block_strathcourse_list');
		$strShowArchives = get_string('show_archive_servers','block_strathcourse_list');
		$ui_checked = $this->userIsLta?"checked":'';
		$archived_checked = $this->showArchives?"checked":'';
		$strPrefForm = "<form action='' method='post'>{$strHideList}<input type='hidden' name='block_strathcourse_list_updatepref' value='1' /><input type='checkbox' name='block_strathcourse_list_search_ui' {$ui_checked}/><br/>
		{$strShowArchives}<input type='checkbox' name='block_strathcourse_list_show_archives' {$archived_checked}/>
		<input type='submit' value='{$strSavePrefs}'/></form>";
        
        if ($this->userIsLta) {
            $this->content = new stdClass;
            $searchbox = "<form action='{$CFG->wwwroot}/course/search.php'><input type='text' name='search' /><input type='submit' value='Search'/><input type='reset' value='Reset'/></form>";
            $this->content->items = array(
                //"<a href=\"$CFG->wwwroot/course/search.php\">".get_string("search")."</a>",
                $searchbox,
                "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a>"
            );
            $this->content->icons = array('','');
            if ($this->showArchives) {
            	$this->get_archive_courses();
            }

            $this->content->footer = $strPrefForm;
            return $this->content;
        }
        

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $icon  = "";//"<img src=\"$CFG->wwwroot/blocks/strathcourse_list/course.gif\"".
                 //" class=\"strathcourse\" alt=\"".get_string("coursecategory")."\" />";
       
        $adminseesall = true;
        if (isset($CFG->block_course_list_adminview)) {
           if ( $CFG->block_course_list_adminview == 'own'){
               $adminseesall = false;
           }
        }
        $this->degree_course_instance = array();
        if (empty($CFG->disablemycourses) and 
            !empty($USER->id) and 
            !(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
            !isguest()) {    // Just print My Courses
            	/*
            $courses = get_user_courses_bycap($USER->id, 'moodle/course:view', $accessinfo,
                                      $doanything, $sort, $fields,
                                      $limit);
	*/
            if ($courses= $this->get_user_courses($USER->id,'visible DESC, fullname ASC')) {
            //get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
				//print_object($courses);            	
                $counter = 0;
                foreach ($courses as $course) {
                    //echo $course->idnumber;
                    if ($course->id == SITEID) {
                        continue;
                    }
                    if (preg_match(BLOCK_STRATHCOURSE_LIST_PROG_SITE,$course->idnumber)) {
                        //found the user's course class
                        $this->degree_course_instance[] = $course;
                        continue;
                    }
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    //$link = "<span $linkcss>".format_string($course->fullname) .' ' . get_string('notavailable_to_students','block_strathcourse_list').'</span>';
                    //if ($course->visible) {
                    	$link ="<a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a>";
                    //}
                    $this->content->items[] = $link;
                    $this->content->icons[]=$icon;
                    $counter++;
                }
                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
               // if (count($this->content->items) >20) {
	                
                //}
            }
            $this->content->footer .=$strPrefForm;
            $this->get_remote_courses();
            if ($this->showArchives) {
            	$this->get_archive_courses();
            }
            $this->display_degree_course();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }
        $this->degree_course_instance = false;
        $categories = get_categories("0");  // Parent = 0   ie top-level categories only
        if ($categories) {   //Check we have categories
            if (count($categories) > 1 || (count($categories) == 1 && count_records('course') > 200)) {     // Just print top level category links
                foreach ($categories as $category) {
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss href=\"$CFG->wwwroot/course/category.php?id=$category->id\">" . format_string($category->name) . "</a>";
                    $this->content->icons[]=$icon;
                }
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $this->title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    foreach ($courses as $course) {
                        ///echo $course->idnumber;
                        if ($course->id == SITEID) {
                            continue;
                        }
                        if (preg_match(BLOCK_STRATHCOURSE_LIST_PROG_SITE,$course->idnumber)) {
                            //found the user's course class
                            $this->degree_course_instance[] = $course;
                            
                            continue;
                        }
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                        if ($course->visible) {
							$link = "<a $linkcss title=\""
                                   . format_string($course->shortname)."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" 
                                   .  format_string($course->fullname) . "</a>";
                        }
                        else {
                        	$link = format_string($course->fullname);
                        }
                        $this->content->items[]=$link;
                        $this->content->icons[]=$icon;
                    }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                    
                    $this->get_remote_courses();
                } else {
                    
                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', get_context_instance(CONTEXT_COURSECAT, $category->id))) {
                        $this->content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }
                   
                    $this->get_remote_courses();
                }
                $this->title = get_string('courses');
            }
        }
            if ($this->showArchives) {
            	$this->get_archive_courses();
            }
        
        //$this->get_archive_courses();
        /*
        if ($this->degree_course_instance) {
//	    array_unshift($this->content->icons,'');
            array_unshift($this->content->items, 
                '<h3>Degree Programme Site</h3>',
                "<a $linkcss title=\"" . format_string($this->degree_course_instance>shortname) . "\" ".
                           "href=\"$CFG->wwwroot/course/view.php?id={$this->degree_course_instance->id}\">" . format_string($this->degree_course_instance>fullname) . "</a>",
                '<div class="title">Classes</div>'
           );
            array_unshift($this->content->icons[],$icon);
        }
        */
        $this->display_degree_course();
        return $this->content;
    }
    
    function get_archive_courses() {
        global $CFG, $USER;
        //print_object($CFG->block_course_list_archiveservers);
        $archiveservers = split("\n",$CFG->block_course_list_archiveservers);
        $archiveservernames = split("\n",$CFG->block_course_list_archiveserversnames);
        //print_object($archives);
        $auth = new auth_plugin_pegasus();
        if ($archiveservers) {
		$courses = array();
		
            //foreach($archives as $server) {
            for($i = 0; $i < count($archiveservers);$i++) {
                $servername = $archiveservernames[$i];
                $server =$archiveservers[$i];
                if ($server != "") {
                	$servertype = strpos($server, 'spider') !== false ? 'spider' : 'moodle';
                	if($servertype == 'spider') {
                		$url = 'http://'.$server.'/spider/interface/getClasses_myplace.php';
                		$username = str_replace('@strath.ac.uk', '', $USER->username);
                		$req = $url.'?username='.$username;
                	} else {
	                    $url = 'http://'.$server.'/auth/pegasus/classes.php';
	                    $mac_params['username'] = $USER->username;
	                    $mac_params['timestamp'] = time();
	                    $mac = $auth->getMAC($mac_params);
	                    //$mac_params['mac'] = $mac;
	                    
                    	$req= $url.'?username='.$mac_params['username'].'&timestamp='.$mac_params['timestamp'].'&mac='.$mac;
                	}
                	
                    $result = download_file_content($req, null, null, false, 5);
                    // Show error message if we don't get content
                    if($result === false) {
                    	$courses[$servername][]= "Server unavailable.";
                    	continue;
                    }
                    $lines = split("\n",$result);
                    //first one is always the result line
                    $lines = array_slice($lines,1);
                    $course[$servername] = array();
                    foreach($lines as $line) {
                        if ($line != '') {
                            $csv = str_getcsv($line);
                            //p($line);
                            if($servertype == 'spider') {
                            	if(!isset($csv[0])) {
                            		continue;
                            	}
                            	$code = trim($csv[0]);
                            	if(!isset($csv[1])) {
                            		$title = $code;
                            	} else {
                            		$title = $code.': '.trim($csv[1]);
                            	}
                            	$courses[$servername][]='<a title="'.$title.'" href="http://'.
                            	    $server.'/spider/spider/showClass.php?class='.$code.'">'.
                            	    $title. '</a>';
                            } else {
	                            $courses[$servername][]="<a title=\"".format_string($csv[3])."\" ".
	                               "href=\"http://{$server}/course/view.php?id={$csv[2]}$\">" 
	                               .  format_string($csv[3]) . "</a>";
                            }
                        }
                    }	
                }   	
            }
        }
	if (count($courses) >0) {
		$this->content->items[]='<div class="header">Previous Years</div>';
		$this->content->icons[] ='';
		foreach($courses as $servername=>$stu_courses) {
            $this->content->items[]='<strong>'.$servername.'</strong>';
            $this->content->icons[] ='';
            foreach($stu_courses as $c) {
                $this->content->items[] = stripslashes($c);
                $this->content->icons[] = '';
            }
		}
	}
    }

    function display_degree_course() {
        global $CFG;
       // print_r($this->degree_course_instance);
        $strDegProg = get_string('degreeprogrammesite','block_strathcourse_list');
        $strClasses = get_string('courses');
		if ($this->degree_course_instance && count($this->degree_course_instance)>0){
			foreach($this->degree_course_instance as $d){
	       	 	$linkcss ='';
	        	$linkcss = $d->visible ? "" : " class=\"dimmed\" ";;
	        	if ($d->visible) {
	        	$link ="<a $linkcss title=\"" . format_string($d->shortname) . "\" ".
	                           "href=\"$CFG->wwwroot/course/view.php?id={$d->id}\">" . format_string($d->fullname) . "</a>";
	        	}
	        	else {
	        		$link = format_string($d->fullname);
	        	}
				array_unshift($this->content->icons,'');
	
	            array_unshift(
	                $this->content->items, 
	                $link
	            );
			}
			array_unshift($this->content->icons,'');
			array_unshift(
                $this->content->items, 
                "<div class='block_strathcourse_list_classes'>{$strClasses}</div>"
			);
		}
    }
    function get_remote_courses() {
        global $THEME, $CFG, $USER;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon  = '<img src="'.$CFG->pixpath.'/i/mnethost.gif" class="icon" alt="'.get_string('course').'" />';

        // only for logged in users!
        if (!isloggedin() || isguest()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[]="<a title=\"" . format_string($course->shortname) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">" 
                    . format_string($course->fullname) . "</a>";
                $this->content->icons[]=$icon;
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotemoodles','mnet'); 
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$somehost['name'].'</a>';
                $this->content->icons[] = $icon;
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }
function get_user_courses($userid, $sort='visible DESC,sortorder ASC', $fields=NULL, $doanything=false,$limit=0) {

    global $CFG,$USER;

    // Guest's do not have any courses
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('moodle/legacy:guest',$sitecontext,$userid,false)) {
        return(array());
    }

    $basefields = array('id', 'category', 'sortorder',
                        'shortname', 'fullname', 'idnumber',
                        'teacher', 'teachers', 'student', 'students',
                        'guest', 'startdate', 'visible',
                        'newsitems',  'cost', 'enrol',
                        'groupmode', 'groupmodeforce');

    if (!is_null($fields) && is_string($fields)) {
        if (empty($fields)) {
            $fields = $basefields;
        } else {
            // turn the fields from a string to an array that
            // get_user_courses_bycap() will like...
            $fields = explode(',',$fields);
            $fields = array_map('trim', $fields);
            $fields = array_unique(array_merge($basefields, $fields));
        }
    } elseif (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        $fields = $basefields;
    }

    $orderby = '';
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    //
    // Logged-in user - Check cached courses
    //
    // NOTE! it's a _string_ because
    // - it's all we'll ever use
    // - it serialises much more compact than an array
    //   this a big concern here - cost of serialise
    //   and unserialise gets huge as the session grows
    //
    // If the courses are too many - it won't be set
    // for large numbers of courses, caching in the session
    // has marginal benefits (costs too much, not
    // worthwhile...) and we may hit SQL parser limits
    // because we use IN()
    //
    if ($userid === $USER->id) {
        if (isset($USER->loginascontext)
            && $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
            // list _only_ this course
            // anything else is asking for trouble...
            $courseids = $USER->loginascontext->instanceid;
        } 
        /*elseif (isset($USER->mycourses)
                  && is_string($USER->mycourses)) {
            if ($USER->mycourses === '') {
                // empty str means: user has no courses
                // ... so do the easy thing...
                return array();
            } else {
                $courseids = $USER->mycourses;
            }
        }*/
        if (isset($courseids)) {
//echo $courseids;
            // The data massaging here MUST be kept in sync with
            // get_user_courses_bycap() so we return
            // the same...
            // (but here we don't need to check has_cap)
            $coursefields = 'c.' .join(',c.', $fields);
            $sql = "SELECT $coursefields,
                           ctx.id AS ctxid, ctx.path AS ctxpath,
                           ctx.depth as ctxdepth, ctx.contextlevel AS ctxlevel,
                           cc.path AS categorypath
                    FROM {$CFG->prefix}course c
                    JOIN {$CFG->prefix}course_categories cc
                      ON c.category=cc.id
                    JOIN {$CFG->prefix}context ctx
                      ON (c.id=ctx.instanceid AND ctx.contextlevel=".CONTEXT_COURSE.")
                    WHERE c.id IN ($courseids)
                    $orderby";
            $rs = get_recordset_sql($sql);
            $courses = array();
            $cc = 0; // keep count
            while ($c = rs_fetch_next_record($rs)) {
                // build the context obj
                $c = make_context_subobj($c);

                if ($limit > 0 && $cc >= $limit) {
                    break;
                }
                
                $courses[$c->id] = $c;
                $cc++;
            }
            rs_close($rs);
            return $courses;
        }
    }

    // Non-cached - get accessinfo
    if ($userid === $USER->id && isset($USER->access)) {
        $accessinfo = $USER->access;
    } else {
        $accessinfo = get_user_access_sitewide($userid);
    }


    $courses = get_user_courses_bycap($userid, 'moodle/course:view', $accessinfo,
                                      $doanything, $sort, $fields,
                                      $limit);

    $cats = NULL;
    // If we have to walk category visibility
    // to eval course visibility, get the categories
    if (empty($CFG->allowvisiblecoursesinhiddencategories)) {
        $sql = "SELECT cc.id, cc.path, cc.visible,
                       ctx.id AS ctxid, ctx.path AS ctxpath,
                       ctx.depth as ctxdepth, ctx.contextlevel AS ctxlevel
                 FROM {$CFG->prefix}course_categories cc
                 JOIN {$CFG->prefix}context ctx ON (cc.id = ctx.instanceid)
                WHERE ctx.contextlevel = ".CONTEXT_COURSECAT."
             ORDER BY cc.id";
        $rs = get_recordset_sql($sql);

        // Using a temporary array instead of $cats here, to avoid a "true" result when isnull($cats) further down
        $categories = array();
        while ($course_cat = rs_fetch_next_record($rs)) {
            // build the context obj
            $course_cat = make_context_subobj($course_cat);
            $categories[$course_cat->id] = $course_cat;
        }
        rs_close($rs);

        if (!empty($categories)) {
            $cats = $categories;
        }

        unset($course_cat);
    }
    //
    // Strangely, get_my_courses() is expected to return the
    // array keyed on id, which messes up the sorting
    // So do that, and also cache the ids in the session if appropriate
    //
    $kcourses = array();
    $courses_count = count($courses);
    $cacheids = NULL;
    $vcatpaths = array();
    if ($userid === $USER->id && $courses_count < 500) {
        $cacheids = array();
    }
    for ($n=0; $n<$courses_count; $n++) {

        //
        // Check whether $USER (not $userid) can _actually_ see them
        // Easy if $CFG->allowvisiblecoursesinhiddencategories
        // is set, and we don't have to care about categories.
        // Lots of work otherwise... (all in mem though!)
        //
        $cansee = true;//false;
        if (is_null($cats)) { // easy rules!
            if ($courses[$n]->visible == true) {
                $cansee = true;
            } elseif (has_capability('moodle/course:viewhiddencourses',
                                     $courses[$n]->context, $USER->id)) {
                $cansee = true;
            }
        } else {
            //
            // Is the cat visible?
            // we have to assume it _is_ visible
            // so we can shortcut when we find a hidden one
            //
            $viscat = true;
            $cpath = $courses[$n]->categorypath;
            if (isset($vcatpaths[$cpath])) {
                $viscat = $vcatpaths[$cpath];
            } else {
                $cpath = substr($cpath,1); // kill leading slash
                $cpath = explode('/',$cpath);
                $ccct  = count($cpath);
                for ($m=0;$m<$ccct;$m++) {
                    $ccid = $cpath[$m];
                    if ($cats[$ccid]->visible==false) {
                        $viscat = false;
                        break;
                    }
                }
                $vcatpaths[$courses[$n]->categorypath] = $viscat;
            }

            //
            // Perhaps it's actually visible to $USER
            // check moodle/category:viewhiddencategories
            //
            // The name isn't obvious, but the description says
            // "See hidden categories" so the user shall see...
            // But also check if the allowvisiblecoursesinhiddencategories setting is true, and check for course visibility
            if ($viscat === false) {
                $catctx = $cats[$courses[$n]->category]->context;
                if (has_capability('moodle/category:viewhiddencategories', $catctx, $USER->id)) {
                    $vcatpaths[$courses[$n]->categorypath] = true;
                    $viscat = true;
                } elseif ($CFG->allowvisiblecoursesinhiddencategories && $courses[$n]->visible == true) {
                    $viscat = true;
                }
            }

            //
            // Decision matrix
            //
            if ($viscat === true) {
                if ($courses[$n]->visible == true) {
                    $cansee = true;
                } elseif (has_capability('moodle/course:viewhiddencourses',
                                        $courses[$n]->context, $USER->id)) {
                    $cansee = true;
                }
            }
        }
        if ($cansee === true) {
            $kcourses[$courses[$n]->id] = $courses[$n];
            if (is_array($cacheids)) {
                $cacheids[] = $courses[$n]->id;
            }
        }
    }
    if (is_array($cacheids)) {
        // Only happens
        // - for the logged in user
        // - below the threshold (500)
        // empty string is _valid_
        $USER->mycourses = join(',',$cacheids);
    } elseif ($userid === $USER->id && isset($USER->mycourses)) {
        // cheap sanity check
        unset($USER->mycourses);
    }

    return $kcourses;
}
}

?>
