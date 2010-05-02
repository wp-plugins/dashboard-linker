<?php
/**
 * Plugin Name: Dashboard linker
 * Plugin URI: http://spais.co.jp/wordpress/plugins/dashboard-linker
 * Description: Create a link to the dashboard
 * Version: 0.1.1
 * Author: SPaiS Inc.
 * Author URI: http://spais.co.jp
 */
class Dashboard_l inker {
    var $_errors = array();
    var $_notices = array();
    var $_defaults = array();
    var $_uriReference = '/^(aaa|aaas|acap|cap|cid|crid|data|dav|dict|dns|fax|file|ftp|go|gopher|h323|http|https|iax|icap|im|imap|info|ipp|iris|iris\.beep|iris\.xpc|iris\.xpcs|iris\.lwz|ldap|mailto|mid|modem|msrp|msrps|mtqp|mupdate|news|nfs|nntp|opaquelocktoken|pop|pres|rtsp|service|shttp|sieve|sip|sips|sms|snmp|soap.beep|soap\.beeps|tag|tel|telnet|tftp|thismessage|tip|tv|urn|vemmi|xmlrpc\.beep|xmlrpc\.beeps|xmpp|z39\.50r|z39\.50s|afs|dtn|geo|mailserver|oid|pack|rsync|tn3270|ws|wss|prospero|snews|videotex|wais)\:\/\/(?:(?:[-_.!~*\'()a-zA-Z0-9;:&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*@)?(?:(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][-a-zA-Z0-9]*[a-zA-Z0-9])\.)*(?:[a-zA-Z]|[a-zA-Z][-a-zA-Z0-9]*[a-zA-Z0-9])\.?|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)(?::[0-9]*)?(?:\/(?:[-_.!~*\'()a-zA-Z0-9:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*(?:;(?:[-_.!~*\'()a-zA-Z0-9:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*)*(?:\/(?:[-_.!~*\'()a-zA-Z0-9:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*(?:;(?:[-_.!~*\'()a-zA-Z0-9:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*)*)*)?(?:\?(?:[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*)?(?:#(?:[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,]|%[0-9A-Fa-f][0-9A-Fa-f])*)?/i';
    var $_redirector = 'http://www.google.com/url?sa=D&q=';

    function display_box()
    {
        $_this = &Dashboard_linker :: i();
        $this -> display_list();
        $this -> display_link_editor();
    }

    function display_list()
    {
        $links = get_option('dashboard_link_list', array());
        if (empty($links)) return null;
        $args = wp_parse_args(getenv('QUERY_STRING'));
        $editBtn = current_user_can('manage_links')? sprintf('<a class="editBtn" href="%s?%%s">e</a>', getenv('SCRIPT_NAME')): null;
        $tab = isset($_GET['tab'])? urldecode($_GET['tab']): (isset($_COOKIE['dashboard_linker_current_tab'])? $_COOKIE['dashboard_linker_current_tab']: null);
        $currentTab = null;
        $lists = $tags = array();
        foreach($links as $id => $link) {
            $_args = wp_parse_args(array('dashboard_linker_link_id' => $id, 'action' => $this -> get_action('edit')), $args);
            $uri = parse_url($link['uri']);
            $redirector = ($uri['scheme'] === 'http' || $uri['scheme'] === 'https' || $uri['scheme'] === 'shttp') && isset($link['redirect']) && $link['redirect'] === '1'? $this -> _redirector: null;
            $li = sprintf('<li>%s<a href="%s%s">%s</a></li>', sprintf($editBtn, $this -> merge_args($_args)), $redirector, $link['uri'], $link['title']);
            foreach($link['tag'] as $tag) {
                if (!isset($lists[$tag])) $lists[$tag] = array();
                $lists[$tag][] = $li;
                if ((empty($tags) && empty($tab)) || $tag === $tab) {
                    $current = 'class="current"';
                    $currentTab = $tag;
                } else {
                    $current = null;
                }
                $__args = wp_parse_args(array('tab' => urlencode($tag)), $args);
                if (!array_key_exists($tag, $tags)) $tags[$tag] = sprintf('<li><a href="%s?%s"%s>%s</li>', getenv('SCRIPT_NAME'), $this -> merge_args($__args), $current, $tag);
            }
        }
        printf('<ul id="dashboard_linker_tags">%s</ul>', implode("\n", $tags));
        foreach($lists as $tag => $list) {
            $current = $currentTab === $tag? ' current': null;
            printf('<ul class="dashboard_linker_list%s">%s</ul>', $current, implode("\n", $list));
        }
    }

