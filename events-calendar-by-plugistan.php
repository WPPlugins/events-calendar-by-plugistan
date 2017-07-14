<?php

/*
  Plugin Name: Wordpress Events Calendar and Scheduler
  Plugin URI: http://plugistan.com/wordpress-events-calendar/
  Plugin URI: http://www.e-abi.ee/wordpress-calendar-and-scheduler-plugin.html/
  Description: This plugin displays Calendar with your events on the site and customers can sign up for your events. You can even limit the amount of customers who can sign up for the event. Useful for doctors, theatre owners, training schedule makers.
  Author: Aktsiamaailm LLC, iPenelo LLC
  Author: iPenelo LLC, Mr. Kaarel Veike
  Author URI: http://www.e-abi.ee
  Author URI: http://www.plugistan.com
  Version: 0.1.3
 */
// Create a master category for Calendar and its sub-pages
/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.3

 */
//we need sessions for the internal messaging system to work
if (!session_id())
    session_start();

add_filter('widget_text', 'do_shortcode');
$eabi_ipenelo_calendar = new Eabi_Ipenelo_Calendar();

/*
  Eabi_Ipenelo_Calendar::service()->import('helpers/Translator');

  Eabi_Ipenelo_Calendar_Helper_Translator::load();


  register_shutdown_function(array('Eabi_Ipenelo_Calendar_Helper_Translator', 'save'));
 */

/**
  Base class for the module.
  All the wordpress filters and actions are performed only in this file.

 */
class Eabi_Ipenelo_Calendar {

    const LOCATION = '/events-calendar-by-plugistan';

    private $_pluginUrl;
    private static $_configurationInstance;
    private static $_translatorLoaded;
    private static $_useNativeDateParser;

