<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
/**
  Displays the Admin side Event edit form.

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

class Eabi_Ipenelo_Calendar_Form_Event extends Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;

    protected $_formName = 'ipenelo-calendar-event';

    public function render() {
        /**
         * 
         * @var Eabi_Ipenelo_Calendar_Helper_Validation
         */
        $validation = Eabi_Ipenelo_Calendar::service()->get('helpers/Validation');
        $html = '';
        if (!$this->_renderOnlyCore) {
            $html .= '<div class="wrap">';
            $html .= "\r\n";

            //title icon
            $html .= '<div class="icon32" id="icon-options-general"><br></div>';
            $html .= "\r\n";

            //title itself
            if ($this->_isset($this->_data, 'title')) {
                $html .= '<h2>' . $this->__->l('Edit event') . ':' . $this->_isset($this->_data, 'title', true) . '</h2>';
            } else {
                $html .= '<h2>' . $this->__->l('New Event') . '</h2>';
            }
            $html .= "\r\n";
            $html .= Eabi_Ipenelo_Calendar::displayErrors();
            $html .= Eabi_Ipenelo_Calendar::displayMessages();
            global $plugin_page;

            //form
            $html .= '<form action="' . admin_url('admin.php?noheader=true&page=' . $plugin_page . '&id=' . $this->_isset($this->_data, 'id')) . '" method="post" id="' . $this->_formName . '">';
            $html .= "\r\n";

            $html .= '<table class="form-table">';

            $html .= '<tbody>';
        }

        $this->addValidatorRule('title', array(&$validation, 'required'), $this->__->l('Title is required'), $validation->js_required());

        //title
        $this->addTextField('title', $this->__->l('Title'), $this->_isset($this->_data, 'title', true));
        //end title
        //url

        $this->addValidatorRule('url', array(&$validation, 'url'), $this->__->l('Invalid URL'), $validation->js_url());




        $this->addUrlField('url', $this->__->l('Url'), $this->_isset($this->_data, 'url', true));
        //end url
        //url_click_title
        $this->addCheckboxField('url_click_title', $this->__->l('Make event title clickable link'), $this->_isset($this->_data, 'url_click_title', true));

        //end url_click_title
        //is_full_day
        $this->addCheckboxField('is_full_day', $this->__->l('Full day event'), $this->_isset($this->_data, 'is_full_day', true));


        $helperText .= $this->__->l('Forces the Start and End times to be at 00:00 and 23:59 respectively');
        $this->addFormHelper('is_full_day', '<div class="float-left" style="">' . $helperText . '</div>', 'right');

        $useShortReadonly = false;
        if ($this->_isset($this->_data, 'is_full_day', true) == '1') {
            $useShortReadonly = true;
        }
        //there is some JS right at the end, which is tied to is_full_day
        //end_is_full_day
        //active_from

        $this->addValidatorRule('active_from', array(&$validation, 'required'), $this->__->l('Start date is required'));
        $this->addValidatorRule('active_from', array(&$validation, 'date'), $this->__->l('Start date is invalid'));

        $this->addValidatorJsRule('active_from_date', $validation->js_required());

        $this->addDateTimeField('active_from', $this->__->l('Starts'), $this->_isset($this->_data, 'active_from', true), $useShortReadonly);
        $formHelperTimeText = $this->__->l('It\'s possible to use arrow keys, page-up, page-down or mouse to adjust time.');
        $this->addFormHelper('active_from_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end active_from
        //active_to
        $this->addValidatorRule('active_to', array(&$validation, 'date'), $this->__->l('Ends date is invalid'));
        $this->addValidatorRule('active_to', array(&$validation, 'required'), $this->__->l('Ends date is required'));
        $this->addValidatorJsRule('active_to_date', $validation->js_required());
        $this->addValidatorJsRule('active_to_time', $this->js_activeToValid());
        $this->addValidatorRule('active_to', array(&$this, 'activeToValid'), $this->__->l('\'Starts\' date should be greater than \'Ends\' date'));

        $this->addDateTimeField('active_to', $this->__->l('Ends'), $this->_isset($this->_data, 'active_to', true), $useShortReadonly);
        $this->addFormHelper('active_to_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');
        //end active_to
        //description
//		$this->addValidatorRule('description', array(&$validation, 'required'), $this->__->l('Description is required'), $validation->js_required());

        $this->addTextareaField('description', $this->__->l('Description'), $this->_isset($this->_data, 'description', true));
        //end description
        //is_paid_event
        $this->addCheckboxField('is_paid_event', $this->__->l('This is paid event'), $this->_isset($this->_data, 'is_paid_event', true));

        //is_paid_event
        //cost
        $this->addValidatorRule('cost', array(&$this, 'costValid'), $this->__->l('Cost is required for the paid event'), $validation->js_required_cost());
        $this->addValidatorRule('cost', array(&$validation, 'currency'), $this->__->l('Cost price is invalid'), $validation->js_currency());
        $this->addCurrencyField('cost', $this->__->l('Cost'), $this->_isset($this->_data, 'cost', true));


        //end cost
        //max_registrants
        $this->addValidatorRule('max_registrants', array(&$validation, 'posInteger'), $this->__->l('Invalid Amount'), $validation->js_posInteger());
        $this->addNumberField('max_registrants', $this->__->l('Maximum registrants allowed'), $this->_isset($this->_data, 'max_registrants', true));
        $formHelperRegistrantText = $this->__->l('Leave this field to 0 to allow unlimited registrants.');
        $formHelperRegistrantText .= '<br/>' . $this->__->l('You can use arrow keys to change the value of this field');
        $this->addFormHelper('max_registrants', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="">' . $formHelperRegistrantText . '</div>');

        //end max_registrants
        //last_registration_allowed
        $this->addValidatorRule('last_registration_allowed', array(&$validation, 'date'), $this->__->l('Last registration date is invalid'));
        $this->addValidatorRule('last_registration_allowed', array(&$this, 'registrationLastsValid'), $this->__->l('Last registration date should be less than \'Ends\' date'));

        $this->addValidatorJsRule('last_registration_allowed_time', $this->js_registrationLastsValid());


        $this->addDateTimeField('last_registration_allowed', $this->__->l('Registration lasts until'), $this->_isset($this->_data, 'last_registration_allowed', true));
        $this->addFormHelper('last_registration_allowed_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end last_registration_allowed
        //visible_from
        $this->addValidatorJsRule('visible_from_date', $validation->js_required());
        $this->addValidatorJsRule('visible_from_time', $this->js_visibleFromValid());
        $this->addValidatorRule('visible_from', array(&$validation, 'required'), $this->__->l('Visible from date is required'));

        $this->addValidatorRule('visible_from', array(&$validation, 'date'), $this->__->l('Visible from date is invalid'));
        $this->addValidatorRule('visible_from', array(&$this, 'visibleFromValid'), $this->__->l('Visible from date should be less than Active from date'));

        $this->addDateTimeField('visible_from', $this->__->l('Visible from'), $this->_isset($this->_data, 'visible_from', true));
        $this->addFormHelper('visible_from_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end active_from
        //visible_to
        $this->addValidatorJsRule('visible_to_date', $validation->js_required());
        $this->addValidatorJsRule('visible_to_time', $this->js_visibleToValid());
        $this->addValidatorRule('visible_to', array(&$validation, 'required'), $this->__->l('Visible to date is required'));

        $this->addValidatorRule('visible_to', array(&$validation, 'date'), $this->__->l('Visible to date is invalid'));

        $this->addValidatorRule('visible_to', array(&$this, 'visibleToValid'), $this->__->l('Visible to date should be greater than \'Ends\' date'));

        $this->addDateTimeField('visible_to', $this->__->l('Visible to'), $this->_isset($this->_data, 'visible_to', true));
        $this->addFormHelper('visible_to_time', '<div class="float-left"><span class="arrow-keys">&nbsp;</span></div><div class="float-left" style="width: 150px;">' . $formHelperTimeText . '</div><div class="float-left"><span class="mouse-cursor">&nbsp;</span></div>');

        //end visible_to
        //background
        $this->addValidatorRule('background', array(&$validation, 'colorOrUrl'), $this->__->l('Invalid color or URL'), $validation->js_colorOrUrl());
        $this->addColorField('background', $this->__->l('Background'), $this->_isset($this->_data, 'background', true));

        $backgroundHelperText = $this->__->l('If you leave this field empty, background from the selected category will be used');
        $this->addFormHelper('background', '<div class="float-left">' . $backgroundHelperText . '</div>');


        //end background
        //author_id
        //only admin should be able to edit this field

        $this->addValidatorRule('author_id', array(&$validation, 'required'), $this->__->l('Author is required'), $validation->js_required());
        $this->addSelectField('author_id', $this->__->l('Event author'), $this->_isset($this->_data, 'author_id', true), Eabi_Ipenelo_Calendar_Model_Event::getUsers());

        //end author_id
        //status
        $statuses = Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'toStatusArray');
//        $statuses = Eabi_Ipenelo_Calendar_Model_Event::toStatusArray();
        $this->addValidatorRule('status', array(&$validation, 'required'), $this->__->l('Category is required'), $validation->js_required());

        $this->addSelectField('status', $this->__->l('Event status'), $this->_isset($this->_data, 'status', true), $statuses);

        //end status

        $categories = array('' => '');
        $categories += Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'getCategories');
//        $categories += Eabi_Ipenelo_Calendar_Model_Event::getCategories();

        $selectedCategory = $this->_isset($this->_data, 'main_category_id', true);
        if (!isset($this->_data['id']) && $selectedCategory == '') {
            $selectedCategory = Eabi_Ipenelo_Calendar::get('default_category', null);
        }

        $this->addValidatorRule('main_category_id', array(&$validation, 'required'), $this->__->l('Category is required'), $validation->js_required());
        $this->addSelectField('main_category_id', $this->__->l('Category'), $selectedCategory, $categories);

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


        if (!$this->_readOnly) {
            if ($this->_isset($this->_data, 'id', true) == '') {
                $this->_js[] = $this->_getDateinputsJs();
            }
            $this->_js[] = $this->_getCostsJs();
        }


        $html .= $this->collectJs();

        if (!$this->_readOnly) {
            $html .= $this->_addFullDayJs();

            if ($this->_isset($this->_data, 'is_full_day', true) == '1') {
                $html .= $this->_addFullDayJsClick();
            }
        }

        return $html;
    }

    public function registrationLastsValid($value) {
        if ($value == '') {
            return true;
        }
        $result = true;
        $activeTo = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $this->_data['active_to']);
        $activeFrom = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $this->_data['active_from']);
        if ($activeTo === false || $activeFrom === false) {
            return false;
        }
        $registrationLasts = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $value);
        return (int) $registrationLasts->format('U') <= (int) $activeTo->format('U');
    }

    public function js_registrationLastsValid() {
        $text = Eabi_Ipenelo_Calendar::escJs($this->__->l('Last registration date should be less than \'Ends\' date'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)),
	activeFromDateApi = jQuery("#active_from_date").data('dateinput'),
	activeToDateApi = jQuery("#active_to_date").data('dateinput'),
	registrationLastsDateApi = jQuery("#last_registration_allowed_date").data('dateinput'),

	activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
	activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
	activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
	activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput'),
	registrationLastsTimeApiH = jQuery("#last_registration_allowed_hh").data('rangeinput'),
	registrationLastsTimeApiM = jQuery("#last_registration_allowed_mm").data('rangeinput'),
	newDate,
	hour,
	minute
;	
	if (ret === true) {
		return ret;
	}
	newDate = activeToDateApi.getValue();
	newDate.setHours(activeToTimeApiH.getValue());
	newDate.setMinutes(activeToTimeApiM.getValue());
	hour = newDate.getTime();
	
	newDate = registrationLastsDateApi.getValue();
	newDate.setHours(registrationLastsTimeApiH.getValue());
	newDate.setMinutes(registrationLastsTimeApiM.getValue());
	minute = newDate.getTime();
	ret = minute <= hour;
	if (!ret) {
		return '{$text}';
	}
 
 return ret;
EOT;
    }

    public function visibleFromValid($value) {
        if ($value == '') {
            return true;
        }
        $result = true;
        $activeFrom = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $this->_data['active_from']);
        if ($activeFrom === false) {
            return false;
        }
        $visibleFrom = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $value);
        return (int) $visibleFrom->format('U') <= (int) $activeFrom->format('U');
    }

    public function costValid($value) {
        if ((!isset($this->_data['is_paid_event']) || $this->_data['is_paid_event'] == 0) && $value == '') {
            return true;
        }
        $value = str_replace(',', '', $value);
        if ($this->_data['is_paid_event'] == 1 && trim($value) == '') {
            return false;
        }
        if ($this->_data['is_paid_event'] == 1 && (float) $value <= 0) {
            return false;
        }
        return true;
    }

    public function js_visibleFromValid() {
        $text = Eabi_Ipenelo_Calendar::escJs($this->__->l('Visible from date should be less than Active from date'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)),
	activeFromDateApi = jQuery("#active_from_date").data('dateinput'),
	visibleFromDateApi = jQuery("#visible_from_date").data('dateinput'),

	activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
	activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
	visibleFromTimeApiH = jQuery("#visible_from_hh").data('rangeinput'),
	visibleFromTimeApiM = jQuery("#visible_from_mm").data('rangeinput'),
	newDate,
	hour,
	minute
;	
	if (ret === true) {
		return ret;
	}
	newDate = activeFromDateApi.getValue();
	newDate.setHours(activeFromTimeApiH.getValue());
	newDate.setMinutes(activeFromTimeApiM.getValue());
	hour = newDate.getTime();
	
	newDate = visibleFromDateApi.getValue();
	newDate.setHours(visibleFromTimeApiH.getValue());
	newDate.setMinutes(visibleFromTimeApiM.getValue());
	minute = newDate.getTime();
	ret = minute <= hour;
	if (!ret) {
		return '{$text}';
	}
 
 return ret;
EOT;
    }

    public function visibleToValid($value) {
        if ($value == '') {
            return true;
        }
        $result = true;
        $activeTo = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $this->_data['active_to']);
        if ($activeTo === false) {
            return false;
        }
        $visibleTo = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $value);
        return (int) $visibleTo->format('U') >= (int) $activeTo->format('U');
    }

    public function js_visibleToValid() {
        $text = Eabi_Ipenelo_Calendar::escJs($this->__->l('Visible to date should be greater than \'Ends\' date'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)),
	activeToDateApi = jQuery("#active_to_date").data('dateinput'),
	visibleToDateApi = jQuery("#visible_to_date").data('dateinput'),

	activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
	activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput'),
	visibleToTimeApiH = jQuery("#visible_to_hh").data('rangeinput'),
	visibleToTimeApiM = jQuery("#visible_to_mm").data('rangeinput'),
	newDate,
	hour,
	minute
;	
	if (ret === true) {
		return ret;
	}
	newDate = activeToDateApi.getValue();
	newDate.setHours(activeToTimeApiH.getValue());
	newDate.setMinutes(activeToTimeApiM.getValue());
	hour = newDate.getTime();
	
	newDate = visibleToDateApi.getValue();
	newDate.setHours(visibleToTimeApiH.getValue());
	newDate.setMinutes(visibleToTimeApiM.getValue());
	minute = newDate.getTime();
	ret = minute >= hour;
	if (!ret) {
		return '{$text}';
	}
 
 return ret;
EOT;
    }

    public function activeToValid($value) {
        if ($value == '') {
            return true;
        }
        $result = true;
        $activeFrom = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $this->_data['active_from']);
        if ($activeFrom === false) {
            return false;
        }
        $activeTo = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $value);
        return (int) $activeTo->format('U') > (int) $activeFrom->format('U');
    }

    public function js_activeToValid() {
        $text = Eabi_Ipenelo_Calendar::escJs($this->__->l('Active to date should be greater than Active from date'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)),
	activeFromDateApi = jQuery("#active_from_date").data('dateinput'),
	activeToDateApi = jQuery("#active_to_date").data('dateinput'),

	activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
	activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
	activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
	activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput'),
	newDate,
	hour,
	minute
;	
	if (ret === true) {
		return ret;
	}
	newDate = activeFromDateApi.getValue();
	newDate.setHours(activeFromTimeApiH.getValue());
	newDate.setMinutes(activeFromTimeApiM.getValue());
	hour = newDate.getTime();
	
	newDate = activeToDateApi.getValue();
	newDate.setHours(activeToTimeApiH.getValue());
	newDate.setMinutes(activeToTimeApiM.getValue());
	minute = newDate.getTime();
	ret = minute > hour;
	if (!ret) {
		return '{$text}';
	}
 
 return ret;
EOT;
    }

    private function _getDateinputsJs() {
        $eventLastsMinutes = (int) Eabi_Ipenelo_Calendar::get('event_lasts', 0);
        $registrationLastsMinutes = (int) Eabi_Ipenelo_Calendar::get('registration_lasts', 0);
        $jsDateFormat = Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat', true);

        $visibleFromMinutes = Eabi_Ipenelo_Calendar::get('visible_from', 'startoftime');
        $visibleToMinutes = Eabi_Ipenelo_Calendar::get('visible_to', 'endoftime');

        if ($visibleFromMinutes !== 'startoftime') {
            $visibleFromMinutes = (int) $visibleFromMinutes;
        }
        if ($visibleToMinutes !== 'endoftime') {
            $visibleToMinutes = (int) $visibleToMinutes;
        }




        $js = '';
        $js .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	jQuery("#active_from_date , #active_from_time").bind("change blur", function() {
		var activeToDateApi = jQuery("#active_to_date").data('dateinput'),
		activeFromDateApi = jQuery("#active_from_date").data('dateinput'),
		registrationLastsDateApi = jQuery("#last_registration_allowed_date").data('dateinput'),

		visibleFromDateApi = jQuery("#visible_from_date").data('dateinput'),
		visibleToDateApi = jQuery("#visible_to_date").data('dateinput'),

		visibleFromTimeApiH = jQuery("#visible_from_hh").data('rangeinput'),
		visibleFromTimeApiM = jQuery("#visible_from_mm").data('rangeinput'),

		visibleToTimeApiH = jQuery("#visible_to_hh").data('rangeinput'),
		visibleToTimeApiM = jQuery("#visible_to_mm").data('rangeinput'),


		registrationLastsTimeApiH = jQuery("#last_registration_allowed_hh").data('rangeinput'),
		registrationLastsTimeApiM = jQuery("#last_registration_allowed_mm").data('rangeinput'),


		activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
		activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
		activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
		activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput'),
		eventLastsMinutes = {$eventLastsMinutes},
		registrationLastsMinutes = {$registrationLastsMinutes},
		visibleFromMinutes = '{$visibleFromMinutes}',
		visibleToMinutes = '{$visibleToMinutes}',
		timestamp = 0,
		newDate = new Date(),
		hour = 0,
		minute = 0;
		;
		if (jQuery("#active_to_date").val() == '' || true) {
			activeToDateApi.setMin(activeFromDateApi.getValue(), true);
			activeToDateApi.setValue(activeFromDateApi.getValue());
			jQuery("#active_to_date").val(jQuery("#active_from_date").val());
		}
		if (jQuery("#active_to_time").val() == '' || true) {
			var newDate = new Date();
			newDate.setTime(activeFromDateApi.getValue().getTime());
			newDate.setHours(activeFromTimeApiH.getValue());
			newDate.setMinutes(activeFromTimeApiM.getValue());
			
			//now get the milliseconds
			timestamp = newDate.getTime();
			
			//add the new part
			timestamp += (eventLastsMinutes * 60000);
			
			newDate.setTime(timestamp);
			
			activeToDateApi.setValue(newDate);
			jQuery("#active_to_date").val(activeToDateApi.getValue("{$jsDateFormat}"));
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#active_to_time").val(hour + ':' + minute);

			activeToTimeApiH.setValue(newDate.getHours());
			activeToTimeApiM.setValue(newDate.getMinutes());
			
			//handle full day
			if (jQuery("#is_full_day").attr('checked') == 'checked') {
				activeToTimeApiH.setValue(23);
				activeToTimeApiM.setValue(59);
				jQuery("#active_to_time").val('23:59');
			}
			
		} else {
		}
		if (jQuery("#last_registration_allowed_date").val() == '' || true) {



			var newDate = new Date();
			newDate.setTime(activeFromDateApi.getValue().getTime());
			newDate.setHours(activeFromTimeApiH.getValue());
			newDate.setMinutes(activeFromTimeApiM.getValue());
			
			//now get the milliseconds
			timestamp = newDate.getTime();
			
			//add the new part
			timestamp -= (registrationLastsMinutes * 60000);
			
			newDate.setTime(timestamp);
			
			//detect the max value
			if (newDate.getTime() > activeToDateApi.getValue().getTime()) {
				newDate.setTime(activeToDateApi.getValue().getTime());
			}
			visibleToDateApi.setMin(activeToDateApi.getValue(), true);
			
			registrationLastsDateApi.setValue(newDate);
			jQuery("#last_registration_allowed_date").val(registrationLastsDateApi.getValue("{$jsDateFormat}"));
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#last_registration_allowed_time").val(hour + ':' + minute);

			registrationLastsTimeApiH.setValue(newDate.getHours());
			registrationLastsTimeApiM.setValue(newDate.getMinutes());
			registrationLastsDateApi.setMax(activeToDateApi.getValue());

			
		}

		if (jQuery("#visible_from_date").val() == '' || true) {
			visibleFromDateApi.setMax(activeFromDateApi.getValue(), true);
			newDate = new Date();
			
			
			//handle time
			if (visibleFromMinutes !== 'startoftime') {
				visibleFromMinutes = parseInt(visibleFromMinutes, 10);
				
				//load the time
				newDate = activeFromDateApi.getValue().getTime();
				newDate = new Date(newDate);
				newDate.setHours(activeFromTimeApiH.getValue());
				newDate.setMinutes(activeFromTimeApiM.getValue());
				
				newDate.setTime(newDate.getTime() - (visibleFromMinutes * 60000));
			}
			
			visibleFromTimeApiH.setValue(newDate.getHours());
			visibleFromTimeApiM.setValue(newDate.getMinutes());
			
			
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#visible_from_time").val(hour + ':' + minute);
			visibleFromDateApi.setValue(newDate);
			jQuery("#visible_from_date").val(visibleFromDateApi.getValue("{$jsDateFormat}"));
		}


		if (jQuery("#visible_to_date").val() == '' || true) {
			visibleToDateApi.setMin(activeToDateApi.getValue(), true);

			newDate = new Date(4294967295 * 1000);
			
			
			//handle time
			if (visibleToMinutes !== 'endoftime') {
				visibleToMinutes = parseInt(visibleToMinutes, 10);
				
				//load the time
				newDate = activeToDateApi.getValue().getTime();
				newDate = new Date(newDate);
				newDate.setHours(activeToTimeApiH.getValue());
				newDate.setMinutes(activeToTimeApiM.getValue());
				
				newDate.setTime(newDate.getTime() + (visibleToMinutes * 60000));
			}
			
			visibleToTimeApiH.setValue(newDate.getHours());
			visibleToTimeApiM.setValue(newDate.getMinutes());
			
			
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#visible_to_time").val(hour + ':' + minute);
			visibleToDateApi.setValue(newDate);
			jQuery("#visible_to_date").val(visibleToDateApi.getValue("{$jsDateFormat}"));


		}


	});
	jQuery("#active_to_date , #active_to_time , #last_registration_allowed_date , #last_registration_allowed_time , #visible_to_date, #visible_to_time , #visible_from_date , #visible_from_time").bind("blur", function() {
		var activeToDateApi = jQuery("#active_to_date").data('dateinput'),
		activeFromDateApi = jQuery("#active_from_date").data('dateinput'),
		registrationLastsDateApi = jQuery("#last_registration_allowed_date").data('dateinput'),

		visibleFromDateApi = jQuery("#visible_from_date").data('dateinput'),
		visibleToDateApi = jQuery("#visible_to_date").data('dateinput'),

		visibleFromTimeApiH = jQuery("#visible_from_hh").data('rangeinput'),
		visibleFromTimeApiM = jQuery("#visible_from_mm").data('rangeinput'),

		visibleToTimeApiH = jQuery("#visible_to_hh").data('rangeinput'),
		visibleToTimeApiM = jQuery("#visible_to_mm").data('rangeinput'),


		registrationLastsTimeApiH = jQuery("#last_registration_allowed_hh").data('rangeinput'),
		registrationLastsTimeApiM = jQuery("#last_registration_allowed_mm").data('rangeinput'),


		activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
		activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
		activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
		activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput'),
		eventLastsMinutes = {$eventLastsMinutes},
		registrationLastsMinutes = {$registrationLastsMinutes},
		visibleToMinutes = '{$visibleToMinutes}',
		timestamp = 0,
		newDate = new Date(),
		otherDate = null,
		hour = 0,
		minute = 0;
		;


		if (jQuery("#last_registration_allowed_date").val() == '' || true) {

			newDate = activeToDateApi.getValue();
			newDate.setHours(activeToTimeApiH.getValue());
			newDate.setMinutes(activeToTimeApiM.getValue());
			activeToDateApi.setValue(newDate);
			visibleToDateApi.setMin(newDate, true);


			newDate = registrationLastsDateApi.getValue();
			newDate.setHours(registrationLastsTimeApiH.getValue());
			newDate.setMinutes(registrationLastsTimeApiM.getValue());
			
			//detect the max value
			if (newDate.getTime() > activeToDateApi.getValue().getTime()) {
				newDate.setTime(activeToDateApi.getValue().getTime());
			}
			
			registrationLastsDateApi.setValue(newDate);
			jQuery("#last_registration_allowed_date").val(registrationLastsDateApi.getValue("{$jsDateFormat}"));
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#last_registration_allowed_time").val(hour + ':' + minute);

			registrationLastsTimeApiH.setValue(newDate.getHours());
			registrationLastsTimeApiM.setValue(newDate.getMinutes());
			registrationLastsDateApi.setMax(activeToDateApi.getValue());
			
			//detect visible_to
			newDate = activeToDateApi.getValue();
			newDate.setHours(activeToTimeApiH.getValue());
			newDate.setMinutes(activeToTimeApiM.getValue());
			hour = newDate.getTime();

			otherDate = visibleToDateApi.getValue();
			otherDate.setHours(visibleToTimeApiH.getValue());
			otherDate.setMinutes(visibleToTimeApiM.getValue());
			minute = otherDate.getTime();


			if (minute < hour) {
				visibleToDateApi.setValue(new Date(hour));
				jQuery("#visible_to_date").val(visibleToDateApi.getValue("{$jsDateFormat}"));
				visibleToDateApi.setMin(new Date(hour));

				hour = activeToTimeApiH.getValue();
				minute = activeToTimeApiM.getValue();
				visibleToTimeApiH.setValue(hour);
				visibleToTimeApiM.setValue(minute);
				
				if (hour < 10) {
					hour = '0' + hour;
				}
				if (minute < 10) {
					minute = '0' + minute;
				}
				jQuery("#visible_to_time").val(hour + ':' + minute);

				
			} else {
			}
			
			//detect visible from

			newDate = activeFromDateApi.getValue();
			newDate.setHours(activeFromTimeApiH.getValue());
			newDate.setMinutes(activeFromTimeApiM.getValue());
			hour = newDate.getTime();

			otherDate = visibleFromDateApi.getValue();
			otherDate.setHours(visibleFromTimeApiH.getValue());
			otherDate.setMinutes(visibleFromTimeApiM.getValue());
			minute = otherDate.getTime();


			if (minute > hour) {
//				jQuery("#visible_from_date").val(visibleFromDateApi.getValue("{$jsDateFormat}"));

				visibleFromDateApi.setMax(new Date(hour));
				visibleFromDateApi.setValue(new Date(hour));

				hour = activeFromTimeApiH.getValue();
				minute = activeFromTimeApiM.getValue();
				visibleFromTimeApiH.setValue(hour);
				visibleFromTimeApiM.setValue(minute);
				
				if (hour < 10) {
					hour = '0' + hour;
				}
				if (minute < 10) {
					minute = '0' + minute;
				}
				jQuery("#visible_from_time").val(hour + ':' + minute);

				
			} else {
			}

		if (jQuery("#visible_to_date").val() == '' || true) {
			visibleToDateApi.setMin(activeToDateApi.getValue(), true);

			newDate = new Date(4294967295 * 1000);
			
			
			//handle time
			if (visibleToMinutes !== 'endoftime') {
				visibleToMinutes = parseInt(visibleToMinutes, 10);
				
				//load the time
				newDate = activeToDateApi.getValue().getTime();
				newDate = new Date(newDate);
				newDate.setHours(activeToTimeApiH.getValue());
				newDate.setMinutes(activeToTimeApiM.getValue());
				
				newDate.setTime(newDate.getTime() + (visibleToMinutes * 60000));
			}
			
			visibleToTimeApiH.setValue(newDate.getHours());
			visibleToTimeApiM.setValue(newDate.getMinutes());
			
			
			hour = newDate.getHours();
			minute = newDate.getMinutes();
			if (hour < 10) {
				hour = '0' + hour;
			}
			if (minute < 10) {
				minute = '0' + minute;
			}
			jQuery("#visible_to_time").val(hour + ':' + minute);
			visibleToDateApi.setValue(newDate);
			jQuery("#visible_to_date").val(visibleToDateApi.getValue("{$jsDateFormat}"));


		}

			
		}

	});

	
});
/* ]]> */
</script>
EOT;
        return $js;
    }

    private function _getCostsJs() {
        $js = '';
        $formName = $this->_formName;
        $js .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	if (jQuery("#is_paid_event").attr('checked') == 'checked') {
		jQuery("#cost").removeAttr('disabled');
		jQuery("#cost").parents('tr').show();
	} else {
		jQuery("#cost").attr('disabled', 'disabled');
		jQuery("#cost").parents('tr').hide();
	}
	jQuery("#is_paid_event").bind("was_checked", function(e) {
		jQuery("#cost").removeAttr('disabled');
		jQuery("#cost").parents('tr').show();
	});
	jQuery("#is_paid_event").bind("was_unchecked", function(e) {

		jQuery("#cost").attr('disabled', 'disabled');
		jQuery("#cost").parents('tr').hide();
		jQuery("#{$formName}").data('validator').reset(jQuery("#cost"));
	});

	});