    function display_link_editor()
    {
        if (!current_user_can('manage_links')) return null;
        $stat = null;
        if (!isset($_GET['action']) || strpos($_GET['action'], __CLASS__) === false) {
            $action = $this -> get_action('add');
            $submit = sprintf('<input type="submit" name="_submit" class="button-primary" value="%s" />', __('Add'));
            $stat = 'closable';
        } else {
            $action = $_GET['action'];
            $submit = sprintf('<input type="submit" name="_submit_edit" class="button-primary" value="%s" />', __('Edit'));
            $submit .= sprintf('<input type="submit" name="_submit_delete" value="%s" />', __('Delete'));
            $submit .= sprintf('<a class="button" href="%s">%s</a>', getenv('SCRIPT_NAME'), __('Cancel'));
        }
        $this -> notices();

        ?><div id="dashboard_linker_add_form"><form method="post" action="<?php echo getenv('REQUEST_URI')?>"><fieldset>
            <legend><?php _e('Edit Link Form', __CLASS__)?></legend>
            <?php wp_nonce_field($action)?>
            <input type="hidden" name="action" value="<?php echo $action?>" />
            <h4><label for="dashboard_linker_title"><?php _e('Title')?></label></h4>
            <div class="input-text-wrap"><input type="text" id="dashboard_linker_title" name="dashboard_linker_title" value="<?php $this -> get_default('title')?>" /></div>
            <h4><label for="dashboard_linker_uri"><?php _e('URI')?></label></h4>
            <div class="input-text-wrap"><input type="text" id="dashboard_linker_uri" name="dashboard_linker_uri" value="<?php $this -> get_default('uri')?>" /></div>
            <h4><label for="dashboard_linker_tag"><?php _e('Tags')?></label></h4>
            <div class="input-text-wrap"><input type="text" id="dashboard_linker_tag" name="dashboard_linker_tag" value="<?php echo is_null($t = $this -> get_default('tag', false))? null: (is_array($t)? implode(',', $t): $t)?>" /></div>
            <p class="notice"><?php _e('Separate tags with commas.')?></p>
            <h4><label for="dashboard_linker_redirect"><?php _e('Redirect')?></label></h4>
            <div class="input-radio-wrap">
                <label><input type="radio" name="dashboard_linker_redirect" value="0"<?php if ($this -> get_default('redirect') === '0') echo ' checked="checked"'?> /><?php _e('Not do it', __CLASS__)?></label>
                <label><input type="radio" name="dashboard_linker_redirect" value="1"<?php if ($this -> get_default('redirect') === '1') echo ' checked="checked"'?> /><?php _e('Do it', __CLASS__)?></label>
            </div>
            <p class="submit"><?php echo $submit?></p>
        </fieldset></form><script type="text/javascript">
            var dashboard_linker_strings = <?php echo json_encode(array('close' => __('Close'), 'open' => __('Open Edit Link Form', __CLASS__)))?>;
            var dashboard_linker_form_stat = '<?php echo $stat?>'
        </script></div><?php
    }

    function add_link()
    {
        if (($link = $this -> verify()) === false) return null;
        $links = get_option('dashboard_link_list', array());
        $links[] = $link;
        $newLinks = array();
        foreach($links as $link) $newLinks[] = $link;
        update_option('dashboard_link_list', $newLinks);
        $this -> _notices[] = __('Added link.', __CLASS__);
    }