    public function __construct() {
        $this->_pluginUrl = plugins_url() . self::LOCATION;
        $this->__ = Eabi_Ipenelo_Calendar::service()->get('translator');

        //load translations
        load_plugin_textdomain('ipenelo_calendar', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $params = array();
        

        //set up email filter
        Eabi_Ipenelo_Calendar::service()->import('events/Email');
        add_filter('wp_mail_from', array('Eabi_Ipenelo_Calendar_Event_Email', 'getFromEmail'));
        add_filter('wp_mail_from_name', array('Eabi_Ipenelo_Calendar_Event_Email', 'getFromName'));

        //set up show admin menu
        if ((bool) self::get('show_in_admin_bar')) {
            add_action('admin_bar_menu', array(&$this, 'admin_bar'), 1000);
        }

        //add new image sizes
        add_image_size('ipenelo_calendar_thumb', (int) Eabi_Ipenelo_Calendar::get('image_thumb_height', 32), (int) Eabi_Ipenelo_Calendar::get('image_thumb_width', 32), (bool) Eabi_Ipenelo_Calendar::get('image_thumb_crop', false));
        add_image_size('ipenelo_calendar_normal', (int) Eabi_Ipenelo_Calendar::get('image_normal_height', 150), (int) Eabi_Ipenelo_Calendar::get('image_normal_width', 150), (bool) Eabi_Ipenelo_Calendar::get('image_normal_crop', false));
        //process shortcodes

        /* @var $shortCodeHandler Eabi_Ipenelo_Calendar_Shortcode_Calendar */
        $shortCodeHandler = Eabi_Ipenelo_Calendar::service()->get('shortcodes/Calendar');
        
        
        if (is_admin()) {
            //new version install only in admin and only when neccessary
            $install = Eabi_Ipenelo_Calendar::service()->get('installers/Main');
            if (version_compare(Eabi_Ipenelo_Calendar_Installer_Main::VERSION, self::get('version_number')) > 0) {
                $install->install();
            }

            add_filter('admin_print_scripts', array(&$this, 'insertAdminPrintJs'));

            //admin_enqueue_scripts
            //admin_head
            add_filter('attachment_fields_to_edit', array(&$this, 'image_sizes_attachment_fields_to_edit'), 100, 2);
            add_action('admin_menu', array(&$this, 'calendar_menu'));
            add_action('admin_menu', array(&$this, 'handleMetabox'));
            add_action("admin_enqueue_scripts", array(&$this, 'insertAdminJs'));
            add_action("admin_enqueue_scripts", array(&$this, 'insertAdminCss'));
            add_action('wp_ajax_ipenelo_calendar_get_events', array(&$this, 'get_ajax_events'));
            add_action('wp_ajax_nopriv_ipenelo_calendar_get_events', array(&$this, 'get_ajax_events'));

            add_action('wp_ajax_ipenelo_calendar_get_registrants', array(&$this, 'get_ajax_registrants'));
            add_action('wp_ajax_nopriv_ipenelo_calendar_get_registrants', array(&$this, 'get_ajax_registrants'));

            add_action('wp_ajax_ipenelo_calendar_start_payment', array(&$this, 'start_payment'));
            add_action('wp_ajax_nopriv_ipenelo_calendar_start_payment', array(&$this, 'start_payment'));
        } else {
            if (isset($_GET['ipenelo_calendar_payment_confirm'])) {
                add_action('init', array(&$this, 'confirmPayment'));
            }

            add_action("wp_enqueue_scripts", array(&$this, 'insertJs'));
            add_action("wp_enqueue_scripts", array(&$this, 'insertCss'));


            add_shortcode(Eabi_Ipenelo_Calendar_Shortcode_Calendar::SHORTCODE, array(&$shortCodeHandler, 'handleShortCode'));
            add_shortcode(Eabi_Ipenelo_Calendar_Shortcode_Calendar::SHORTCODE . '_message', array(&$shortCodeHandler, 'handleMessages'));
        }
    }

    /**
      Creates the Main calendar menu for the administration.


     */
    public function calendar_menu() {
        global $_registered_pages;
        // Set admin as the only one who can use Calendar for security
        $allowed_group = 'manage_options';

        $hookname = get_plugin_page_hookname('ipenelo_calendar_view_registrants', 'admin.php');

        if (!empty($hookname)) {
            add_action($hookname, array(&$this, 'view_registrants'));
        }
        $_registered_pages[$hookname] = true;

        $hookname = get_plugin_page_hookname('ipenelo_calendar_edit_registrant', 'admin.php');

        if (!empty($hookname)) {
            add_action($hookname, array(&$this, 'edit_registrant'));
        }
        $_registered_pages[$hookname] = true;


        $hookname = get_plugin_page_hookname('ipenelo_calendar_edit_category', 'admin.php');

        if (!empty($hookname)) {
            add_action($hookname, array(&$this, 'edit_category'));
        }
        $_registered_pages[$hookname] = true;


        // Add the admin panel pages for Calendar. Use permissions pulled from above
        if (function_exists('add_menu_page')) {
            $theme = Eabi_Ipenelo_Calendar::get('theme', 'simple');
            add_menu_page($this->__->l('Event Scheduler'), $this->__->l('Event Scheduler'), $allowed_group, 'ipenelo_calendar', array(&$this, 'manage_events'), $this->_pluginUrl . '/themes/'.$theme.'/img/eabi-ipenelo-calendar-icon.png');
        }
        if (function_exists('add_submenu_page')) {
            add_submenu_page('ipenelo_calendar', $this->__->l('Manage events'), $this->__->l('Manage events'), $allowed_group, 'ipenelo_calendar', array(&$this, 'manage_events'));
            add_submenu_page('ipenelo_calendar', $this->__->l('Add new event'), $this->__->l('Add event'), $allowed_group, 'ipenelo_calendar_new', array(&$this, 'new_event'));
            // Note only admin can change calendar options
            add_submenu_page('ipenelo_calendar', $this->__->l('Manage Categories'), $this->__->l('Manage Categories'), $allowed_group, 'ipenelo_calendar_categories', array(&$this, 'manage_categories'));
            add_submenu_page('ipenelo_calendar', $this->__->l('Settings'), $this->__->l('Settings'), $allowed_group, 'ipenelo_calendar_config', array(&$this, 'edit_calendar_config'));
            add_submenu_page('ipenelo_calendar', $this->__->l('Payment settings'), $this->__->l('Payment Settings'), $allowed_group, 'ipenelo_calendar_payment_settings', array(&$this, 'manage_payment_methods'));
        }
    }

    /**
      Inserts Javascripts for the public side.

     */
    public function insertJs() {
        wp_register_script('jquery-tools-min', $this->_pluginUrl . '/js/jquery.tools.min.js', array('jquery'));
        wp_register_script('json-ie7', $this->_pluginUrl . '/js/json2.js');
        wp_enqueue_script('jquery-tools-min');
        wp_enqueue_script('json-ie7');
        wp_enqueue_script('thickbox');
    }

    /**
      Loads the css files for the public side


     */
    public function insertCss() {
        wp_enqueue_style('thickbox');
        $theme = self::get('theme', 'simple');
        wp_register_style('ipenelo-calendar-public', $this->_pluginUrl . '/themes/' . $theme . '/css/styles-public.css', array());
        wp_enqueue_style('ipenelo-calendar-public');
    }

    public function insertAdminPrintJs() {
        if ($GLOBALS['editing']) {
            wp_register_script('ipenelo-calendar-shortcode', $this->_pluginUrl . '/js/shortcodehelper.js', array('jquery'));
            wp_enqueue_script('ipenelo-calendar-shortcode');
        }
    }

    /**

      Loads the JS files for the Administration side

     */
    public function insertAdminJs() {

        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('jquery-tools-min', $this->_pluginUrl . '/js/jquery.tools.min.js', array('jquery'));
        wp_register_script('jquery-color-picker', $this->_pluginUrl . '/js/colorpicker.js', array('jquery'));
        wp_enqueue_script('jquery-tools-min');
        wp_enqueue_script('jquery-color-picker');

        return;
    }

    /**
      Loads the CSS files for the Administration side

     */
    public function insertAdminCss() {
        wp_enqueue_style('thickbox');
        wp_register_style('ipenelo-calendar', $this->_pluginUrl . '/css/styles.css', array());
        wp_enqueue_style('ipenelo-calendar');
        return;
    }

    /**
      Adds Calendar menu element to the admin bar


     */
    public function admin_bar() {
        global $wp_admin_bar;
        if (!is_super_admin() || !is_admin_bar_showing()) {
            return;
        }
        /* Add the main siteadmin menu item */
        $wp_admin_bar->add_menu(array('id' => 'ipenelo_calendar', 'title' => $this->__->l('Ipenelo Calendar'), 'href' => false));

        $wp_admin_bar->add_menu(array('parent' => 'ipenelo_calendar', 'title' => $this->__->l('Add new event'), 'href' => admin_url('admin.php?page=ipenelo_calendar_new')));
        $wp_admin_bar->add_menu(array('parent' => 'ipenelo_calendar', 'title' => $this->__->l('Manage events'), 'href' => admin_url('admin.php?page=ipenelo_calendar')));
    }

    /**
      Adds shortcode generator to the POST and PAGE edits.

     */
    public function handleMetabox() {
        $shortCodeHandler = Eabi_Ipenelo_Calendar::service()->get('shortcodes/Calendar');
        add_meta_box('ipenelo_calendar', $this->__->l('Insert Ipenelo Calendar'), array(&$shortCodeHandler, 'insertAdminForm'), 'post', 'normal');
        add_meta_box('ipenelo_calendar', $this->__->l('Insert Ipenelo Calendar'), array(&$shortCodeHandler, 'insertAdminForm'), 'page', 'normal');
    }

    /**
      This function is invoked when ?ipenelo_calendar_payment_confirm  parameter is supplied.
      Intention for the function is to mark the Registrant as paid and redirect to the payment success page.
      Whenever possible, this function attempts to supply the event_id parameter on redirection.

     */
    public function confirmPayment() {
        $code = $_GET['ipenelo_calendar_payment_confirm'];
        $payment = Eabi_Ipenelo_Calendar::service()->get('payment')->getByCode($code, Eabi_Ipenelo_Calendar::service()->get('models/Registrant'));

        if ($payment === false) {
            wp_redirect(get_option('siteurl'));
            exit;
        }
        $result = $payment->validatePayment();
        $event = $payment->getEvent();
        if ($result == true) {
            $successPage = Eabi_Ipenelo_Calendar::get('payment_success');
            $url = '';
            if (!$successPage) {
                $url = get_option('home');
            } else {
                $url = get_permalink($successPage);
            }
            if ($event !== null) {
                if (strpos($url, '?') > 0) {
                    $url .= '&event_id=' . $event->id;
                } else {
                    $url .= '?event_id=' . $event->id;
                }
            }
            wp_redirect($url);
        } else {
            $successPage = Eabi_Ipenelo_Calendar::get('payment_cancel');
            $url = '';
            if (!$successPage) {
                $url = get_option('home');
            } else {
                $url = get_permalink($successPage);
            }
            if ($event !== null) {
                if (strpos($url, '?') > 0) {
                    $url .= '&event_id=' . $event->id;
                } else {
                    $url .= '?event_id=' . $event->id;
                }
            }
            wp_redirect($url);
        }
        exit;
    }

    /**
      Manage payment methods in administration

     */
    public function manage_payment_methods() {
        global $plugin_page;
        Eabi_Ipenelo_Calendar::service()->import('models/Registrant');
        Eabi_Ipenelo_Calendar::service()->get('payment')->detectAvailablePaymentMethods();
        $paymentMethods = array(
        );
        $availablePayments = @unserialize(Eabi_Ipenelo_Calendar::get('available_payments'));
        if (!is_array($availablePayments)) {
            wp_redirect(admin_url('admin.php?page=' . $plugin_page));
            exit;
            $availablePayments = array();
        }
        foreach ($availablePayments as $code => $availablePayment) {
            $paymentMethods[$code] = $availablePayment['title'];
        }
        if (isset($_GET['code']) && isset($paymentMethods[$_GET['code']])) {
            //echo the form
            $form = Eabi_Ipenelo_Calendar::service()->get('payment')->getByCode($_GET['code'], Eabi_Ipenelo_Calendar::service()->get('models/Registrant'));
        } else {
            //echo the select field.
            if (isset($_GET['noheader'])) {
                require_once(ABSPATH . 'wp-admin/admin-header.php');
            }
            echo Eabi_Ipenelo_Calendar::service()->get('payment')->renderSelect($paymentMethods);
            return;
        }

//		$form = new Eabi_Ipenelo_Calendar_Payment_Checkmo(new Eabi_Ipenelo_Calendar_Model_Registrant());

        $needsRedirect = $form->save();
        if ($needsRedirect) {
            wp_redirect(admin_url('admin.php?noheader=true&page=' . $plugin_page));

            exit;
        }

        if (isset($_GET['noheader'])) {
            require_once(ABSPATH . 'wp-admin/admin-header.php');
        }
        if (is_object($form)) {
            echo $form->renderConfigurationForm();
        }
    }

    /**
      Get list of events for the public side as JsonGrid

     */
    public function get_ajax_events() {
        Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'applyStatuses');
        $grid = Eabi_Ipenelo_Calendar::service()->get('grids/EventJson');
        $grid->setDateFilter($_POST);
        header('Content-type: application/json');
        echo json_encode($grid->indexByDate());
        die();
    }