/* ]]> */
</script>
EOT;
        return $js;
    }

    private function _addFullDayJs() {
        $js = '';
        $js .= <<<EOT
		
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	var activeFromTimeKeydown, activeToTimeKeydown;
		jQuery("#is_full_day").bind('was_checked', function() {
			var 
				activeFromTimeApiH = jQuery("#active_from_hh").data('rangeinput'),
				activeFromTimeApiM = jQuery("#active_from_mm").data('rangeinput'),
				activeToTimeApiH = jQuery("#active_to_hh").data('rangeinput'),
				activeToTimeApiM = jQuery("#active_to_mm").data('rangeinput')
			;


			activeFromTimeApiH.setValue(0);
			activeFromTimeApiM.setValue(0);
			jQuery("#active_from_time").val('00:00');
			jQuery("#active_from_time").blur();
			jQuery("#active_from_time").attr('readonly', 'disabled');
			jQuery("#active_from_hh").attr('disabled', 'disabled');

			activeToTimeApiH.setValue(23);
			activeToTimeApiM.setValue(59);
			jQuery("#active_to_time").val('23:59');
			jQuery("#active_to_time").blur();
			jQuery("#active_to_time").attr('readonly', 'disabled');
			jQuery("#active_to_hh").attr('disabled', 'disabled');
			activeFromTimeKeydown = jQuery('#active_from_time').data('events')['keydown'][0]['handler'];
			activeToTimeKeydown = jQuery('#active_to_time').data('events')['keydown'][0]['handler'];
			
			jQuery('#active_from_time').unbind('keydown');
			jQuery('#active_to_time').unbind('keydown');

		});
		jQuery("#is_full_day").bind('was_unchecked', function() {
			jQuery("#active_from_time").removeAttr('readonly');
			jQuery("#active_from_hh").removeAttr('disabled');
			jQuery("#active_to_time").removeAttr('readonly');
			jQuery("#active_to_hh").removeAttr('disabled');

			jQuery('#active_from_time').keydown(activeFromTimeKeydown);
			jQuery('#active_to_time').keydown(activeToTimeKeydown);

			
		});

	});
/* ]]> */
</script>

EOT;
//		$this->_js[] = $js;
        return $js;
    }

    private function _addFullDayJsClick() {
        $js = '';
        $js .= <<<EOT
		
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	jQuery("#is_full_day").trigger('was_checked');

	});
/* ]]> */
</script>

EOT;
//		$this->_js[] = $js;
        return $js;
    }

}