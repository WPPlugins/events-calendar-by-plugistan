<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
/**
  Displays the Admin side Registrant edit form.

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Registrant');
Eabi_Ipenelo_Calendar::service()->import('forms/Event');

class Eabi_Ipenelo_Calendar_Form_PublicRegistrant extends Eabi_Ipenelo_Calendar_Form_Registrant {

    const __CLASS = __CLASS__;

    protected $_formName = 'ipenelo-calendar-registrant';
//	protected $_renderOnlyCore = true;
    protected $_eventForm;
    protected $_disableFields;
    protected $_submitFormId = 'ipenelo-calendar';

    public function setSubmitFormId($formId) {
        $this->_submitFormId = $formId;
        return $this;
    }

    public function getSubmitFormId() {
        return $this->_submitFormId;
    }

    /**
      @param $data associative array of database row instance

     */
    public function __construct(array $data, Eabi_Ipenelo_Calendar_Form_Event $eventForm) {
        if ($this->__ == null) {
            //TODO this kind of translator loading should be removed from the constructor.
            //it should be injected after the constructor
            //move out the fields, which need to be translated
            $this->__ = Eabi_Ipenelo_Calendar::service()->get('translator');
        }
        $this->_eventForm = $eventForm;
        $this->_eventForm->setReadOnly(true);
        $this->_eventForm->setRenderOnlyCore(true);
        $this->_eventForm->addRestrictedOutput('is_full_day');
        $this->_eventForm->addRestrictedOutput('visible_from');
        $this->_eventForm->addRestrictedOutput('visible_to');
        $this->_eventForm->addRestrictedOutput('is_paid_event');
        $this->_eventForm->addRestrictedOutput('author_id');
        $this->_eventForm->addRestrictedOutput('main_category_id');
        $this->_eventForm->addRestrictedOutput('max_registrants');
        $this->_eventForm->addRestrictedOutput('url_click_title');
        if (!isset($data['id'])) {
            $this->_eventForm->addRestrictedOutput('status');
        }

        $eventModel = $this->_eventForm->getModel();
        if (isset($eventModel['status']) && in_array(
                        $eventModel['status'], array(
                    Eabi_Ipenelo_Calendar_Model_Event::STATUS_DISABLED,
                    Eabi_Ipenelo_Calendar_Model_Event::STATUS_SOLD_OUT,
                    Eabi_Ipenelo_Calendar_Model_Event::STATUS_ENDED,
                        )
                )
                && !isset($data['id'])) {
            $this->_disableFields = true;
            $this->setReadOnly(true);
        }

        if (Eabi_Ipenelo_Calendar::get('disable_registration')) {
            $this->_disableFields = true;
            $this->setReadOnly(true);
            $this->_eventForm->addRestrictedOutput('last_registration_allowed');
            $this->_eventForm->addRestrictedOutput('free_spots');
            $this->_eventForm->addRestrictedOutput('event_state');
        }
        /**
          Disable registration if:
          event sold out
          last_registration_time has passed
          active_to time has passed

         */
        $event = Eabi_Ipenelo_Calendar::service()->get('models/Event');

        $isOver = $event->isOver($eventModel);
        $freeSpots = $event->getFreeSpots($eventModel);

        $isStarted = $event->isStarted($eventModel);
        $isEnded = $event->isEnded($eventModel);

        if (Eabi_Ipenelo_Calendar::get('show_free_spots')) {
            $freeSpotsText = $freeSpots;
            if ($freeSpotsText === false) {
                $freeSpotsText = $this->__->l('No limit');
            }
            $this->_eventForm->setFormElementHtml(', ${LABEL}: ${INPUT}', 'free_spots');
            $this->_eventForm->addInfoField('free_spots', $this->__->l('Free spots'), $freeSpotsText);
        }
        $stateText = $this->__->l('Open for registration');
        $this->_eventForm->setFormElementHtml('<div class="free_spots">${INPUT}', 'event_state');

        if (!isset($data['id']) && ($isOver || $freeSpots === 0)) {
            $this->_disableFields = true;
            $this->setReadOnly(true);
        }
        if ($freeSpots === 0) {
            $stateText = $this->__->l('Sold out');
        }
        if ($isOver) {
            $stateText = $this->__->l('Registration ended');
        }
        if ($isStarted && $isOver) {
            $stateText = $this->__->l('Event started');
        }
        if ($isEnded) {
            $stateText = $this->__->l('Expired');
        }

        if (!is_user_logged_in() && (Eabi_Ipenelo_Calendar::get('log_to_register') || Eabi_Ipenelo_Calendar::get('log_to_view'))) {
            $this->_disableFields = true;
            $this->setReadOnly(true);
            $loginUrl = wp_login_url();
            Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('Please <a href="%s">Log in</a>'), $loginUrl));
            if (Eabi_Ipenelo_Calendar::get('log_to_view')) {

                $this->_eventForm->addRestrictedOutput('background');
                $this->_eventForm->addRestrictedOutput('title');
                $this->_eventForm->addRestrictedOutput('url');
                $this->_eventForm->addRestrictedOutput('active_from');
                $this->_eventForm->addRestrictedOutput('active_to');
                $this->_eventForm->addRestrictedOutput('cost');
                $this->_eventForm->addRestrictedOutput('description');
                $this->_eventForm->addRestrictedOutput('last_registration_allowed');
                $this->_eventForm->addRestrictedOutput('free_spots');
                $this->_eventForm->addRestrictedOutput('event_state');

                $this->_eventForm->setModel(array());
            }
        }

        $this->_eventForm->addInfoField('event_state', $this->__->l('Status'), $stateText);

        parent::__construct($data);
    }

    private $_displayPayment = false;

    public function setDisplayPayment($displayPayment) {
        $this->_displayPayment = (bool) $displayPayment;
    }

    public function render() {
        $validation = Eabi_Ipenelo_Calendar::service()->get('helpers/Validation');
        $html = '';

        if (!$this->_renderOnlyCore) {
            $html .= '<div class="ipenelo-calendar-registrant-form-wrapper"><div class="ipenelo-calendar-registrant-form">';
            $html .= "\r\n";

            //title icon
            $html .= "\r\n";

            //title itself
            if (!$this->_disableFields) {
                if ($this->_isset($this->_data, 'id', true)) {
                    $html .= '<h2>' . $this->__->l('Your registration info') . '</h2>';
                } else {
                    $html .= '<h2>' . $this->__->l('Register') . '</h2>';
                }
            }
            $html .= Eabi_Ipenelo_Calendar::displayErrors();
            $html .= Eabi_Ipenelo_Calendar::displayMessages();

            //form
            if (!$this->_readOnly) {
                if ($this->_submitFormId == 'ipenelo-calendar') {

                    $html .= '<form action="javascript:(function() { var pmInfo = jQuery(\'#TB_ajaxContent #payment-extra-info input , #TB_ajaxContent #payment-extra-info select , #TB_ajaxContent #payment-extra-info textarea\').serializeArray(); jQuery(\'#TB_ajaxContent #payment-extra-info div\').html(\'\'); jQuery(\'#TB_ajaxContent #payment-extra-info\').hide();  eabi_ipenelo_calendar_submitData(' . $this->_isset($this->_data, 'event_id', true) . ', jQuery(\'#' . $this->_formName . '\').serializeArray(), pmInfo); return;})();" method="post" id="' . $this->_formName . '">';
                } else {
                    $html .= '<form action="javascript:(function() { var pmInfo = jQuery(\'#TB_ajaxContent #payment-extra-info input , #TB_ajaxContent #payment-extra-info select , #TB_ajaxContent #payment-extra-info textarea\').serializeArray(); jQuery(\'#TB_ajaxContent #payment-extra-info div\').html(\'\'); jQuery(\'#TB_ajaxContent #payment-extra-info\').hide();  jQuery(\'#' . $this->_submitFormId . '\').data(\'dateinput\').getConf().submitData(' . $this->_isset($this->_data, 'event_id', true) . ', jQuery(\'#' . $this->_formName . '\').serializeArray(), pmInfo); return;})();" method="post" id="' . $this->_formName . '">';
                }
                $html .= "\r\n";
            }

            $html .= '<table class="form-table">';

            $html .= '<tbody>';
        }

        $model = $this->_eventForm->getModel();
        $availablePayments = array();
        if (!$this->_disableFields) {
            //first_name
            $this->addTextField('first_name', $this->__->l('First name'), $this->_isset($this->_data, 'first_name', true));
            //end first_name
            //last_name
            $this->addTextField('last_name', $this->__->l('Last name'), $this->_isset($this->_data, 'last_name', true));
            //end last_name
            //wp_user_id should be current user
            //email
            $this->addTextField('email', $this->__->l('E-mail'), $this->_isset($this->_data, 'email', true));
            $this->addValidatorRule('email', array(&$validation, 'required'), $this->__->l('Email is required'), $validation->js_required());
            if (!isset($this->_data['id'])) {
                $email = '';
                $currentUser = wp_get_current_user();
                if ($currentUser->ID > 0) {
                    $email = $currentUser->user_email;
                }
                $this->addValidatorRule('email', array(&$this, 'userNotLoggedIn'), $this->__->l('You have an account with us, please log in'));
                $this->addValidatorRule('email', array(&$this, 'uniqueUserEmail'), $this->__->l('Supplied E-mail has already been used to register for this event'));
                $this->addValidatorRule('email', array(&$this, 'loggedInEmailKeep'), sprintf($this->__->l('You can only register with your email: %s'), $email));
            }
            //end email
            //payment method
            $availablePayments = array();
            if ($model['is_paid_event'] == '1') {
                $paymentMethods = array(
                    '' => '',
                );
                $availablePayments = @unserialize(Eabi_Ipenelo_Calendar::get('available_payments'));
                if (!is_array($availablePayments)) {
                    $availablePayments = array();
                }
                foreach ($availablePayments as $code => $availablePayment) {
                    if ($availablePayment['enabled']) {
                        $paymentMethods[$code] = $availablePayment['title'];
                    }
                }


                $this->addSelectField('payment_method', $this->__->l('Payment method'), $this->_isset($this->_data, 'payment_method', true), $paymentMethods);
                $this->addValidatorRule('payment_method', array(&$validation, 'required'), $this->__->l('Payment method is required'), $validation->js_required());


                //todo add the payment collector form
                $this->setFormElementHtml('<tr id="payment-extra-info" style="display:none;"><td colspan="2"><div>${INPUT}</div></td></tr>', 'payment_extra_info');
                $this->addInfoField('payment_extra_info', $this->__->l('Extra payment info'), '');

                $this->_js[] = <<<EOT
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready(function() {
		jQuery('#payment_method').change(function(event) {
			var val = jQuery(this).val(),
			hasExtraInfo = jQuery('#payment-extra-ipenelo-' + jQuery(this).val()).length > 0;
			if (hasExtraInfo) {
				jQuery('#payment-extra-info div').html(jQuery('#payment-extra-ipenelo-' + jQuery(this).val()).html());
				jQuery('#payment-extra-info').show();
			} else {
				jQuery('#payment-extra-info div').html('');
				jQuery('#payment-extra-info').hide();
			}
		});
		jQuery('#payment_method').change();
	});
	/* ]]> */
	</script>
EOT;
            }


            //end_payment method
            //status should be determined
            $statuses = Eabi_Ipenelo_Calendar::service()->getStatic('models/Registrant', 'toStatusArray');

            //registration_date should be automatic
            //payment_date should be automatic


            if ($this->_isset($this->_data, 'id', true)) {
                $this->addSelectField('status', $this->__->l('Registration status'), $this->_isset($this->_data, 'status', true), $statuses);
            }






            $html .= $this->_render();
        }

        if (!$this->_renderOnlyCore) {

            $html .= '</tbody>';

            $html .= '</table>';

            //submit button
            //if registration was successful, event needs to be payd, then you should put the start payment button here......


            if (!$this->_readOnly) {
                $html .= '<p class="submit">';
                $html .= '<input type="submit" value="' . $this->__->l('Register') . '" class="button-primary ipenelo-register" id="submi" name="submi">';
                $html .= '</p>';
                $html .= '</form>';
            }
            if ($this->_readOnly) {
                $html .= '<p class="submit">';
                $url = admin_url("admin-ajax.php?action=ipenelo_calendar_start_payment&registrant_id=" . $this->_isset($this->_data, 'id', true));
                if ($model['is_paid_event'] == '1'
                        && ($this->_displayPayment
                        || $this->_isset($this->_data, 'status', true) == Eabi_Ipenelo_Calendar_Model_Registrant::STATUS_PENDING)) {
                    $html .= '<a class="ipenelo-register" onclick="window.location.href=\'' . addslashes($url) . '\'; return false;">' . $this->__->l('Start payment') . '</a>';
                } else {
                    $html .= '<a class="ipenelo-register" onclick="tb_remove(); return false;">' . $this->__->l('Close window') . '</a>';
                }

                $html .= '</p>';
            }

            if ($model['is_paid_event'] == '1') {
                $dummyRegistrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                $selectedPayment = $this->_isset($this->_data, 'payment_method', true);
                foreach ($availablePayments as $code => $availablePayment) {
                    if ($availablePayment['enabled'] && $availablePayment['has_extra']) {

                        $loadedMethod = Eabi_Ipenelo_Calendar::service()->get('payment')->getByCode($availablePayment['code'], $dummyRegistrant);
                        if (!is_object($loadedMethod)) {
                            continue;
                        }
                        $html .= '<div id="payment-extra-ipenelo-' . $availablePayment['code'] . '" class="ipenelo_hidden">';

                        $html .= '<table class="form-table">';
                        if ($availablePayment['code'] == $selectedPayment) {

                            $html .= $loadedMethod->renderInfoCollectorForm(@unserialize($this->_isset($this->_data, 'payment_data')));
                        } else {
                            $html .= $loadedMethod->renderInfoCollectorForm();
                        }
                        $html .= '</table>';

                        $html .= '</div>';
                    }
                }
            }


            $html .= "\r\n";

            //close div.wrap
            $html .= '</div>';
            $html .= "\r\n";
        }


        $html .= $this->collectJs();

        $html .= '<div class="ipenelo-calendar-event">';


        //set the templates
        $this->_eventForm->setFormElementHtml('<div class="row">${INPUT}</div>');
        $this->_eventForm->setFormElementHtml('<div class="row"><div class="background">${INPUT}</div>', 'background');

        if (isset($model['url_click_title']) && trim($model['url_click_title']) == '1') {
            $this->_eventForm->setFormElementHtml('<div class="title"><h2><a href="' . htmlspecialchars($model['url']) . '">${INPUT}</a></h2></div></div>', 'title');
        } else {
            $this->_eventForm->setFormElementHtml('<div class="title"><h2>${INPUT}</h2></div></div>', 'title');
        }

        if (isset($model['url']) && trim($model['url']) == '') {
            $this->_eventForm->addRestrictedOutput('url');
        }

        $this->_eventForm->setFormElementHtml('<div class="row"><div class="url">${INPUT}</div></div>', 'url');

        $durationText = $this->__->l('Date');

        $this->_eventForm->setFormElementHtml('<div class="row"><div class="active_from"><b>' . $durationText . ':</b> ${INPUT} ', 'active_from');
        if (Eabi_Ipenelo_Calendar::get('disable_registration')) {
            //do not show cost, when registration is disabled
            $this->_eventForm->setFormElementHtml('- ${INPUT}', 'active_to');
            $this->_eventForm->setFormElementHtml('', 'cost');
        } else {
            $this->_eventForm->setFormElementHtml('- ${INPUT}</div>', 'active_to');
            $this->_eventForm->setFormElementHtml('<div class="cost">${INPUT}</div></div>', 'cost');
        }


        $this->_eventForm->setFormElementHtml('</div></div><div class="row"><div class="description">${INPUT}</div></div>', 'description');
        $this->_eventForm->setFormElementHtml('', 'status');

        $bookingToText = $this->__->l('Booking to');
        if (Eabi_Ipenelo_Calendar::get('disable_registration')) {
            
        } else {
            
        }
        $this->_eventForm->setFormElementHtml('<div class="row"><div class="last_registration_allowed"><b>' . $bookingToText . ':</b> ${INPUT}</div>', 'last_registration_allowed');




        $this->_eventForm->addFieldOrder('background');
        $this->_eventForm->addFieldOrder('title');
        $this->_eventForm->addFieldOrder('url');
        $this->_eventForm->addFieldOrder('active_from');
        $this->_eventForm->addFieldOrder('active_to');
        $this->_eventForm->addFieldOrder('cost');
        $this->_eventForm->addFieldOrder('last_registration_allowed');
        $this->_eventForm->addFieldOrder('event_state');
        $this->_eventForm->addFieldOrder('free_spots');
        $this->_eventForm->addFieldOrder('description');

        //set the background if not set already

        if ($model['background'] == '') {
            $category = Eabi_Ipenelo_Calendar::service()->get('models/Category');
            $category->load($model['main_category_id']);
            $model['background'] = $category->background;
            $this->_eventForm->setModel($model);
        }


        $html .= $this->_eventForm->render();

        $html .= '</div></div>';
        return $html;
    }

    public function toDb() {
        if ($this->_readOnly) {
            return $this->_data;
        }
        $result = array();
        if (count($_POST) > 0 && isset($_POST['register'])) {
            foreach ($this->_db as $key => $value) {
                if ($value === false) {
                    $result[$key] = isset($_POST[$key]) ? $_POST[$key] : null;
                } else if (is_string($value)) {
                    $result[$key] = $this->$value($key);
                }
            }
            if (isset($_POST['payment_data'])) {
                $data = @json_decode(stripslashes($_POST['payment_data']));
                if (is_array($data) || is_object($data)) {
                    $result['payment_data'] = serialize((array) $data);
                }
            }
            return $result;
        }
        return false;
    }

    public function validate($transformedRequest) {
        $finalResult = parent::validate($transformedRequest);
        if (!$this->_readOnly && Eabi_Ipenelo_Calendar::get('disable_registration')) {
            $finalResult[] = $this->__->l('Registration is disabled');
        }
        if (isset($transformedRequest['payment_data']) && is_array(@unserialize($transformedRequest['payment_data']))
                && $transformedRequest['payment_method'] != '') {
            $dummyRegistrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
            $payment_data = @unserialize($transformedRequest['payment_data']);


            
            /**
             *  @var Eabi_Ipenelo_Calendar_Payment_Abstract
             */
            $extraPaymentForm = Eabi_Ipenelo_Calendar::service()->get('payment')->getByCode($transformedRequest['payment_method'], $dummyRegistrant);
            if (is_object($extraPaymentForm)) {
                $subValidationErrors = $extraPaymentForm->validateRenderInfoCollectorForm($payment_data);
                foreach ($subValidationErrors as $error) {
                    $finalResult[] = $error;
                }
            }
            return $finalResult;
        }

        $finalResult = array();
        foreach ($this->_validatorRules as $field_name => $rules) {
            $value = isset($transformedRequest[$field_name]) ? $transformedRequest[$field_name] : '';
            foreach ($rules as $rule) {
                $result = call_user_func($rule['validationFunction'], $value);
                if ($result !== true) {
                    $finalResult[] = $rule['message'];
                    break;
                }
            }
        }
        return $finalResult;
    }

}