    /**
      When the registration was successful and we are dealing with paid event.
      User is redirected thru this function.
      This function attempts to load the payment process, which in turn marks the order paid right away or redirects the user
      to the third party page or to the success page.


     */
    public function start_payment() {
        $passed = false;
        $payment = null;
        if (isset($_GET['registrant_id'])) {

            $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
            $registrant->load($_GET['registrant_id']);
            if (isset($registrant->id) && $registrant->status == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PENDING) {
                $event = Eabi_Ipenelo_Calendar::service()->get('models/Event')->load($registrant->event_id);
                if (isset($event->id) && $event->is_paid_event == '1') {
                    $payment = Eabi_Ipenelo_Calendar::service()->get('payment')->getByCode($registrant->payment_method, $registrant);
                    if ($payment != '' && $payment->isEnabled()) {
                        $passed = true;
                    }
                }
            }
        }
        if ($passed === true) {
            //echo the payment form
            $form = $payment->getPaymentForm();
            if ($form === false) {
                //no need to save the event, simply redirect user to the front page.
                //add the success message
                Eabi_Ipenelo_Calendar::addMessage($this->__->l('Payment successful'));
                $successPage = Eabi_Ipenelo_Calendar::get('payment_success');
                $url = '';
                if (!$successPage) {
                    $url = get_option('home');
                } else {
                    $url = get_permalink($successPage);
                }
                if ($event !== null) {
                    if (strpos($url, '?') > 0) {
                        $url .= '&event_id=' . $event->id;
                    } else {
                        $url .= '?event_id=' . $event->id;
                    }
                }
                wp_redirect($url);

                exit;
            } else {
                //echo the form
                $action = $payment->getRedirectUrl();
                $title = $payment->getTitle();
                echo <<<EOT
<html>
	<head>
	</head>
	<title>{$title}</title>
	</head>
	<body>
		<form action="{$action}" method="post" id="myform">
			{$form}
		</form>
		<script type="text/javascript">
			document.getElementById('myform').submit();
		</script>
	</body>
</html>
EOT;
            }
        } else {
            //add the error somewhere
            Eabi_Ipenelo_Calendar::addError($this->__->l('Payment operation failed'));
            wp_redirect(get_option('home'));
        }
        die();
    }

    /**
      This function displays Event Detail window for the public side.
      Also this view contains the registration form.
      Displays the contents of the public thickbox.


     */
    public function get_ajax_registrants() {
        Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'applyStatuses');
        $ajaxResult = array(
            'is_error' => false,
            'content' => '',
            'error_messages' => array(),
            'success_messages' => array(),
        );

        //we need event id
        /** @var $model Eabi_Ipenelo_Calendar_Model_Registrant */
        $model = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
        

        //we need to check if the user has been registered previously
        $isError = false;
        if (!is_user_logged_in() && Eabi_Ipenelo_Calendar::get('log_to_view')) {
            $isError = true;
        }


        $eventModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');
        if (isset($_REQUEST['event_id']) && is_numeric($_REQUEST['event_id'])
                && $eventModel->existsPublic($_REQUEST['event_id']) && !$isError) {
            $model->event_id = $_REQUEST['event_id'];
        } else {
            $ajaxResult['error_messages'][] = $this->__->l('Event does not exist');
            $ajaxResult['is_error'] = true;
            $ajaxResult['content'] = $this->__->l('Event does not exist');
        }
        $formId = false;
        if (isset($_REQUEST['form_id']) && preg_match('/^[A-Za-z0-9_\-]+$/', $_REQUEST['form_id'])) {
            $formId = $_REQUEST['form_id'];
        }