    function del_link()
    {
        $links = get_option('dashboard_link_list', array());
        $deleted = false;
        $id = -1;
        while (isset($links[++$id])) {
            if ((int) $_GET['dashboard_linker_link_id'] === $id && $_POST['dashboard_linker_title'] === $links[$id]['title']) {
                unset($links[$id]);
                $deleted = true;
                break;
            }
        }
        $newLinks = array();
        foreach($links as $link) $newLinks[] = $link;
        update_option('dashboard_link_list', $newLinks);
        if ($deleted === true)
            $this -> _notices[] = __('Deleted link.', __CLASS__);
        else
            $this -> _errors[] = __('There is no such link ID', __CLASS__);
    }

    function edit_link()
    {
        if (isset($_POST['_submit_delete'])) return $this -> del_link();
        if (($link = $this -> verify()) === false) return null;
        $links = get_option('dashboard_link_list', array());
        $edited = false;
        $id = -1;
        while (isset($links[++$id])) {
            if ((int) $_GET['dashboard_linker_link_id'] === $id) {
                $links[$id] = $link;
                $edited = true;
                break;
            }
        }
        $newLinks = array();
        foreach($links as $link) $newLinks[] = $link;
        update_option('dashboard_link_list', $newLinks);
        $this -> set_default($_GET['dashboard_linker_link_id']);
        if ($edited === true)
            $this -> _notices[] = __('Edited link.', __CLASS__);
        else
            $this -> _errors[] = __('There is no such link ID', __CLASS__);
    }

