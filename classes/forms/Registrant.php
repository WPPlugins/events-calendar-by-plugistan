<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
/**
  Displays the Admin side Registrant edit form.

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

class Eabi_Ipenelo_Calendar_Form_Registrant extends Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;

    protected $_formName = 'ipenelo-calendar-registrant';

    public function render() {
        $validation = Eabi_Ipenelo_Calendar::service()->get('helpers/Validation');
        $html = '';
        global $plugin_page;

        if (!$this->_renderOnlyCore) {
            $html .= '<div class="wrap">';
            $html .= "\r\n";

            //title icon
            $html .= '<div class="icon32" id="icon-options-general"><br></div>';
            $html .= "\r\n";

            $regListHtml = '<a class="add-new-h2" href="' . admin_url('admin.php?page=ipenelo_calendar_view_registrants&event_id=' . $this->_isset($this->_data, 'event_id')) . '">' . $this->__->l('Back to registrants list') . '</a>';

            //title itself
            if ($this->_isset($this->_data, 'email')) {
                $html .= '<h2>' . $this->__->l('Edit Registrant') . ' (' . htmlspecialchars(Eabi_Ipenelo_Calendar::service()->get('models/Event')->load($this->_isset($this->_data, 'event_id'))->title).') ' . $regListHtml . '</h2>';
            } else {
                $html .= '<h2>' . $this->__->l('New Registrant') . $regListHtml . '</h2>';
            }
            $html .= "\r\n";
            $html .= Eabi_Ipenelo_Calendar::displayErrors();
            $html .= Eabi_Ipenelo_Calendar::displayMessages();

            //form
            $html .= '<form action="' . admin_url('admin.php?noheader=true&page=' . $plugin_page . '&id=' . $this->_isset($this->_data, 'id') . '&event_id=' . $this->_isset($this->_data, 'event_id')) . '" method="post" id="' . $this->_formName . '">';
            $html .= "\r\n";

            $html .= '<table class="form-table">';

            $html .= '<tbody>';
        }


        //first_name
        $this->addTextField('first_name', $this->__->l('First name'), $this->_isset($this->_data, 'first_name', true));
        //end first_name
        //last_name
        $this->addTextField('last_name', $this->__->l('Last name'), $this->_isset($this->_data, 'last_name', true));
        //end last_name
        //wp_user_id

        $users = $this->_dbi->get_results(
                $this->_dbi->prepare(
                        "SELECT ".$this->_dbi->users.".ID, ".$this->_dbi->users.".user_nicename FROM ".$this->_dbi->users." ORDER BY %s ASC"
                        , 'user_nicename'
                )
        );
        $suppliedUsers = array(
            '' => '',
        );
        foreach ($users as $user) {
            $suppliedUsers[$user->ID] = $user->user_nicename;
        }


        $this->addSelectField('wp_user_id', $this->__->l('Registered user'), $this->_isset($this->_data, 'wp_user_id', true), $suppliedUsers);
        //end wp_user_id
        //email
        $this->addTextField('email', $this->__->l('E-mail'), $this->_isset($this->_data, 'email', true));
        $this->addValidatorRule('email', array(&$validation, 'required'), $this->__->l('Email is required'), $validation->js_required());
        $this->addValidatorRule('email', array(&$this, 'uniqueUserEmail'), $this->__->l('Supplied E-mail has already been used to register for this event'));
        $this->addValidatorRule('email', array(&$this, 'emailReadonly'), sprintf($this->__->l('You cannot change the email of %s for this registrant'), $this->_isset($this->_data, 'email', true)));

        $registeredUserEmail = '';
        if (isset($this->_data['wp_user_id']) && is_numeric($this->_data['wp_user_id']) && ((int) $this->_data['wp_user_id']) > 0) {
            $userData = get_userdata($this->_data['wp_user_id']);
            if ($userData === false) {
                throw new Exception('Invalid wp_user_id supplied');
            }
            $registeredUserEmail = $userData->user_email;
        }


        $this->addValidatorRule('email', array(&$this, 'emailForRegisteredUser'), sprintf($this->__->l('Correct email for the registered user is %s. You need to use this email, if you wish to assign this entry to the selected registered user'), $registeredUserEmail));
        $this->addValidatorRule('email', array(&$this, 'userIdForEmail'), $this->__->l('Entered email belongs to logged in user'));
        //end email
        //status
        $statuses = Eabi_Ipenelo_Calendar::service()->getStatic('models/Registrant', 'toStatusArray');

        $this->addSelectField('status', $this->__->l('Registrant status'), $this->_isset($this->_data, 'status', true), $statuses);
        $this->addValidatorRule('status', array(&$validation, 'required'), $this->__->l('Registrant status is required'), $validation->js_required());

        //end status
        //registration_date
        $this->addDateTimeField('registration_date', $this->__->l('Registration date'), $this->_isset($this->_data, 'registration_date', true));
        $this->addValidatorRule('registration_date', array(&$validation, 'required'), $this->__->l('Registration date is required'));
        $this->addValidatorJsRule('registration_date_date', $validation->js_required());
        $this->addValidatorJsRule('registration_date_time', $validation->js_required());

        $this->addValidatorRule('registration_date', array(&$validation, 'date'), $this->__->l('Registration date is invalid'));


        $formHelperTimeText = $this->__->l('It\'s possible to use arrow keys, page-up, page-down or mouse to adjust time.');
        $this->addFormHelper('registration_date_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end registration_date
        //start payment_method

        $paymentMethods = array(
            '' => '',
        );
        $availablePayments = @unserialize(Eabi_Ipenelo_Calendar::get('available_payments'));
        if (!is_array($availablePayments)) {
            $availablePayments = array();
        }
        foreach ($availablePayments as $code => $availablePayment) {
            $paymentMethods[$code] = $availablePayment['title'];
        }


        $this->addSelectField('payment_method', $this->__->l('Payment method'), $this->_isset($this->_data, 'payment_method', true), $paymentMethods);

        //end payment_method
        //payment_date
        $this->addDateTimeField('payment_date', $this->__->l('Payment date'), $this->_isset($this->_data, 'payment_date', true));

        $this->addValidatorRule('payment_date', array(&$validation, 'date'), $this->__->l('Payment date is invalid'));
        $this->addFormHelper('payment_date_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end payment_date



        $html .= $this->_render();


        if (!$this->_renderOnlyCore) {

            $html .= '</tbody>';

            $html .= '</table>';

            //submit button
            $html .= '<p class="submit">';
            $html .= '<input type="submit" value="' . $this->__->l('Save changes') . '" class="button-primary" id="submi" name="submi">';
            $html .= '</p>';



            $html .= '</form>';
            $html .= "\r\n";

            //close div.wrap
            $html .= '</div>';
            $html .= "\r\n";
        }
        $html .= $this->collectJs();
        return $html;
    }

    public function userNotLoggedIn($value) {
        if ($value == '') {
            return true;
        }
        $event_id = $this->_isset($this->_data, 'event_id', false);
        if (!$event_id) {
            //on no event id, unique validation is not valid
            //because no registration possible if the event is not known.
            return false;
        }
        if (!is_user_logged_in() && email_exists($value)) {
            return false;
        }
        return true;
    }

    public function loggedInEmailKeep($value) {
        if (!is_user_logged_in()) {
            return true;
        }
        $currentUser = wp_get_current_user();
        return $value == $currentUser->user_email;
    }

    public function emailReadonly($value) {
        if (isset($this->_data['id']) && is_numeric($this->_data['id']) && ((int) $this->_data['id']) > 0) {
            $oldEmail = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
            $oldEmail->load($this->_data['id']);
            if (isset($oldEmail->email) && $oldEmail->email == $value) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function emailForRegisteredUser($value) {
        if (isset($this->_data['wp_user_id']) && is_numeric($this->_data['wp_user_id']) && ((int) $this->_data['wp_user_id']) > 0) {
            $userData = get_userdata($this->_data['wp_user_id']);
            if ($userData === false) {
                return false;
            }
            if ($value != $userData->user_email) {
                return sprintf($this->__->l('Correct email for the registered user is %s. You need to use this email, if you wish to assign this entry to the selected registered user'), $userData->user_email);
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function userIdForEmail($value) {
        if ((!isset($this->_data['wp_user_id']) || ((int) $this->_data['wp_user_id']) <= 0) && isset($this->_data['email']) && $this->_data['email'] != '') {
            $userData = get_user_by('email', $value);
            if ($userData === false) {
                return true;
            }
            if ($this->_data['email'] == $userData->user_email) {
                return sprintf($this->__->l('Entered email is registered for the user %s. Please set this user as Registered user or choose another email'), $userData->user_nicename);
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function uniqueUserEmail($value) {
        if ($value == '') {
            return true;
        }
        $event_id = $this->_isset($this->_data, 'event_id', false);
        if (!$event_id) {
            //on no event id, unique validation is not valid
            //because no registration possible if the event is not known.
            return false;
        }
        if (isset($this->_data['id']) && is_numeric($this->_data['id']) && ((int) $this->_data['id']) > 0) {
            return true;
        }

        //check this event.
        return !Eabi_Ipenelo_Calendar::service()->get('models/Registrant')->emailExists($value, $event_id);
    }

}