        //we need to check if anonymous users are allowed
        //we need to auto-fill the form if the user has been logged in
        //we need to determine if the registrant has to pay or not
        //we need to determine if there are empty spots for the registration
        if (!$ajaxResult['is_error']) {
            Eabi_Ipenelo_Calendar::service()->import('forms/PublicRegistrant');
            Eabi_Ipenelo_Calendar::service()->import('forms/Event');

            $currentUser = wp_get_current_user();
            $registeredModel = null;
            if ($currentUser->ID > 0) {
                unset($_SESSION['ipenelo_calendar_noregister']);
                $registeredModel = Eabi_Ipenelo_Calendar::service()->getStatic('models/Registrant', 'loadByEmailAndEvent', $currentUser->user_email, $_REQUEST['event_id']);
                $model->email = $currentUser->user_email;
                $model->first_name = $currentUser->user_firstname;
                $model->last_name = $currentUser->user_lastname;
            } else {
                if (isset($_SESSION['ipenelo_calendar_noregister'])
                        && is_array($_SESSION['ipenelo_calendar_noregister'])
                        && isset($_SESSION['ipenelo_calendar_noregister'][$_REQUEST['event_id']])) {
                    $registrantEmail = $_SESSION['ipenelo_calendar_noregister'][$_REQUEST['event_id']];
                    $registeredModel = Eabi_Ipenelo_Calendar::service()->getStatic('models/Registrant', 'loadByEmailAndEvent', $currentUser->user_email, $_REQUEST['event_id']);
                }
            }

            $eventForm = Eabi_Ipenelo_Calendar::service()->get('forms/Event', (array) $eventModel->load($_REQUEST['event_id']));
            if ($registeredModel != null) {
                $model = $registeredModel;
            }


            $form = Eabi_Ipenelo_Calendar::service()->get('forms/PublicRegistrant', (array) $model, $eventForm);
            if ($formId != '') {
                $form->setSubmitFormId($formId);
            }
            if ($registeredModel != null) {
                $form->setReadOnly(true);
            }
            $ajaxResult['content'] = $form->render();

            $res = $form->toDb();

            //validate
            $validationResult = $form->validate($res);

            if ($res !== false && count($validationResult) == 0 && !$form->getReadOnly()) {
                if (!isset($model->id)) {
                    $res['registration_date'] = date('Y-m-d H:i:s', current_time('timestamp'));
                    $res['status'] = Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PENDING;
                    $res['event_id'] = $_REQUEST['event_id'];



                    $currentUser = wp_get_current_user();
                    $res['wp_user_id'] = $currentUser->ID;
                    $res['order_hash'] = $model->getHash();
                    Eabi_Ipenelo_Calendar::service()->get('database')->insert($model->getTableName(), $res);
                    $model->id = Eabi_Ipenelo_Calendar::service()->get('database')->insert_id;

                    if ($model->id <= 0) {
                        self::addError($this->__->l('Registration failed'));
                        if (Eabi_Ipenelo_Calendar::service()->get('database')->last_error != '') {
                            self::addError(htmlspecialchars(Eabi_Ipenelo_Calendar::service()->get('database')->last_error));
                        }
                        $model = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                        $form = Eabi_Ipenelo_Calendar::service()->get('forms/PublicRegistrant', (array) $model, $eventForm);
                        if ($formId != '') {
                            $form->setSubmitFormId($formId);
                        }
                        $form->setReadOnly(true);
                        $ajaxResult['error_messages'][] = $this->__->l('Registration failed');
                    } else {
                        $eventParams = array(
                            'registrant' => (array) $model->load($model->id),
                            'event' => (array) $eventModel->load($model->event_id),
                        );

                        Eabi_Ipenelo_Calendar::service()->get('event')->event('new_registrant', $eventParams);


                        $form->setModel($res);
                        $form->setReadOnly(true);
                        self::addMessage($this->__->l('Registration successful'));

                        if ($currentUser->ID == 0) {
                            if (!isset($_SESSION['ipenelo_calendar_noregister']) || !is_array($_SESSION['ipenelo_calendar_noregister'])) {
                                $_SESSION['ipenelo_calendar_noregister'] = array();
                            }
                            $_SESSION['ipenelo_calendar_noregister'][$model->event_id] = array('id' => $model->id, 'email' => $model->email);
                        }

                        //reload the form and send the user to pay
                        $model->load(Eabi_Ipenelo_Calendar::service()->get('database')->insert_id);
                        $form->setModel((array) $model);
                        $form->setDisplayPayment(true);
                        $ajaxResult['success_messages'][] = $this->__->l('Registration successful');
                    }


                    $ajaxResult['content'] = $form->render();
                } else {
                    Eabi_Ipenelo_Calendar::service()->get('database')->update($model->getTableName(), $res, array('id' => $model->id));
                }

            } else if ($res !== false && count($validationResult) > 0) {
                foreach ($validationResult as $error) {
                    self::addError($error);
                    $ajaxResult['error_messages'][] = $error;
                }
                $res['event_id'] = $_REQUEST['event_id'];
                $form->setModel($res);
                $ajaxResult['content'] = $form->render();
            } else {
                //larger problem
            }
        }





        echo json_encode($ajaxResult);
        die();
    }

    /**
      Displays Event Grid for the administration side.

     */
    public function manage_events() {
        Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'applyStatuses');
        if (isset($_GET['enable_links'])) {
            Eabi_Ipenelo_Calendar::set('show_link', 1);
            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Thank you for your support!'));
            wp_redirect(admin_url('admin.php?page=ipenelo_calendar'));
            die();
        }

        //handle delete
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'delete':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'deleteEvent')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }

                        //perform the delete

                        $deleteModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');
                        $deleteRegModel = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');

                        if ($valid) {
//							echo "delete from ".$deleteModel->getTableName()." where id in (".implode(',', $ids).")";
                            $regRows = Eabi_Ipenelo_Calendar::service()->get('database')->query("delete from " . $deleteRegModel->getTableName() . " where event_id in (" . implode(',', $ids) . ")");
                            $rows = Eabi_Ipenelo_Calendar::service()->get('database')->query("delete from " . $deleteModel->getTableName() . " where id in (" . implode(',', $ids) . ")");
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Number of registrants deleted:') . ' ' . $regRows);
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Number of events deleted:') . ' ' . $rows);
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=ipenelo_calendar'));
                    die();
                    break;
                case 'updateStatus':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'updateStatus')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }

                        //perform the delete

                        $model = Eabi_Ipenelo_Calendar::service()->get('models/Event');

                        $newStatus = $_GET['status'];
                        $allowedStatuses = Eabi_Ipenelo_Calendar_Model_Event::toStatusArray();
                        if (!isset($allowedStatuses[$newStatus])) {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Invalid new status'));
                            $valid = false;
                        }

                        if ($valid) {
//							echo "update ".$model->getTableName()." set status = ".$newStatus." where id in (".implode(',', $ids).")";
//							die();
                            $rows = Eabi_Ipenelo_Calendar::service()->get('database')->query("update " . $model->getTableName() . " set status = " . $newStatus . " where id in (" . implode(',', $ids) . ")");
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Number of events updated to new status:') . $rows);
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('New status:') . ' ' . $allowedStatuses[$newStatus]);
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=ipenelo_calendar'));
                    die();
                    break;
            }
        }


        $grid = Eabi_Ipenelo_Calendar::service()->get('grids/Event');


        echo $grid->render();

        return;
    }

    /**
      Edits or adds a new event for the Administration side.

     */
    public function new_event() {
        Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'applyStatuses');
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $model = Eabi_Ipenelo_Calendar::service()->get('models/Event');
        $model->load($id);



        $event = Eabi_Ipenelo_Calendar::service()->get('forms/Event', (array) $model);
        $event->setRenderOnlyCore(false);
        $event->setFormElementHtml('<tr valign="top"><th scope="row">${LABEL}</th><td>${INPUT}', 'url');
        $event->setFormElementHtml('<div class="url_click_title">${INPUT} ${LABEL}</div></td></tr>', 'url_click_title');
        $html = $event->render();

        $res = $event->toDb();

        if (isset($res['is_full_day']) && $res['is_full_day'] == '1') {
            $res['active_from'] = str_replace(substr($res['active_from'], -8), '00:00:00', $res['active_from']);
            $res['active_to'] = str_replace(substr($res['active_to'], -8), '23:59:00', $res['active_to']);
        }
        //validate
        $validationResult = $event->validate($res);