    function verify()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], $_POST['action'])) {
            $this -> _errors[] = __('Invalid nonce.', __CLASS__);
            return false;
        }
        $links = get_option('dashboard_link_list', array());
        $titles = array();
        foreach($links as $link)
        $titles[] = $link['title'];
        $link = array();
        if (empty($_POST['dashboard_linker_uri']))
            $this -> _errors[] = __('URI is required.', __CLASS__);
        elseif (!preg_match($this -> _uriReference, $_POST['dashboard_linker_uri']))
            $this -> _errors[] = __('Invalid URI.', __CLASS__);
        else
            $link['uri'] = $_POST['dashboard_linker_uri'];

        if (empty($_POST['dashboard_linker_title']))
            $this -> _errors[] = __('Title is required.', __CLASS__);
        elseif ($_POST['action'] === $this -> get_action('add') && in_array($_POST['dashboard_linker_title'], $titles))
            $this -> _errors[] = __('Title is duplicated.', __CLASS__);
        elseif ($_POST['action'] === $this -> get_action('edit') && $_POST['dashboard_linker_title'] !== $this -> get_default('title', false) && in_array($_POST['dashboard_linker_title'], $titles))
            $this -> _errors[] = __('Title is duplicated.', __CLASS__);
        else
            $link['title'] = $_POST['dashboard_linker_title'];

        if (empty($_POST['dashboard_linker_tag']))
            $this -> _errors[] = __('Tag is required.', __CLASS__);
        else
            $link['tag'] = explode(',', $_POST['dashboard_linker_tag']);

        if (empty($_POST['dashboard_linker_redirect']))
            $link['redirect'] = '0';
        else
            $link['redirect'] = '1';

        return empty($this -> _errors)? $link: false;
    }

    function set_default($currentId)
    {
        $links = get_option('dashboard_link_list', array());
        foreach($links as $id => $link) {
            if ((int) $currentId === $id) {
                $this -> _defaults = $link;
                $this -> _defaults['id'] = $id;
                return;
            }
        }
    }

    function get_default($name, $echo = true)
    {
        $d = isset($this -> _defaults[$name])? $this -> _defaults[$name]: (isset($_POST["dashboard_linker_{$name}"])? $this -> _h($_POST["dashboard_linker_{$name}"], false): null);
        if ($echo === true) echo $d;
        return $d;
    }

    function notices()
    {
        if (!empty($this -> _notices))
            printf('<div id="message" class="updated fade"><ul><li>%s</li></ul></div>', implode('</li><li>', $this -> _notices));
        elseif (!empty($this -> _errors))
            printf('<div id="message" class="error fade"><ul><li>%s</li></ul></div>', implode('</li><li>', $this -> _errors));
    }

    function merge_args($args)
    {
        if (empty($args) || !is_array($args)) return null;
        $d = array();
        foreach($args as $name => $value)
        $d[] = sprintf('%s=%s', $name, urlencode($value));
        return implode('&amp;', $d);
    }

    function get_action($action)
    {
        return esc_attr(sprintf('%s_%s', __CLASS__, $action));
    }

    function admin_init()
    {
        load_textdomain(__CLASS__, dirname(__FILE__) . '/languages/' . get_locale() . '.mo');
        add_meta_box(__CLASS__, __('Dashboard linker', __CLASS__), array(&$this, 'display_box'), 'dashboard', 'side', 'core');
        wp_enqueue_style(__CLASS__, plugin_dir_url(__FILE__) . 'dashboard-linker.css');
        wp_enqueue_script(__CLASS__, plugin_dir_url(__FILE__) . 'dashboard-linker.js', array('jquery'), null, true);
        if (!current_user_can('manage_links')) return null;
        if (!empty($_GET) && isset($_GET['dashboard_linker_link_id']) && $_GET['action'] === $this -> get_action('edit')) {
            $this -> set_default($_GET['dashboard_linker_link_id']);
        }
        if (!empty($_POST)) {
            if ($_POST['action'] === $this -> get_action('add')) $this -> add_link();
            elseif ($_POST['action'] === $this -> get_action('edit')) $this -> edit_link();
        }
        if (isset($_GET['tab'])) {
            setcookie('dashboard_linker_current_tab', urldecode($_GET['tab']), time() + 31536000);
        }
    }

    function init()
    {
        $_this = &Dashboard_linker :: i();
        register_activation_hook(__FILE__, array(&$_this, 'install'));
        add_action('admin_init', array(&$_this, 'admin_init'));
    }

    function &i()
    {
        static $i;
        if (empty($i)) $i = new Dashboard_linker;
        return $i;
    }

    function _h($str, $echo = true)
    {
        static $e;
        if (empty($e)) $e = get_bloginfo('charset');
        $d = htmlspecialchars($str, ENT_QUOTES, $e);
        if ($echo === true) echo $d;
        return $d;
    }

    function install()
    {
        load_textdomain(__CLASS__, dirname(__FILE__) . '/languages/' . get_locale() . '.mo');
        $links = get_option('dashboard_link_list', array());
        if (empty($links)) $links = array(array('title' => __('Google Analytics', __CLASS__), 'uri' => 'http://www.google.com/analytics/', 'tag' => array(__('SEO', __CLASS__), __('Analysis', __CLASS__)), 'redirect' => '0'),
                array('title' => __('Google Website Optimizer', __CLASS__), 'uri' => 'http://www.google.com/websiteoptimizer/', 'tag' => array(__('SEO', __CLASS__), __('Analysis', __CLASS__)), 'redirect' => '0'),
                array('title' => __('Google Webmaster Central', __CLASS__), 'uri' => 'http://www.google.com/webmasters/', 'tag' => array(__('SEO', __CLASS__)), 'redirect' => '0'),
                array('title' => __('Yahoo! Site Explorer', __CLASS__), 'uri' => 'http://siteexplorer.search.yahoo.co.jp/', 'tag' => array(__('SEO', __CLASS__)), 'redirect' => '0'),
                array('title' => __('Bing Webmaster Center', __CLASS__), 'uri' => 'http://www.bing.com/webmaster', 'tag' => array(__('SEO', __CLASS__)), 'redirect' => '0'),
                array('title' => __('SPaiS Inc.', __CLASS__), 'uri' => 'http://spais.co.jp/', 'tag' => array(__('Producer', __CLASS__)), 'redirect' => '0'));
        update_option('dashboard_link_list', $links);
    }
}
Dashboard_linker :: init();

?>
