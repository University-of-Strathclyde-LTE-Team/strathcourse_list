<?PHP //$Id: block_course_list.php,v 1.46.2.6 2008/08/29 04:23:38 peterbulmer Exp $

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/auth/pegasus/auth.php');
require_once($CFG->libdir . '/filelib.php');
define('BLOCK_STRATHCOURSE_LIST_PROG_SITE','/^[\d]{4}-[\d]{1}-[\d]{1}/');

class block_strathcourse_list extends block_list {
    var $degree_course_instance =false;
    var $userIsLta= false;
    function init() {
        global $USER;
        $this->title = get_string('courses').'(Strathclyde)';
        $this->version = 2007101509;
        
	    if ($r = get_record('role','shortname','lta') ) {
			$this->userIsLta = user_has_role_assignment($USER->id, $r->id); 
        }
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

        if ($this->userIsLta) {
            $this->content = new stdClass;
            $searchbox = "<form action='{$CFG->wwwroot}/course/search.php'><input type='text' name='search' /><input type='submit' value='Search'/><input type='reset' value='Reset'/></form>";
            $this->content->items = array(
                //"<a href=\"$CFG->wwwroot/course/search.php\">".get_string("search")."</a>",
                $searchbox,
                "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a>"
            );
            $this->content->icons = array('','');
            $this->content->footer = '';
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
        $this->degree_course_instance = false;
        if (empty($CFG->disablemycourses) and 
            !empty($USER->id) and 
            !(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
            !isguest()) {    // Just print My Courses
            if ($courses = get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
                $counter = 0;
                foreach ($courses as $course) {
                    //echo $course->idnumber;
                    if ($course->id == SITEID) {
                        continue;
                    }
                    if (preg_match(BLOCK_STRATHCOURSE_LIST_PROG_SITE,$course->idnumber)) {
                        //found the user's course class
                        $this->degree_course_instance = $course;
                        continue;
                    }
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a>";
                    //$this->content->icons[]=$icon;
                    $counter++;
                }
                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
            }
            $this->get_remote_courses();
            $this->get_archive_courses();
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
                        echo $course->idnumber;
                        if ($course->id == SITEID) {
                            continue;
                        }
                        if (preg_match(BLOCK_STRATHCOURSE_LIST_PROG_SITE,$course->idnumber)) {
                            //found the user's course class
                            $this->degree_course_instance = $course;
                            
                            continue;
                        }
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->items[]="<a $linkcss title=\""
                                   . format_string($course->shortname)."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" 
                                   .  format_string($course->fullname) . "</a>";
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
        $this->get_archive_courses();
        /*
        if ($this->degree_course_instance) {
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
                    $url = 'http://'.$server.'/auth/pegasus/classes.php';
                    $mac_params['username'] = $USER->username;
                    $mac_params['timestamp'] = time();
                    $mac = $auth->getMAC($mac_params);
                    //$mac_params['mac'] = $mac;
                    
                   
                    $req= $url.'?username='.$mac_params['username'].'&timestamp='.$mac_params['timestamp'].'&mac='.$mac;
                    $result = download_file_content($req);
                    $lines = split("\n",$result);
                    //first one is always the result line
                    $lines = array_slice($lines,1);
                    $course[$servername] = array();
                    foreach($lines as $line) {
                        if ($line != '') {
                            $csv = str_getcsv($line);
                            //p($line);
                            $courses[$servername][]="<a title=\"".format_string($csv[3])."\" ".
                               "href=\"http://{$server}/course/view.php?id={$csv[2]}$\">" 
                               .  format_string($csv[3]) . "</a>";
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
                $this->content->items[] = $c;
                $this->content->icons[] = '';
            }
		}
	}
    }

    function display_degree_course() {
        global $CFG;
        $strDegProg = get_string('degreeprogrammesite','block_strathcourse_list');
        $strClasses = get_string('courses');
        $linkcss ='';
        if (!empty($this->degree_course_instance)) {
        	$linkcss = $this->degree_course_instance->visible ? "" : " class=\"dimmed\" ";
        }
        if ($this->degree_course_instance) {
            array_unshift(
                $this->content->items, 
                //"<div class='block_strathcourse_list_degreeprogramme'>{$strDegProg}</div>",
                "<a $linkcss title=\"" . format_string($this->degree_course_instance->shortname) . "\" ".
                           "href=\"$CFG->wwwroot/course/view.php?id={$this->degree_course_instance->id}\">" . format_string($this->degree_course_instance->fullname) . "</a>",
                "<div class='block_strathcourse_list_classes'>{$strClasses}</div>"
            );
            //array_unshift($this->content->icons[],$icon);
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

}

?>