//		echo '<pre>'.print_r($res, true).'</pre>';
//		echo '<pre>'.print_r($validationResult, true).'</pre>';
//		die();

        if ($res !== false && count($validationResult) == 0 && !$event->getReadOnly()) {
            if (isset($res['is_paid_event']) && $res['is_paid_event'] == 0) {
                $res['cost'] = 0;
            }


            if (isset($res['url']) && $res['url'] == '' && $res['url_click_title'] == '') {
                $res['url_click_title'] = 0;
            }

            if (isset($res['is_paid_event']) && $res['is_paid_event'] == 1) {
                //WARN if currency has not been set
                if (strlen(Eabi_Ipenelo_Calendar::get('currency_iso')) != 3) {
                    self::addError($this->__->l('You just saved paid event, but currency has not been set up properly under Settings menu!'));
                }
                $availablePayments = @unserialize(Eabi_Ipenelo_Calendar::get('available_payments'));
                $paymentMethods = array();
                if (!is_array($availablePayments)) {
                    $availablePayments = array();
                }
                foreach ($availablePayments as $code => $availablePayment) {
                    if ($availablePayment['enabled']) {
                        $paymentMethods[$code] = $availablePayment['title'];
                    }
                }
                if (count($paymentMethods) == 0) {
                    self::addError($this->__->l('You just saved a paid event, but you don\'t have any active payment methods'));
                }
            }
            if (!isset($model->id)) {
                Eabi_Ipenelo_Calendar::service()->get('database')->insert($model->getTableName(), $res);
                $model->id = Eabi_Ipenelo_Calendar::service()->get('database')->insert_id;
            } else {
                Eabi_Ipenelo_Calendar::service()->get('database')->update($model->getTableName(), $res, array('id' => $model->id));
            }
            if ($model->id <= 0) {
                self::addError($this->__->l('Event save failed'));
                if (Eabi_Ipenelo_Calendar::service()->get('database')->last_error != '') {
                    self::addError(htmlspecialchars(Eabi_Ipenelo_Calendar::service()->get('database')->last_error));
                }
            } else {
                self::addMessage($this->__->l('Event has been successfully saved'));
            }

            wp_redirect(admin_url('admin.php?page=ipenelo_calendar_new&id=' . $model->id));
            exit();
        } else if ($res !== false && count($validationResult) > 0) {
            foreach ($validationResult as $error) {
                self::addError($error);
            }
            $event = Eabi_Ipenelo_Calendar::service()->get('forms/Event', (array) $res);
            $event->reset();
            $event->setFormElementHtml('<tr valign="top"><th scope="row">${LABEL}</th><td>${INPUT}', 'url');
            $event->setFormElementHtml('<div class="url_click_title">${INPUT} ${LABEL}</div></td></tr>', 'url_click_title');
            $event->setRenderOnlyCore(false);

            if (isset($_GET['noheader'])) {
                require_once(ABSPATH . 'wp-admin/admin-header.php');
            }
            $html = $event->render();


//			$event->setModel($res);
        } else {
            //larger problem
        }
