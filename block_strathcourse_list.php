<?PHP //$Id: block_course_list.php,v 1.46.2.6 2008/08/29 04:23:38 peterbulmer Exp $

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/auth/pegasus/auth.php');
require_once($CFG->libdir . '/filelib.php');
class block_strathcourse_list extends block_list {
    function init() {
        $this->title = get_string('courses').'(Strathclyde)';
        $this->version = 2007101509;
    }
    
    function has_config() {
        return true;
    }
    function specialization() {
        global $CFG;
        $this->title = "<img src=\"$CFG->wwwroot/blocks/strathcourse_list/course.gif\" class=\"strathcourse\" alt=\"".get_string("coursecategory")."\" />Courses";            
    }
    
     
    function get_content() {
        global $THEME, $CFG, $USER;

        if($this->content !== NULL) {
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

        if (empty($CFG->disablemycourses) and 
            !empty($USER->id) and 
            !(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
            !isguest()) {    // Just print My Courses
            if ($courses = get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
                $counter = 0;
                foreach ($courses as $course) {
                    if ($course->id == SITEID) {
                        continue;
                    }
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a>";
                    $this->content->icons[]=$icon;
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
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }

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

        return $this->content;
    }
    
    function get_archive_courses() {
        global $CFG, $USER;
        $archives = split(',',$CFG->block_course_list_archiveservers);
        $auth = new auth_plugin_pegasus();
        print_object($archives);
        if ($archives) {
            foreach($archives as $server) {
                $url = 'http://'.$server.'/auth/pegasus/classes.php';
                $mac_params['username'] = $USER->username;
                $mac_params['timestamp'] = time();
                $mac = $auth->getMAC($mac_params);
                //$mac_params['mac'] = $mac;
                
               
                $req= $url.'?username='.$mac_params['username'].'&timestamp='.$mac_params['timestamp'].'&mac='.$mac;
                $result = download_file_content($req);
                p($result);
            }
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