//		echo '<pre>'.print_r($event->toDb(), true).'</pre>';

        echo $html;
    }

    /**

      @deprecated
     */
    public function edit_event() {
        echo 'edit event';
    }

    /**
      Edits or adds new category in the administration side.

     */
    public function edit_category() {

        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $model = Eabi_Ipenelo_Calendar::service()->get('models/Category');
        $model->load($id);

        if (!isset($model->id)) {
            $model->is_active = '1';
            $model->sort_order = '1';
        }




        $event = Eabi_Ipenelo_Calendar::service()->get('forms/Category', (array) $model);
        $event->setRenderOnlyCore(false);


        $html = $event->render();

        $res = $event->toDb();

        //validate
        $validationResult = $event->validate($res);
//		echo '<pre>'.print_r($res, true).'</pre>';
//		echo '<pre>'.print_r($validationResult, true).'</pre>';
//		die();

        if ($res !== false && count($validationResult) == 0 && !$event->getReadOnly()) {
            if (!isset($model->id)) {
                Eabi_Ipenelo_Calendar::service()->get('database')->insert($model->getTableName(), $res);
                $model->id = Eabi_Ipenelo_Calendar::service()->get('database')->insert_id;
            } else {
                Eabi_Ipenelo_Calendar::service()->get('database')->update($model->getTableName(), $res, array('id' => $model->id));
            }
            
            if ($model->id <= 0) {
                self::addError($this->__->l('Category save failed'));
                if (Eabi_Ipenelo_Calendar::service()->get('database')->last_error != '') {
                    self::addError(htmlspecialchars(Eabi_Ipenelo_Calendar::service()->get('database')->last_error));
                }
            } else {
                self::addMessage($this->__->l('Category successfully saved'));
            }

            wp_redirect(admin_url('admin.php?page=ipenelo_calendar_categories'));
            exit();
        } else if ($res !== false && count($validationResult) > 0) {
            foreach ($validationResult as $error) {
                self::addError($error);
            }
            if (isset($_GET['noheader'])) {
                require_once(ABSPATH . 'wp-admin/admin-header.php');
            }
            $event->setModel($res);
        } else {
            //larger problem
        }
//		echo '<pre>'.print_r($event->toDb(), true).'</pre>';

        echo $html;
        //activate the first submenu element
        echo $this->openMenu(3);
    }

    /**
      Displays a Grid of categories for the administration side.

     */
    public function manage_categories() {
        /* @var $plugin_page string */
        global $plugin_page;

        //check the event and redirect to the front page on non-existent event
        //handle delete
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'delete':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'deleteCategory')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }

                        //perform the delete
                        $deleteModel = Eabi_Ipenelo_Calendar::service()->get('models/Category');

                        if ($valid) {
                            $event = Eabi_Ipenelo_Calendar::service()->get('models/Event');
                            $rows = 0;
                            foreach ($ids as $id) {
                                //check if there are any events
                                $id = (int) trim($id);
                                $eventCount = Eabi_Ipenelo_Calendar::service()->get('database')->get_var("select count(id) from " . $event->getTableName() . " where main_category_id = " . $id);
                                if ($eventCount > 0) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Category with id %s cannot be deleted because it has events'), $id));
                                } else {
                                    $rows += Eabi_Ipenelo_Calendar::service()->get('database')->query("delete from " . $deleteModel->getTableName() . " where id = " . $id);
                                }
                            }
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Number of categories deleted:') . $rows);
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page));
                    die();
                    break;
            }
        }

        $grid = Eabi_Ipenelo_Calendar::service()->get('grids/Category');


        echo $grid->render();

        return;
    }

    /**
      Displays grid of Registrants for the administration side for a specific event.

     */
    public function view_registrants() {
        global $plugin_page;
        //check the event and redirect to the front page on non-existent event

        Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'applyStatuses');
        $eventModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');
        if (isset($_REQUEST['event_id']) && is_numeric($_REQUEST['event_id'])
                && $eventModel->exists($_REQUEST['event_id'])) {
            
        } else {
            self::addError($this->__->l('Event does not exist'));
            wp_redirect(admin_url('admin.php?page=ipenelo_calendar'));
            exit();
        }


        //handle delete
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'delete':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'deleteRegistrant')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }

                        //perform the delete
                        $deleteModel = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');

                        if ($valid) {
//							echo "delete from ".$deleteModel->getTableName()." where id in (".implode(',', $ids).")";
                            $rows = Eabi_Ipenelo_Calendar::service()->get('database')->query("delete from " . $deleteModel->getTableName() . " where id in (" . implode(',', $ids) . ")");
                            Eabi_Ipenelo_Calendar::addMessage($this->__->l('Number of registrants deleted:') . $rows);
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page) . '&event_id=' . $_GET['event_id']);
                    die();
                    break;
                case 'markpaid':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'markPaidRegistrant')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);
                    $eventModel->load($_GET['event_id']);
                    if (!$eventModel->is_paid_event) {
                        Eabi_Ipenelo_Calendar::addError($this->__->l('This is not paid event'));
                        $valid = false;
                    }

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }


                        if ($valid) {
                            Eabi_Ipenelo_Calendar::service()->import('models/Registrant');
                            //find the new status
                            $newStatus = Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PAYMENT_ACCEPTED;


                            foreach ($ids as $id) {
                                //load the registrant
                                $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                                $registrant->load($id);
                                if (!isset($registrant->id)) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with id %s does not exist'), $id));
                                    continue;
                                }

                                //check the status
                                if ($registrant->markAsPaid()) {
                                    Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Registrant with email %s marked as paid'), $registrant->email));
                                } else {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with email %s could not be marked as paid'), $registrant->email));
                                }
                            }
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page) . '&event_id=' . $_GET['event_id']);
                    die();
                    break;
                case 'markaccepted':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'markAcceptedRegistrant')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);
                    $eventModel->load($_GET['event_id']);
                    $paidEvent = true;
                    if (!$eventModel->is_paid_event) {
                        $paidEvent = false;
                    }

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }


                        if ($valid) {
                            Eabi_Ipenelo_Calendar::service()->import('models/Registrant');
                            //find the new status
                            $newStatus = Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_ACCEPTED;


                            foreach ($ids as $id) {
                                //load the registrant
                                $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                                $registrant->load($id);
                                if (!isset($registrant->id)) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with id %s does not exist'), $id));
                                    continue;
                                }

                                if ($paidEvent && $registrant->status == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PENDING) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with email %s has not paid, mark the registrant as paid before accepting'), $registrant->email));
                                    continue;
                                }

                                if (($paidEvent && $registrant->status == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PAYMENT_ACCEPTED)
                                        || (!$paidEvent && $registrant->status == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PENDING)) {
                                    Eabi_Ipenelo_Calendar::service()->get('database')->update($registrant->getTableName(), array('status' => $newStatus), array('id' => $registrant->id));

                                    $eventParams = array(
                                        'registrant' => (array) $registrant,
                                        'event' => (array) $eventModel,
                                    );
                                    Eabi_Ipenelo_Calendar::service()->get('event')->event('accept_registrant', $eventParams);



                                    Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Registrant with email %s marked as accepted'), $registrant->email));
                                } else {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with email %s is already accepted or rejected and cannot be accepted'), $registrant->email));
                                }
                            }
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page) . '&event_id=' . $_GET['event_id']);
                    die();
                    break;
                case 'markrejected':
                    $valid = true;
                    if (!wp_verify_nonce($_GET['_wpnonce'], 'markRejectedRegistrant')) {
                        $valid = false;
                        Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                    }
                    $ids = explode(',', $_GET['ids']);
                    $eventModel->load($_GET['event_id']);
                    $paidEvent = true;
                    if (!$eventModel->is_paid_event) {
                        $paidEvent = false;
                    }

                    if (count($ids) > 0 && $valid) {
                        for ($i = 0; $i < count($ids); $i++) {
                            $ids[$i] = (int) trim($ids[$i]);
                            if ($ids[$i] == 0) {
                                $valid = false;
                                break;
                            }
                        }


                        if ($valid) {
                            Eabi_Ipenelo_Calendar::service()->import('models/Registrant');
                            //find the new status
                            $newStatus = Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_REJECTED;


                            foreach ($ids as $id) {
                                //load the registrant
                                $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                                $registrant->load($id);
                                if (!isset($registrant->id)) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with id %s does not exist'), $id));
                                    continue;
                                }

                                if ($paidEvent && in_array($registrant->status, array(Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PAYMENT_ACCEPTED, Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_ACCEPTED))) {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with email %s has probably paid, maybe he needs a refund'), $registrant->email));
                                }

                                if (in_array($registrant->status, Eabi_Ipenelo_Calendar_Model_Registrant::allowedStatuses())) {
                                    Eabi_Ipenelo_Calendar::service()->get('database')->update($registrant->getTableName(), array('status' => $newStatus), array('id' => $registrant->id));

                                    $eventParams = array(
                                        'registrant' => (array) $registrant,
                                        'event' => (array) $eventModel,
                                    );
                                    Eabi_Ipenelo_Calendar::service()->get('event')->event('reject_registrant', $eventParams);

                                    Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Registrant with email %s marked as rejected'), $registrant->email));
                                } else {
                                    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Registrant with email %s has already been rejected'), $registrant->email));
                                }
                            }
                        } else {
                            Eabi_Ipenelo_Calendar::addError($this->__->l('Security check failed'));
                        }
                    }
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page) . '&event_id=' . $_GET['event_id']);
                    die();
                    break;
                default:
                    Eabi_Ipenelo_Calendar::addError($this->__->l('Invalid action'));
                    wp_redirect(admin_url('admin.php?page=' . $plugin_page) . '&event_id=' . $_GET['event_id']);
                    die();
                    break;
            }
        }

        $grid = Eabi_Ipenelo_Calendar::service()->get('grids/Registrant');

        echo $grid->render();

        //activate the first submenu element
        echo $this->openMenu(1);


        return;
    }

    /**
      Prints out Javascript, which opens up the Wordpress menu.
      Used for the pages, which are not listed under the menu.
      For example, when viewing registrants for an event, there is no menu element for that.
      Instead Calendar -> Events is marked as active.
      @param number - nth element to be marked as active.

     */
    public function openMenu($number = 0) {
        $js = '';
        $number = (int) $number;
        if ($number > 0) {
            $js = <<<EOT
.find('li:nth-child({$number})').addClass('current').find('a').addClass('current');
EOT;
        }
        $html = <<<EOT
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
			jQuery("li#toplevel_page_ipenelo_calendar").addClass('wp-menu-open'){$js};
			});
			
		</script>
		
EOT;
        return $html;
    }

    /**
      Adds or edits registrant in the administration menu

     */
    public function edit_registrant() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $model = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
        $model->load($id);

        if (!isset($model->id)) {
            $model->registration_date = date('Y-m-d H:i:s', current_time('timestamp'));

            //check the event id
            $eventModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');

            if (isset($_REQUEST['event_id']) && is_numeric($_REQUEST['event_id'])
                    && $eventModel->exists($_REQUEST['event_id'])) {
                $model->event_id = $_REQUEST['event_id'];
            } else {
                self::addError($this->__->l('Event does not exist'));
                wp_redirect(admin_url('admin.php?page=ipenelo_calendar'));
                exit();
            }
        }



        $event = Eabi_Ipenelo_Calendar::service()->get('forms/Registrant', (array) $model);

        $event->setRenderOnlyCore(false);
        if (isset($_GET['event_id'])) {
            $event->addHiddenField('event_id', $_GET['event_id']);
        }


        $html = $event->render();

        $res = $event->toDb();
        $evModel = $event->getModel();
        if ((int) $id > 0) {
            $evModel['id'] = $id;
            $event->setModel($evModel);
        }

        //validate
        $validationResult = $event->validate($res);
//		echo '<pre>'.print_r($res, true).'</pre>';
//		echo '<pre>'.print_r($validationResult, true).'</pre>';
//		die();

        if ($res !== false && count($validationResult) == 0 && !$event->getReadOnly()) {
            unset($res['id']);
            if (!isset($model->id)) {
                $res['order_hash'] = $model->getHash();

                //if wp_user_id has been set and first_name, last_name is empty then set them
                if ($res['wp_user_id'] > 0 && ($res['first_name'] == '' || $res['last_name'] == '')) {
                    $userData = get_userdata($res['wp_user_id']);
                    if ($userData === false) {
                        throw new Exception('Invalid wp_user_id');
                    }

                    if ($res['first_name'] == '') {
                        $res['first_name'] = $userData->user_firstname;
                    }
                    if ($res['last_name'] == '') {
                        $res['last_name'] = $userData->user_lastname;
                    }
                }

                //if status == STATUS_ACCEPTED (check payment date, and warn else mark paid, accept)
                //if status == PAYMENT_ACCEPTED (set payment date, mark paid)
                $runMarkPaid = false;
                $runMarkAccepted = false;
                $runMarkRejected = false;
                if ($res['status'] == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_ACCEPTED) {
                    $eventModel->load($_GET['event_id']);
                    if ($eventModel->is_paid_event) {
                        $runMarkPaid = true;
                        if ($res['payment_date'] == '' || $res['payment_date'] == '0000-00-00 00:00:00') {
                            self::addError($this->__->l('Payment date was not set for the event, if the saved Registrant did not actually pay, then re-open the registrant and verify details before saving'));
                        }
                    }
                    $runMarkAccepted = true;
                }
                if ($res['status'] == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PAYMENT_ACCEPTED) {
                    $runMarkPaid = true;
                }
                if ($res['status'] == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_REJECTED) {
                    $runMarkRejected = true;
                }


                Eabi_Ipenelo_Calendar::service()->get('database')->insert($model->getTableName(), $res);
                $model->id = Eabi_Ipenelo_Calendar::service()->get('database')->insert_id;
                if ($model->id > 0) {

                    $eventParams = array(
                        'registrant' => (array) $model->load($model->id),
                        'event' => (array) $eventModel->load($model->event_id),
                    );
                    Eabi_Ipenelo_Calendar::service()->get('event')->event('new_registrant', $eventParams);

                    if ($runMarkPaid) {
                        if ($model->markAsPaid(true)) {
                            self::addMessage($this->__->l('Registrant was marked as paid'));
                        } else {
                            self::addError($this->__->l('Event was not marked as paid'));
                        }
                    }
                    if ($runMarkAccepted) {
                        self::addMessage($this->__->l('Registrant marked as accepted'));
                        Eabi_Ipenelo_Calendar::service()->get('event')->event('accept_registrant', $eventParams);
                    }
                    if ($runMarkRejected) {
                        self::addMessage($this->__->l('Registrant marked as rejected'));
                        Eabi_Ipenelo_Calendar::service()->get('event')->event('reject_registrant', $eventParams);
                    }
                }
            } else {
                Eabi_Ipenelo_Calendar::service()->get('database')->update($model->getTableName(), $res, array('id' => $model->id));
            }

            if ($model->id <= 0) {
                self::addError($this->__->l('Registrant failed'));
                if (Eabi_Ipenelo_Calendar::service()->get('database')->last_error != '') {
                    self::addError(htmlspecialchars(Eabi_Ipenelo_Calendar::service()->get('database')->last_error));
                }
            } else {
                self::addMessage($this->__->l('Registrant successfully saved'));
            }

            wp_redirect(admin_url('admin.php?page=ipenelo_calendar_view_registrants&event_id=' . $model->event_id));
            exit();
        } else if ($res !== false && count($validationResult) > 0) {
            foreach ($validationResult as $error) {
                self::addError($error);
            }
            if (isset($_GET['noheader'])) {
                require_once(ABSPATH . 'wp-admin/admin-header.php');
            }
            if ((int) $id > 0) {
                $res['id'] = $id;
            }

            $event = Eabi_Ipenelo_Calendar::service()->get('forms/Registrant', (array) $res);
            $event->reset();
            $event->setModel($res);
            $html = $event->render();
        } else {
            //larger problem
        }
//		echo '<pre>'.print_r($event->toDb(), true).'</pre>';

        echo $html;
        //activate the first submenu element
        echo $this->openMenu(1);
    }

    /**
      Displays a Calendar configuration menu.
     */
    public function edit_calendar_config() {

        $model = Eabi_Ipenelo_Calendar::service()->get('models/Configuration')->load();

        $html = '';



        $event = Eabi_Ipenelo_Calendar::service()->get('forms/Configuration', (array) $model);

        $event->setOptionFunctions($model->getOptionFunctions());
        $html .= $event->render();

        $res = $event->toDb();
        if ($res !== false) {
            foreach ($res as $key => $value) {
                update_option($key, $value);
            }
            self::addMessage($this->__->l('Settings have been successfully saved'));
            wp_redirect(admin_url('admin.php?page=ipenelo_calendar_config'));
            exit();
        }
        Eabi_Ipenelo_Calendar::service()->import('events/Abstract');
        Eabi_Ipenelo_Calendar_Event_Abstract::detectAvailableEventHandlers();

        echo $html;
    }

    /**
     * Add intermediate image sizes to media gallery modal dialog
     */
    public function image_sizes_attachment_fields_to_edit($form_fields, $post) {
        if (!is_array($imagedata = wp_get_attachment_metadata($post->ID))) {
            return $form_fields;
        }
        $translations = array(
            'ipenelo_calendar_thumb' => 'Scheduler Thumbnail',
        );


        if (is_array($imagedata['sizes'])) {
            foreach ($imagedata['sizes'] as $size => $val) {
                $skips = array('thumbnail', 'medium', 'large');
                $allowed = array('ipenelo_calendar_thumb');
                if (!in_array($size, $skips) && in_array($size, $allowed)) {
                    $css_id = "image-size-{$size}-{$post->ID}";
                    $checked = isset($_GET['ipenelo-calendar']) ? ' checked="checked"' : '';
                    $html .= '<div class="image-size-item"><input type="radio" name="attachments[' . $post->ID . '][image-size]" id="' . $css_id . '" value="' . $size . '"' . $checked . '/>';
                    $html .= '<label for="' . $css_id . '">' . $this->__->l($translations[$size]) . '</label>';
                    $html .= ' <label for="' . $css_id . '" class="help">' . sprintf($this->__->l("(%d&nbsp;&times;&nbsp;%d)"), $val['width'], $val['height']) . '</label>';
                    $html .= '</div>' . print_r($_SESSION['ipenelo-calendar_add_size'], true);
                }
            }
        }

        $form_fields['image-size']['html'] .= $html;
        return $form_fields;
    }

    /**
      Shorthand for the get_option(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX.$option, $default) function.

     */
    public static function get($option, $default = false) {
        if (self::$_configurationInstance == null) {
            self::$_configurationInstance = Eabi_Ipenelo_Calendar::service()->get('models/Configuration')->load();
        }
        return self::$_configurationInstance->get($option, $default);
    }

    /**
      Shorthand for the update_option(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX.$option, $value) function.

     */
    public static function set($option, $value) {
        if (self::$_configurationInstance == null) {
            self::$_configurationInstance = Eabi_Ipenelo_Calendar::service()->get('models/Configuration')->load();
        }
        return self::$_configurationInstance->set($option, $value);
    }

    public static function addMessage($message) {
        if (!isset($_SESSION['eabi_ipenelo_calendar_display_messages'])) {
            $_SESSION['eabi_ipenelo_calendar_display_messages'] = array();
        }
        $_SESSION['eabi_ipenelo_calendar_display_messages'][] = $message;
    }

    public static function addError($message) {
        if (!isset($_SESSION['eabi_ipenelo_calendar_display_errors'])) {
            $_SESSION['eabi_ipenelo_calendar_display_errors'] = array();
        }
        $_SESSION['eabi_ipenelo_calendar_display_errors'][] = $message;
    }

    public static function displayMessages() {
        $html = '';


        if (isset($_SESSION['eabi_ipenelo_calendar_display_messages'])
                && is_array($_SESSION['eabi_ipenelo_calendar_display_messages'])
                && count($_SESSION['eabi_ipenelo_calendar_display_messages']) > 0) {
            $html .= '<div id="message" class="updated fade">';
            foreach ($_SESSION['eabi_ipenelo_calendar_display_messages'] as $msg) {
                $html .= '<p><strong>' . ($msg) . '</strong></p>';
            }
            $html .= '</div>';
        }
        $_SESSION['eabi_ipenelo_calendar_display_messages'] = array();
        return $html;
    }

    public static function displayErrors() {
        $html = '';
        if (isset($_SESSION['eabi_ipenelo_calendar_display_errors'])
                && is_array($_SESSION['eabi_ipenelo_calendar_display_errors'])
                && count($_SESSION['eabi_ipenelo_calendar_display_errors']) > 0) {
            $html .= '<div id="error" class="error fade">';
            foreach ($_SESSION['eabi_ipenelo_calendar_display_errors'] as $msg) {
                $html .= '<p><strong>' . ($msg) . '</strong></p>';
            }
            $html .= '</div>';
        }

        $_SESSION['eabi_ipenelo_calendar_display_errors'] = array();
        return $html;
    }
    
    private static $_loadedService;
    
    /**
     * Returns the main service handler for the application.
     * Instance that is returned offers two methods:
     * get(string[, argument, argument, ...])
     * import(string)
     * 
     * self::service()->get() returns instance of the requested object type and also supplies the arguments if any
     * self::service()->import() simply imports the class by path so you could use them to call static methods.
     * 
     * When accessing the ->get() method, then no prior import is neccessary.
     * 
     * All the instances of this framework should be called thru this method, since objects returned are initiated with database connection
     * for example.
     * 
     * @return Eabi_Ipenelo_Calendar_Service_Handler 
     */
    public static function service() {
        if (self::$_loadedService === null) {
            self::import('services/Handler');
            self::$_loadedService = self::instance('services/Handler', __CLASS__, 'configurations/Free', __FILE__);
        }
        
        return self::$_loadedService;
    }



    public static function path() {
        return plugin_dir_path(__FILE__);
    }

    
    

    /**
      Takes in indexed array, where each element should contain assoc array like this:
      array(
      'name' => name,
      'value' => value,
      );
      and converts the inputed array into assoc array like this:
      array[name] = value;
      @param indexed array
      @return converted array


     */
    public static function convert(array $array) {
        $result = array();
        foreach ($array as $elem) {
            $result[$elem['name']] = $elem['value'];
        }
        return $result;
    }

    public static function escJs($var) {
        return addslashes(htmlspecialchars($var));
    }


    /**
      Wrapper function for DateTimeCreateFromFormat, since it does not exist in php 5.2


     */
    public static function createDateFromFormat($format, $string) {
        if (self::$_useNativeDateParser === null) {
            if (is_callable(array('DateTime', 'createFromFormat'))) {
                self::$_useNativeDateParser = true;
            } else {
                Eabi_Ipenelo_Calendar::service()->import('helpers/Dateparser');
                self::$_useNativeDateParser = false;
            }
        }
        if (self::$_useNativeDateParser === true) {
            return DateTime::createFromFormat($format, $string);
        } else {
            return Eabi_Ipenelo_Calendar::service()->getStatic('helpers/Dateparser', 'parse', $format, $string);
        }
    }

    /**
      Outputs contents of a variable in HTML friendly format.
      Does not echo, only returns variable.

     */
    public function d($var) {
        $res = date('Y-m-d H:i:s') . ' <pre>' . htmlspecialchars(print_r($var, true)) . '</pre>' . "\r\n";
//		file_put_contents(plugin_dir_path(__FILE__).'logs/log.txt',$res, FILE_APPEND);
        return $res;
    }

    
    private static function import($path) {
        $splitClasses = explode('/', $path);
        if (count($splitClasses) !== 2) {
            throw new Exception('invalid import type');
        }
        if (!class_exists(__CLASS__ . '_' . ucfirst($splitClasses[0]) . '_' . ucfirst($splitClasses[1]))) {
            require_once(plugin_dir_path(__FILE__) . 'classes/' . $path . '.php');
        }
    }

    private static $_usedPaths = array();

    /**
     *  Returns new instance of the class, specified by path and additional arguments, which are directly passed to the constructor function.
     * Example: Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
     * 
     * and the returned variable would be the instance of Eabi_Ipenelo_Calendar_Model_Registrant
     * 
     * 
     * 
     * 
     * @param type $path
     * @return type 
     */
    private static function instance($path) {
        $className = null;
        if (!isset(self::$_usedPaths[$path])) {
            Eabi_Ipenelo_Calendar::import($path);
            $splitClasses = explode('/', $path);
            /* @var $splitClasses string */
            $className = __CLASS__ . '_' . substr(ucfirst($splitClasses[0]), 0, -1) . '_' . ucfirst($splitClasses[1]);
            self::$_usedPaths[$path] = $className;
        } else {
            //class has been imported
            $className = self::$_usedPaths[$path];
        }
        //return the new instance
        //get the arguments
        $arguments = array();

        $tmpArgs = func_get_args();
        foreach ($tmpArgs as $i => $tmpArg) {
            if ($i > 0) {
                $arguments[] = $tmpArg;
            }
        }

        $reflectionMethod = new ReflectionMethod($className, '__construct');
        $params = $reflectionMethod->getParameters();

        $constructorArgs = array();
        foreach ($params as $key => $param) {
            if ($param->isPassedByReference()) {
                $constructorArgs[$key] = &$arguments[$key];
            } else {
                $constructorArgs[$key] = $arguments[$key];
            }
        }

        //get the class constructor with arguments.
        $reflectionClass = new ReflectionClass($className);
        return $reflectionClass->newInstanceArgs($constructorArgs);
    }

    /**
     *  Translator interface
     * 
     * 
     * @var Eabi_Ipenelo_Calendar_Helper_Translator 
     */
    protected $__;
    
    /**
     *
     * @param Eabi_Ipenelo_Calendar_Helper_Translator $translator 
     */
    public function setTranslator($translator) {
        $this->__ = $translator;
    }
    
    
}

