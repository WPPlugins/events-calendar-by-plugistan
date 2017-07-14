<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */

/**

  Base class for the HTML forms.
  Contains methods to achieve the following
 * Generate html inputs
 * text
 * textarea
 * checkbox
 * select
 * datetime
 * url
 * positive integer
 * currency
 * Validate the POST data against the form
 * Fill form values from the database row entry
 * Create Javascript validation rules
 * Add Javascript actions related to the form.

  All the subclasses of the form should implement the render() method
  and define the $_formName variable, which is the html id for the form element.


 */
abstract class Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;
    protected $_dbi;

    /**

      Holds the database object instance.
      Represents single row of table.
     */
    protected $_data = array();

    /**
      Holds the Javascript snippets.
      Those snippets should be provided in raw form.
     */
    protected $_js = array();

    /**
      Holds the information how to convert incoming POST data to corresponding DB entry.
      format:
      array[field_name] = local_conversionfunction_name

      if no conversion required, then local_conversion_function_name should be (bool) false

     */
    protected $_db = array();

    /**
      Holds the information about the HTML snippets.
      Each element represents html snippet for each form element.

     */
    protected $_htmls = array();

    /**
      Holds the information on how the form should be validated.
      Validation Chain will be run on validate() method.
      format:
      array[field_name] = array of arrays
      where each element is in format:
      array(
      validationFuntion => validationFunctioncallback
      message => message if the validation fails
      js => javascript validation string, can be null
      )

      If javascript validation string is provided, then $_validatorJsRules will be filled.
      @see Eabi_Ipenelo_Calendar_Helper_Validation

     */
    protected $_validatorRules = array();

    /**
      Holds the information about the ValidationRules, which are placed on the frontend as Javascript.
      format:
      array[field_name] = array of arrays
      where each element is in format:
      array(
      javascript string
      )
      javascript string should be body of the function and should return true
      if the validation passed and errormessage string if the validation failed.

      @see Eabi_Ipenelo_Calendar_Helper_Validation

     */
    protected $_validatorJsRules = array();

    /**
      id tag for the form element. Mandatory.

     */
    protected $_formName = '';

    /**

      Html template to generate single form input.
      ${LABEL} should contain the <label>label</label> stucture
      and ${INPUT} should contain all the input.

     */
    protected $_formElementHtml = '<tr valign="top"><th scope="row">${LABEL}</th><td>${INPUT}</td></tr>';

    /**
      Holds the definitions of each form element html template.
      If the template is defined, this it is used.
      If not defined, then default template is used.
      format (array[field_name] = html template)
     */
    protected $_formElementHtmls = array();

    /**

      If this value is set to true, then only field value is rendered instead of actual input.

     */
    protected $_readOnly = false;

    /**
      If set to true, then only the core form elements are rendered.
      If set to false, then form with the header and footer is rendered.

     */
    protected $_renderOnlyCore = false;

    /**
      Holds the information about the labels.

     */
    protected $_labels = array();

    /**
      Indexed array of the field_names.
      If this field contains the field_name then the corresponding field_name
      will not be rendered and no validation logic for the corresponding field_name
      will not be added.

      This is static variable, which means once set, takes effect in all the forms
      created afterwards.

     */
    protected static $_restrictedOutputs = array();

    /**

      Holds the array in a form
      array[__CLASS__][field_name] = field_name

      Iterates all the fields in the order this variable was set
      And the fields which are not defined in this variable, will be rendered after the original
      ordering has been complete.

      This is static variable, which means once set, takes effect in all the forms of same class
      created afterwards.


     */
    protected static $_fieldOrders = array();

    /**
      Holds the fiedls which are required, in order to create red asterisk next to label.
      this field is populated automatically by the addValidatorRule call.

      sturcture
      array[field_name] = $field_name

     */
    protected $_requiredFields = array();

    /**
      When user has focus on some of the inputs, then tooltip will be displayed with the text
      Structure is:
      array[field_name] = array(
      'position' => [left,right,top,bottom],
      'text' => text
      )


     */
    protected $_formHelpers = array();

    /**
      Generates input type=hidden name=_wp_nonce field if set to true.

     */
    protected $_nonceEnabled = true;

    /**
      @param $data associative array of database row instance

     */
    public function __construct(array $data) {
        $this->_data = $data;
    }

    public function setFormName($formName) {
        $this->_formName = $formName;
        return $this;
    }
    
    public function setDb($db) {
        $this->_dbi = &$db;
    }
            

    /**
      Replaces the form contents with another row from Database.
      @param $data associative array of database row instance

     */
    public function setModel(array $data) {
        $this->_data = $data;
        return $this;
    }

    public function getModel() {
        return $this->_data;
    }

    /**
      Sets the formElementHtml Template.
      @param $formElementHtml Should be raw HTML template with two variables:
      ${INPUT} - will be replaced with actual input html
      ${LABEL} - will be replaced with actual label html

      @param $field_name - if set, then will set the html template for the specific form field.

      This function should be called before the contents of the render() method are returned.

     */
    public function setFormElementHtml($formElementHtml, $field_name = null) {
        if ($field_name != null) {
            $this->_formElementHtmls[$field_name] = $formElementHtml;
            return $this;
        }
        $this->_formElementHtml = $formElementHtml;
        return $this;
    }

    /**
      Clears special forms html templates and retains the default one.

     */
    public function clearSpecialFormElementHtmls() {
        $this->_formElementHtmls = array();
    }

    protected function getFormElementHtml($field_name) {
        if (isset($this->_formElementHtmls[$field_name])) {
            return $this->_formElementHtmls[$field_name];
        }
        return $this->_formElementHtml;
    }

    /**

      Disables Nonce field generation

     */
    public function disableNonce() {
        $this->_nonceEnabled = false;
        return $this;
    }

    /**

      Enables Nonce field generation

     */
    public function enableNonce() {
        $this->_nonceEnabled = true;
        return $this;
    }

    /**
      Sets the form readonly property.
      @param $readOnly If set to true, then the form renders only field value between <span id=field_name> tags.

     */
    public function setReadOnly($readOnly) {
        $this->_readOnly = (bool) $readOnly;
    }

    public function getReadOnly() {
        return $this->_readOnly;
    }

    public function setRenderOnlyCore($renderOnlyCore) {
        $this->_renderOnlyCore = (bool) $renderOnlyCore;
    }

    public function getRenderOnlyCore() {
        return $this->_renderOnlyCore;
    }

    public function addFormHelper($field_name, $text, $position = 'bottom') {
        if ((bool) Eabi_Ipenelo_Calendar::get('disable_tooltips')) {
            return $this;
        }
        $allowedPositions = array('top', 'bottom', 'left', 'right');
        if (!in_array($position, $allowedPositions)) {
            throw new Exception('Only allowed positions are: ' . implode(',', $allowedPositions));
        }
        $url = admin_url('admin.php?page=ipenelo_calendar_config');
        $turnOffText = '<div class="info">' . sprintf($this->__->l('You can turn off tooltips under the <a href="%s">Calendar settings</a> menu.'), $url) . '</div>';

        $this->_formHelpers[$field_name] = array(
            'position' => $position,
            'text' => $text . $turnOffText,
        );
        return $this;
    }

    public function removeFormHelper($field_name) {
        if (isset($this->_formHelpers[$field_name])) {
            unset($this->_formHelpers[$field_name]);
        }
        return $this;
    }

    private $_labelsSorted = false;

    /**
      Returns all the label texts in a form and takes into the account the ordering of the fields.
      @return - assoc array in a format
      array[field_name] = label
      label has been already htmlspecialchared.

     */
    public function getLabels() {
        if (!$this->_labelsSorted) {
            $processedFields = array();
            $processedLabels = array();
            //process the fields which have been ordered
            if (isset(self::$_fieldOrders[self::__CLASS])) {
                foreach (self::$_fieldOrders[self::__CLASS] as $field_name) {
                    if (isset($this->_labels[$field_name])) {
                        $processedLabels[$field_name] = $this->_labels[$field_name];
                        $processedFields[$field_name] = $field_name;
                    }
                }
            }

            //process the rest of the fields as they are defined.
            foreach ($this->_labels as $field_name => $html) {
                if (!isset($processedFields[$field_name])) {
                    $processedLabels[$field_name] = $this->_labels[$field_name];
                    $processedHtml .= "\r\n";
                }
            }

            $this->_labels = $processedLabels;
            $this->_labelsSorted = true;
        }
        return $this->_labels;
    }

    public static function clearRestrictedOutputs() {
        self::$_restrictedOutputs = array();
    }

    public static function addRestrictedOutput($restrictedOutput) {
        self::$_restrictedOutputs[$restrictedOutput] = $restrictedOutput;
    }

    public static function removeRestrictedOutput($restrictedOutput) {
        if (isset(self::$_restrictedOutputs[$restrictedOutput])) {
            unset(self::$_restrictedOutputs[$restrictedOutput]);
        }
    }

    public static function clearFieldOrders($allClasses = false) {
        if ($allClasses) {
            self::$_fieldOrders = array();
            return;
        }
        if (isset(self::$_fieldOrders[self::__CLASS])) {
            self::$_fieldOrders[self::__CLASS] = array();
        }
    }

    public static function addFieldOrder($field_name) {
        if (!isset(self::$_fieldOrders[self::__CLASS])) {
            self::$_fieldOrders[self::__CLASS] = array();
        }
        self::$_fieldOrders[self::__CLASS][$field_name] = $field_name;
    }

    public static function removeFieldOrder($field_name) {
        if (isset(self::$_fieldOrders[self::__CLASS])
                && isset(self::$_fieldOrders[self::__CLASS][$field_name])) {
            unset(self::$_fieldOrders[self::__CLASS][$field_name]);
        }
    }

    /**
      Is the same as the class constructor, but can be invoked on the already constructed
      class instance.
      Returns new instance of the same class and initiates it with the supplied model data.
      @param - $data table row associative array
      @return - new instance of the same class.


     */
    public function newInstance(array $data) {
        $classParts = explode('_', get_class($this));
        $cnt = count($classParts);
        return Eabi_Ipenelo_Calendar::service()->get(strtolower($classParts[$cnt -2]).'s/'.$classParts[$cnt - 1], $data);
    }

    /**
      Converts raw _POST input to the format, which can be inserted directly to the database.
      Works only when the request is _POST.
      Before inserting the result to the Database, the returned result should be validated by using the
      self::validate() funtion.
      @return database table row instance as assoc array.

     */
    public function toDb() {
        if ($this->_readOnly) {
            return $this->_data;
        }
        $result = array();
        if (count($_POST) > 0) {
            foreach ($this->_db as $key => $value) {
                if ($value === false) {
                    $result[$key] = isset($_POST[$key]) ? $_POST[$key] : null;
                } else if (is_string($value)) {
                    $result[$key] = $this->$value($key);
                }
            }
            $this->_data = $result;
//			echo Eabi_Ipenelo_Calendar::d($this->_data);
            return $result;
        }
        return false;
    }

    /**
      Renders the raw html of the form.
      @return raw html of the form.

     */
    abstract public function render();

    public function reset() {
        self::$_calendarLocalized = null;
    }

    /**
      Adds a validation rule to the form.
      Order of the inputvalidation for the same field names is important, since only the first error
      from the same field name is returned on validation.
      Example: If there is required email address, then first rule for the email_address field
      should be "required" and the "email-format" validation should be entered as second rule for the same field.

      @param $field_name form field name
      @param $validationFunction valid callback for the validationfunction
      @param $js Javascript validation function body.

      ValidationFuctioncallback should be a function which takes $value as parameter
      and should return true on success and error message string on failure.

      Javascript validation function body is not required, but for improved user experience it is
      suggessted.
      javascript string should be body of the javascript function and should return true
      if the validation passed and errormessage string if the validation failed.

      @see Eabi_Ipenelo_Calendar_Helper_Validation


     */
    public function addValidatorRule($field_name, $validationFunction, $message, $js = null) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return $this;
        }
        if ($this->_readOnly) {
            return $this;
        }
        if (!isset($this->_validatorRules[$field_name])) {
            $this->_validatorRules[$field_name] = array();
        }

        if (is_array($validationFunction) && count($validationFunction) == 2
                && get_class($validationFunction[0]) == 'Eabi_Ipenelo_Calendar_Helper_Validation'
                && $validationFunction[1] == 'required') {
            $this->_requiredFields[$field_name] = $field_name;
        }

        $this->_validatorRules[$field_name][] = array(
            'validationFunction' => $validationFunction,
            'message' => $message,
            'js' => $js,
        );
        if ($js != null) {
            if (!isset($this->_validatorJsRules[$field_name])) {
                $this->_validatorJsRules[$field_name] = array();
            }
            $this->_validatorJsRules[$field_name][] = $js;
        }
        return $this;
    }

    /**
      Adds Javascript validation rule to the form.
      @param field_name - field_name
      @param js = javascript function body.

      @see self::addValidatorRule(field_name, validationFunction, message)
      @see Eabi_Ipenelo_Calendar_Helper_Validation


     */
    public function addValidatorJsRule($field_name, $js) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return $this;
        }
        if ($this->_readOnly) {
            return $this;
        }
        if (!isset($this->_validatorJsRules[$field_name])) {
            $this->_validatorJsRules[$field_name] = array();
        }
        $this->_validatorJsRules[$field_name][] = $js;
        return $this;
    }

    /**

      Validates the data against the submitted validation rules.
      @param $transformedRequest data, which has passed thru the toDb() function or raw assoc array of database table row.
      @return array of error message strings. If validation was successful, empty array is returned.
     */
    public function validate($transformedRequest) {
        $finalResult = array();
        if ($this->_nonceEnabled && !$this->_readOnly) {
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $this->_formName)) {
                $finalResult[] = $this->__->l('Security validation failed');
            }
        }
        foreach ($this->_validatorRules as $field_name => $rules) {
            $value = isset($transformedRequest[$field_name]) ? $transformedRequest[$field_name] : '';
            foreach ($rules as $rule) {
                $result = call_user_func($rule['validationFunction'], $value);
                if ($result !== true) {
                    if (is_string($result)) {
                        $finalResult[] = $result;
                    } else {
                        $finalResult[] = $rule['message'];
                    }
                    break;
                }
            }
        }
        return $finalResult;
    }

    /**
      Returns HTML text input.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addTextField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }

        $this->_db[$field_name] = false;
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $this->_labels[$field_name] = htmlspecialchars($label);

        $_input = '<input type="text" class="regular-text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';

        if ($this->_readOnly) {
            $_input = '<span id="' . $field_name . '">' . $value . '</span>';
        }

        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    public function addHiddenField($field_name, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $html = '';

        $this->_db[$field_name] = '__parseHidden';

        $_input = '<input type="hidden" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';

        if ($this->_readOnly) {
            $_input = '';
        }

        $this->_htmls[$field_name] = $_input;
        return $html;
    }

    /**
      Returns HTML textarea input.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addTextareaField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $this->_db[$field_name] = false;

        $this->_labels[$field_name] = htmlspecialchars($label);
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $_input = '<textarea class="large-text code ipenelo-large-text" id="' . $field_name . '" name="' . $field_name . '" rows="10" cols="50">';
        $_input .= $value;
        $_input .= '</textarea>';

        if ($this->_readOnly) {
            $_input = '<span id="' . $field_name . '">' . htmlspecialchars_decode($value) . '</span>';
        }


        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Returns HTML checkbox input, which is checked, when the value is 1.
      Reacts to the keys up, right, +, y  - checks the checkbox
      Reacts to the keys down, left, - , n - unchecks the checkbox
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addCheckboxField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $this->_db[$field_name] = '__parseCheckbox';

        $this->_labels[$field_name] = htmlspecialchars($label);
        $_input = '<input type="checkbox" class="" id="' . $field_name . '" name="' . $field_name . '" value="1"';
        if ($value == 1) {
            $_input .= ' checked="chedked"';
        }
        $_input .= '/>';

        if ($this->_readOnly) {
            if ($value == '1') {
                $value = $this->__->l('Yes');
            } else {
                $value = $this->__->l('No');
            }
            $_input = '<span id="' . $field_name . '">' . $value . '</span>';
        } else {

            $this->_js[] = <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	jQuery("#{$field_name}").click(function(e) {
		if (jQuery(this).attr('checked') == 'checked') {
			jQuery(this).trigger('was_checked');
		} else {
			jQuery(this).trigger('was_unchecked');
		}
	});

	jQuery("#{$field_name}").keydown(function(e) {
		var key = e.keyCode,
			up = jQuery([33,38,39,89,107]).index(key) != -1,
			down = jQuery([34,37,40,78,109]).index(key) != -1;
			if (up) {
				jQuery(this).attr('checked', 'checked').trigger('was_checked');
				return e.preventDefault();
			} else if (down) {
				jQuery(this).removeAttr('checked').trigger('was_unchecked');
				return e.preventDefault();
			}
			return e;
	});
});
/* ]]> */
</script>
EOT;
        }


        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';


        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);



        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Returns HTML select input.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @param $options array of key value pairs, where key is option value and value is option text.
      Also if the $value parameter is contained in the array_keys of options, then this option
      is selected.
      @return raw html

     */
    public function addSelectField($field_name, $label, $value, $options = array()) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $this->_db[$field_name] = false;

        $caughtValue = false;

        $this->_labels[$field_name] = htmlspecialchars($label);
        $_input = '<select class="" id="' . $field_name . '" name="' . $field_name . '">';
        foreach ($options as $key => $val) {
            $_input .= '<option value="' . htmlspecialchars($key) . '"';
            if ((string) $key == (string) $value) {
                $_input .= ' selected="selected"';
                $caughtValue = htmlspecialchars($val);
            }
            $_input .= '>' . htmlspecialchars($val);
            $_input .= '</option>';
        }
        $_input .= $value;
        $_input .= '</select>';


        if ($this->_readOnly) {
            if ($value == '0') {
                $value = '';
            }
            if ($caughtValue !== false) {
                $value = $caughtValue;
            }
            $_input = '<span id="' . $field_name . '">' . $value . '</span>';
        }


        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';


        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);

        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Returns HTML <p> input, useful for dispaying information.
      Value if this field will not be returned, when calling toDB function.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addInfoField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }

        $this->_labels[$field_name] = htmlspecialchars($label);
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $_input = '';
//		$_input = '<p id="'.$field_name.'">';
        $_input .= $value;
//		$_input .= '</p>';



        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Adds input field to render an URL input
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addUrlField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }

        $this->_db[$field_name] = false;
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $this->_labels[$field_name] = htmlspecialchars($label);

        $_input = '<input type="text" class="regular-text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';

        if ($this->_readOnly) {
            $_input = '<a id="' . $field_name . '" href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Adds input field to render currency input
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addCurrencyField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }

        $this->_db[$field_name] = '__parseCurrency';
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $this->_labels[$field_name] = htmlspecialchars($label);

        $thousandsSeparator = stripslashes(Eabi_Ipenelo_Calendar::get('currency_thousands_separator', ''));
        $decimalSeparator = Eabi_Ipenelo_Calendar::get('currency_decimal_separator', '.');
        $numDecimals = (int) Eabi_Ipenelo_Calendar::get('currency_num_decimals', '2');
        $iso = Eabi_Ipenelo_Calendar::get('currency_iso');
        $symbol = Eabi_Ipenelo_Calendar::get('currency_symbol');
        $symbolPosition = Eabi_Ipenelo_Calendar::get('currency_symbol_position', 'left');
        if ($symbol == '') {
            $symbol = $iso;
        }

        if (!$this->_readOnly) {
            $value = number_format($value, $numDecimals, $decimalSeparator, '');
        }
        $_input = '';
        if ($symbolPosition == 'left') {
            $_input .= htmlspecialchars($symbol);
        }


        $_input .= '<input type="text" class="regular-text ipenelo-currency" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';
        if ($symbolPosition == 'right') {
            $_input .= htmlspecialchars($symbol);
        }

        if ($this->_readOnly) {





            if ($value <= 0) {
                $value = $this->__->l('Free!');
            } else {
                if ($symbolPosition == 'right') {
                    $value = number_format($value, $numDecimals, $decimalSeparator, $thousandsSeparator) . $symbol;
                } else {
                    $value = $symbol . number_format($value, $numDecimals, $decimalSeparator, $thousandsSeparator);
                }
            }
            $_input = '<span id="' . $field_name . '">' . $value . '</span>';
        }

        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Adds input field to render numeric integer input, which acts to the following keys:
      up,+ increases the value by 1 unit
      down,- decreases the value by 1 unit
      page-up - increases the value by 10 units
      page-down - decreases the value by 10 units.

      The value is not decreased when it is equal or less to zero.

      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addNumberField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }

        $this->_db[$field_name] = false;
        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';

        $this->_labels[$field_name] = htmlspecialchars($label);

        $_input = '<input type="text" class="regular-text ipenelo-integer" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';

        if ($this->_readOnly) {
            $_input = '<a class="post-com-count ipenelo-calendar-number"><span class="comment-count" id="' . $field_name . '">' . $value . '</span></a>';
        } else {
            $this->_js[] = <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {

	jQuery("#{$field_name}").keydown(function(e) {
		var key = e.keyCode,
			up = jQuery([33,38,39,89,107]).index(key) != -1,
			down = jQuery([34,37,40,78,109]).index(key) != -1
			el = jQuery(this),
			val = parseInt(jQuery(this).val(), 10),
			amount = 1;
			if (!val || val < 0) {
				val = 0;
			}
			if (up) {
				if (key == 33) { amount = 10; }
				el.val(val + amount);
				return e.preventDefault();
			} else if (down) {
				if (key == 34) { amount = 10; }
				if (val >= amount) {
					el.val(val - amount);
				}
				return e.preventDefault();
			}
			return e;
	});
});
/* ]]> */
</script>
EOT;
        }

        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);
        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Returns html input, which accepts hex color or URL as parameter.
      HTML input contains upload field for the image and html hex color selector.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field.
      @return raw html

     */
    public function addColorField($field_name, $label, $value) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $this->_db[$field_name] = false;

        $this->_labels[$field_name] = htmlspecialchars($label);
        $_input = '<input type="text" class="regular-text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" />';


        //todo make the image display default.
        //todo make the select color button to display the color by default
        //also convert the css to negative color
        $src = '';
        $style = 'display: none;';
        $colorStyle = '';

        $isColor = false;
        $isImage = false;
        if (preg_match('/#[0-9a-faA-F]+/', $value) && strlen($value) == 7) {
            //we have color
            $colorStyle = 'background:' . $value . ';';

            $negValue = sprintf("%02X", (255 - hexdec(substr($value, 1, 2))))
                    . sprintf("%02X", (255 - hexdec(substr($value, 3, 2))))
                    . sprintf("%02X", (255 - hexdec(substr($value, 5, 2))));


            $colorStyle .= 'color:#' . $negValue . ';';
            $isColor = true;
        } else if ($value != '') {
            //we have url
            $src = htmlspecialchars($value);
            $style = '';
            $isImage = true;
        }

        $_input .= '<input id="' . $field_name . '_color" type="button" value="' . $this->__->l('Select color') . '" style="' . $colorStyle . '"/>';
        $_input .= '<input id="' . $field_name . '_image" type="button" value="' . $this->__->l('Select image') . '" />';

        $_input .= '<img id="' . $field_name . '_imageurl" src="' . $src . '" alt="" style="' . $style . '" />';

        if ($this->_readOnly) {
            if ($isColor) {
                $value = '<div id="' . $field_name . '_imageurl" class="ipenelo_colordiv" style="' . $colorStyle . '" ></div>';
            } else if ($isImage) {
                $h = (int) Eabi_Ipenelo_Calendar::get('image_thumb_height', 32);
                $w = (int) Eabi_Ipenelo_Calendar::get('image_thumb_width', 32);
                $style .= 'height:' . $h . 'px;width:' . $w . 'px;';
                $value = '<img id="' . $field_name . '_imageurl" src="' . $src . '" alt="" style="' . $style . '" />';
            }
            $_input = '<span id="' . $field_name . '">' . $value . '</span>';
        } else {
            $this->addColorpickerJs($field_name);
        }


        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';


        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);



        $this->_htmls[$field_name] = $html;
        return $html;
    }

    /**
      Returns HTML datetime input, with jquery tools calendar and time input, which
      reacts to the up,down,left,right,page-up,page-down buttons.
      @param $field_name field name
      @param $label label for the field
      @param $value value for the field. in yyyy-mm-dd hh:mm:ss format.
      @return raw html

     */
    public function addDateTimeField($field_name, $label, $value, $useShortReadonly = false) {
        if (isset(self::$_restrictedOutputs[$field_name])) {
            return '';
        }
        $this->_db[$field_name] = '__parseDateTime';
        $this->_labels[$field_name] = htmlspecialchars($label);

        //parse the time
        if ($value != '' && $value != '0000-00-00 00:00:00') {
            $d = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $value);
            $value = array();
            $value['date'] = $d->format(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat'));
            $value['time'] = $d->format('H:i');
        } else {
            $value = array();
            $value['date'] = '';
            $value['time'] = '';
        }

        $_input = '<input autocomplete="off" type="text" class="medium-text" id="' . $field_name . '_date" name="' . $field_name . '_date" value="' . $value['date'] . '" />';
        $_input .= '<input autocomplete="off" type="text" class="small-text" id="' . $field_name . '_time" name="' . $field_name . '_time" value="' . $value['time'] . '" />';
        $_input .= '<div class="ipenelo_rangeinput">';
        $_input .= '<input type="text" style="display:none;" class="small-text" id="' . $field_name . '_hh" name="' . $field_name . '_hh" value="0" />';
        $_input .= '<input type="text" style="display:none;" class="small-text" id="' . $field_name . '_mm" name="' . $field_name . '_mm" value="0" />';
        $_input .= '</div>';

        if ($this->_readOnly) {
            if ((bool) Eabi_Ipenelo_Calendar::get('enable_12h') && $value['time'] != '') {
                $value['time'] = $d->format('g:i A');
            }
            $_input = '<span id="' . $field_name . '">' . $value['date'] . ' ' . $value['time'] . '</span>';
            if ($useShortReadonly) {
                $_input = '<span id="' . $field_name . '">' . $value['date'] . '</span>';
            }
        } else {
            $this->addCalendarJs($field_name);
        }

        $_label = '<label for="' . $field_name . '">';
        $_label .= htmlspecialchars($label);
        $_label .= '</label>';


        $html = $this->getFormElementHtml($field_name);
        $html = str_replace('${LABEL}', $_label, $html);
        $html = str_replace('${INPUT}', $_input, $html);



        $this->_htmls[$field_name] = $html;
        return $html;
    }

    public function getRenderedFieldsRaw($addIds = false) {
        $renderedFields = array();
        $labels = $this->getLabels();
        foreach ($labels as $field_name => $label) {
            $renderedFields[$field_name] = array(
                'field_name' => $field_name,
                'label' => $label,
                'value' => $this->_isset($this->_data, $field_name, true),
                'valueText' => trim(strip_tags($this->_htmls[$field_name])),
            );
        }
        if ($addIds) {
            $renderedFields['id'] = array(
                'field_name' => 'id',
                'label' => $this->__->l('ID'),
                'value' => $this->_isset($this->_data, 'id', true),
                'valueText' => $this->_isset($this->_data, 'id', true),
            );
        }
        return $renderedFields;
    }

    /**

      After adding all the form fields, Javascript should be appended.
      If there was for example javascript validator rules, or simply
      javascript snippets in the forms, then this functions collects them all
      and adds to the js.
      @return raw javascript string.
     */
    public function collectJs() {
        $str = implode("\r\n", $this->_js);
        if (!$this->_readOnly) {

            if (count($this->_formHelpers) > 0) {
                //process the formhelpers
                $str .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function() {
EOT;



                foreach ($this->_formHelpers as $field_name => $formHelper) {

                    $tipText = addslashes($formHelper['text']);
                    $position = $formHelper['position'];
                    if ($position == 'left' || $position == 'right') {
                        $position = 'center ' . $position;
                    } else {
                        $position = $position . ' center';
                    }


                    $str .= <<<EOT
					jQuery('body').append('<div id="ipenelo-calendar-tip-{$field_name}" style="display:none;" class="ipenelo-calendar-tip">{$tipText}</div>');
					jQuery('#{$field_name}').tooltip({
						tip: '#ipenelo-calendar-tip-{$field_name}',
						tipClass: 'ipenelo-calendar-tip',
						position: '{$position}',
						delay: 100,
						events: {
							def: 'focus,blur'
						}
					});

EOT;
                }
                $str .= <<<EOT

	});			
/* ]]> */
</script>
EOT;
            } //end of process the formhelpers
            //process the validation JS rules
            if (count($this->_validatorJsRules) > 0) {

                $str .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
var eabi_ipenelo_calendar_validator = false;
jQuery(document).ready(function() {

	jQuery("#{$this->_formName}").validator();
	eabi_ipenelo_calendar_validator = jQuery("#{$this->_formName}").data('validator');
EOT;

                //parse the required field
                foreach ($this->_requiredFields as $field_name) {
                    $str .= <<<EOT

	jQuery("label[for=\"{$field_name}\"]").append('<sup class="em">*</sup>');
				
EOT;
                }


                $str .= <<<EOT
});
EOT;

                foreach ($this->_validatorJsRules as $field => $rules) {
//				$script = $rule['script'];
                    $str .= <<<EOT
jQuery.tools.validator.fn("#{$field}", function(el, v) {
	var result = true;
	
	//start chain
	
EOT;

                    foreach ($rules as $script) {
                        $str .= <<<EOT
	//start of item
	result = (function(el, v) { {$script} })(el, v);
	if (typeof(result) == 'string') {
		return result;
	}
	//end of item


EOT;
                    }

                    $str .= <<<EOT
	//end of chain
//	console.log(jQuery('#{$field}'));
 	eabi_ipenelo_calendar_validator.reset(jQuery('#{$field}'));
	return result;
});
	//add the onblur command
	jQuery('#{$field}').blur(function(e) {
	 	eabi_ipenelo_calendar_validator.checkValidity(jQuery('#{$field}'), e);
	});

EOT;
                }




                $str .= <<<EOT
			
/* ]]> */
</script>
EOT;
            } //end of process the validation JS rules
        }
        return $str;
    }

    /*
      Debugs the contents of the parameter.
      @param variable to debug
      @return debug string
     */

    public function d($var) {
        return '<pre>' . print_r($var, true) . '</pre>';
    }

    /**
      Function which takes all the submitted inputs, feeds it thru the ordering definition
      and returns the full html of the form elements.

      @return html of the ordered form elements.

     */
    protected function _render() {

        $processedFields = array();
        $processedHtml = '';

        if ($this->_nonceEnabled) {
            $processedHtml .= wp_nonce_field($this->_formName, '_wpnonce', false, false);
        }
//		echo $this->d(self::$_fieldOrders);
        //process the fields which have been ordered
        if (isset(self::$_fieldOrders[self::__CLASS])) {
            foreach (self::$_fieldOrders[self::__CLASS] as $field_name) {
                if (isset($this->_htmls[$field_name])) {
                    $processedHtml .= $this->_htmls[$field_name];
//					$processedHtml .= "\r\n";
                    $processedFields[$field_name] = $field_name;
                }
            }
        }

        //process the rest of the fields as they are defined.
        foreach ($this->_htmls as $field_name => $html) {
            if (!isset($processedFields[$field_name])) {
                $processedHtml .= $this->_htmls[$field_name];
//				$processedHtml .= "\r\n";
            }
        }

        return $processedHtml;
    }

    /**
      Wrapper function to return self::$_data contents to prevent E_WARNING errors
      returns array[$key] and if $key does not exist,then returns false.
      Optional htmlspecialcharsp parameter too.

     */
    protected function _isset(array &$data, $key, $htmlspecialchars = false) {
        if (isset($data[$key])) {
            if ($htmlspecialchars) {
                return htmlspecialchars(stripslashes($data[$key]));
            }
            return $data[$key];
        }
        return false;
    }

    /**
      Generates the Javascript for the field of addColorField

     */
    protected function addColorpickerJs($field_name) {
        $js = '';
        $js .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	jQuery('#{$field_name}_color').ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
			jQuery('#{$field_name}').val('#' + hex);
			jQuery('#{$field_name}_imageurl').hide();
			jQuery('#{$field_name}_color').css('background', '#' + hex);
			var negHex = (0xFF - parseInt(hex.substring(0, 2), 16)).toString(16) + (0xFF - parseInt(hex.substring(2, 4), 16)).toString(16) + (0xFF - parseInt(hex.substring(4, 6), 16)).toString(16);
			jQuery('#{$field_name}_color').css('color', '#' + negHex);
			jQuery(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			var val = jQuery('#{$field_name}').val(),
			colours = {"aliceblue":"f0f8ff","antiquewhite":"faebd7","aqua":"00ffff","aquamarine":"7fffd4","azure":"f0ffff",
    "beige":"f5f5dc","bisque":"ffe4c4","black":"000000","blanchedalmond":"ffebcd","blue":"0000ff","blueviolet":"8a2be2","brown":"a52a2a","burlywood":"deb887",
    "cadetblue":"5f9ea0","chartreuse":"7fff00","chocolate":"d2691e","coral":"ff7f50","cornflowerblue":"6495ed","cornsilk":"fff8dc","crimson":"dc143c","cyan":"00ffff",
    "darkblue":"00008b","darkcyan":"008b8b","darkgoldenrod":"b8860b","darkgray":"a9a9a9","darkgreen":"006400","darkkhaki":"bdb76b","darkmagenta":"8b008b","darkolivegreen":"556b2f",
    "darkorange":"ff8c00","darkorchid":"9932cc","darkred":"8b0000","darksalmon":"e9967a","darkseagreen":"8fbc8f","darkslateblue":"483d8b","darkslategray":"2f4f4f","darkturquoise":"00ced1",
    "darkviolet":"9400d3","deeppink":"ff1493","deepskyblue":"00bfff","dimgray":"696969","dodgerblue":"1e90ff",
    "firebrick":"b22222","floralwhite":"fffaf0","forestgreen":"228b22","fuchsia":"ff00ff",
    "gainsboro":"dcdcdc","ghostwhite":"f8f8ff","gold":"ffd700","goldenrod":"daa520","gray":"808080","green":"008000","greenyellow":"adff2f",
    "honeydew":"f0fff0","hotpink":"ff69b4",
    "indianred ":"cd5c5c","indigo ":"4b0082","ivory":"fffff0","khaki":"f0e68c",
    "lavender":"e6e6fa","lavenderblush":"fff0f5","lawngreen":"7cfc00","lemonchiffon":"fffacd","lightblue":"add8e6","lightcoral":"f08080","lightcyan":"e0ffff","lightgoldenrodyellow":"fafad2",
    "lightgrey":"d3d3d3","lightgreen":"90ee90","lightpink":"ffb6c1","lightsalmon":"ffa07a","lightseagreen":"20b2aa","lightskyblue":"87cefa","lightslategray":"778899","lightsteelblue":"b0c4de",
    "lightyellow":"ffffe0","lime":"00ff00","limegreen":"32cd32","linen":"faf0e6",
    "magenta":"ff00ff","maroon":"800000","mediumaquamarine":"66cdaa","mediumblue":"0000cd","mediumorchid":"ba55d3","mediumpurple":"9370d8","mediumseagreen":"3cb371","mediumslateblue":"7b68ee",
    "mediumspringgreen":"00fa9a","mediumturquoise":"48d1cc","mediumvioletred":"c71585","midnightblue":"191970","mintcream":"f5fffa","mistyrose":"ffe4e1","moccasin":"ffe4b5",
    "navajowhite":"ffdead","navy":"000080",
    "oldlace":"fdf5e6","olive":"808000","olivedrab":"6b8e23","orange":"ffa500","orangered":"ff4500","orchid":"da70d6",
    "palegoldenrod":"eee8aa","palegreen":"98fb98","paleturquoise":"afeeee","palevioletred":"d87093","papayawhip":"ffefd5","peachpuff":"ffdab9","peru":"cd853f","pink":"ffc0cb","plum":"dda0dd","powderblue":"b0e0e6","purple":"800080",
    "red":"ff0000","rosybrown":"bc8f8f","royalblue":"4169e1",
    "saddlebrown":"8b4513","salmon":"fa8072","sandybrown":"f4a460","seagreen":"2e8b57","seashell":"fff5ee","sienna":"a0522d","silver":"c0c0c0","skyblue":"87ceeb","slateblue":"6a5acd","slategray":"708090","snow":"fffafa","springgreen":"00ff7f","steelblue":"4682b4",
    "tan":"d2b48c","teal":"008080","thistle":"d8bfd8","tomato":"ff6347","turquoise":"40e0d0",
    "violet":"ee82ee",
    "wheat":"f5deb3","white":"ffffff","whitesmoke":"f5f5f5",
    "yellow":"ffff00","yellowgreen":"9acd32"};
    



			if (typeof(val) == 'string') {
				//handle word formats
				if (typeof(colours[val]) != 'undefined') {
					val = colours[val];
				}
				
				
				
				val = val.replace(/[^0-9a-f]+/gim,'');
				//handle format like #ffe;
				if (val.length == 3) {
					val = val.charAt(0) + val.charAt(0) + val.charAt(1) + val.charAt(1) + val.charAt(2) + val.charAt(2);
				}
			}
			
			jQuery(this).ColorPickerSetColor(val);
			if (val.length == 7) {
				jQuery('#{$field_name}').val('#' + val);
			}
		}
	});

	jQuery('#{$field_name}_image').click(function() {
	 formfield = jQuery('#{$field_name}').attr('name');
	 tb_show('', 'media-upload.php?type=image&amp;ipenelo-calendar=true&amp;TB_iframe=true');
	 
	 jQuery('#TB_window').unload(function() {
	 	//trigger the ajax request
//	 	jQuery.get('?ipenelo-calendar=false');
	 });
	 return false;
	});

	window.send_to_editor = function(html) {
	 imgurl = jQuery('img',html).attr('src');
	 jQuery('#{$field_name}').val(imgurl);
	 jQuery('#{$field_name}_imageurl').attr('src', imgurl);
	 jQuery('#{$field_name}_imageurl').show();
     jQuery('#{$field_name}_color').css('background', '');
     jQuery('#{$field_name}_color').css('color', '');
	 tb_remove();
	}

});
/* ]]> */
</script>
EOT;
        $this->_js[] = $js;
        return $js;
    }

    /**
      Generates the Javascript for the field of addDateTimeField
      Localizes the calendar too.

     */
    protected function addCalendarJs($field_name) {


        $val = Eabi_Ipenelo_Calendar::service()->get('helpers/Validation');
        if (!isset($this->_validatorJsRules[$field_name . '_date'])) {
            $this->_validatorJsRules[$field_name . '_date'] = array();
        }
        $this->_validatorJsRules[$field_name . '_date'][] = $val->js_date();
        if (!isset($this->_validatorJsRules[$field_name . '_time'])) {
            $this->_validatorJsRules[$field_name . '_time'] = array();
        }
        $this->_validatorJsRules[$field_name . '_time'][] = $val->js_time();


        $js = '';
        $js .= $this->localizeCalendar();
        $js .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function() {
	var hval = 0,
	mval = 0,
	timeVals = jQuery("#{$field_name}_time").val().split(":");
	if (timeVals.length == 2) {
		hval = Number(timeVals[0]).valueOf();
		mval = Number(timeVals[1]).valueOf();
	}
	jQuery("#{$field_name}_date").dateinput();
//	var apil = jQuery("#{$field_name}_date").data("dateinput");
//	console.log(apil);

	jQuery("#{$field_name}_hh").rangeinput({
		'min': 0,
		'max': 23,
		'step': 1,
		'value': hval
	});
	jQuery("#{$field_name}_mm").rangeinput({
		'min': 0,
		'max': 59,
		'step': 1,
		'value': mval
	});
	jQuery("#{$field_name}_hh").prev().find(".handle").html('h');
	jQuery("#{$field_name}_mm").prev().find(".handle").html('m');
	jQuery("#{$field_name}_hh").change(function(event, value) {
		var prevTime = jQuery("#{$field_name}_time"),
		times = [],
		hour = 0,
		minute = 0;
		
		if (!prevTime || prevTime.val() == '') {
			prevTime = '00:00';
		} else {
			prevTime = prevTime.val();
		}
		times = prevTime.split(":");
		//hours
		if (times.length == 2 && times[0]) {
			hour = value;
			if (hour < 0 || hour > 23) {
				hour = 0;
			}
		}
		//minutes
		if (times.length == 2 && times[1]) {
			minute = Number(times[1]).valueOf();
			if (minute < 0 || minute > 59) {
				minute = 0;
			}
		}
		
		//glue them together
		if (minute < 10) {
			minute = "0" + minute;
		}
		if (hour < 10) {
			hour = "0" + hour;
		}
		jQuery("#{$field_name}_time").val(hour + ":" + minute);
		jQuery("#{$field_name}_time").focus();


	});
	jQuery("#{$field_name}_mm").change(function(event, value) {
		var prevTime = jQuery("#{$field_name}_time"),
		times = [],
		hour = 0,
		minute = 0;
		
		if (!prevTime || prevTime.val() == '') {
			prevTime = '00:00';
		} else {
			prevTime = prevTime.val();
		}
		times = prevTime.split(":");
		//hours
		if (times.length == 2 && times[0]) {
			hour = Number(times[0]).valueOf();
			if (hour < 0 || hour > 23) {
				hour = 0;
			}
		}
		//minutes
		if (times.length == 2 && times[1]) {
			minute = value;
			if (minute < 0 || minute > 59) {
				minute = 0;
			}
		}
		
		//glue them together
		if (minute < 10) {
			minute = "0" + minute;
		}
		if (hour < 10) {
			hour = "0" + hour;
		}
		jQuery("#{$field_name}_time").val(hour + ":" + minute);
		jQuery("#{$field_name}_time").focus();


	});
	
	var hourApi = jQuery("#{$field_name}_hh").data("rangeinput");
	var minuteApi = jQuery("#{$field_name}_mm").data("rangeinput");
	jQuery("#{$field_name}_time").keydown(function(e) {
		var key = e.keyCode,
			up = jQuery([38]).index(key) != -1,
			down = jQuery([40]).index(key) != -1,
			left = jQuery([34, 37]).index(key) != -1,
			right = jQuery([33, 39]).index(key) != -1;
			if (up) {
				hourApi.step(1);
				e.type = "change";
				hourApi.getInput().trigger(e, [hourApi.getValue()]);
				return e.preventDefault();
			} else if (down) {
				hourApi.step(-1);
				e.type = "change";
				hourApi.getInput().trigger(e, [hourApi.getValue()]);
				return e.preventDefault();
			} else if (left) {
				minuteApi.step(key == 34 ? -10 : -1, e);
				e.type = "change";
				minuteApi.getInput().trigger(e, [minuteApi.getValue()]);
				return e.preventDefault();
			} else if (right) {
				minuteApi.step(key == 33 ? 10 : 1, e);
				e.type = "change";
				minuteApi.getInput().trigger(e, [minuteApi.getValue()]);
				return e.preventDefault();
			}
			return e;
	});

});
/* ]]> */
</script>
EOT;
        $this->_js[] = $js;
        return $js;
    }

    /**
      Member to make sure the calendar localization is performed only once per page load.
     */
    protected static $_calendarLocalized;

    /**
      Javascript calendar localization callback.
     */
    protected function localizeCalendar() {
        return self::_localizeCalendar();
    }

    protected static function _localizeCalendar() {
        $str = '';
        if (self::$_calendarLocalized == null) {
            $locale = WPLANG;
            global $wp_locale;
            $january = htmlentities(__('January'), ENT_QUOTES, 'utf-8');
            $february = htmlentities(__('February'), ENT_QUOTES, 'utf-8');
            $march = htmlentities(__('March'), ENT_QUOTES, 'utf-8');
            $april = htmlentities(__('April'), ENT_QUOTES, 'utf-8');
            $may = htmlentities(__('May'), ENT_QUOTES, 'utf-8');
            $june = htmlentities(__('June'), ENT_QUOTES, 'utf-8');
            $july = htmlentities(__('July'), ENT_QUOTES, 'utf-8');
            $august = htmlentities(__('August'), ENT_QUOTES, 'utf-8');
            $september = htmlentities(__('September'), ENT_QUOTES, 'utf-8');
            $october = htmlentities(__('October'), ENT_QUOTES, 'utf-8');
            $november = htmlentities(__('November'), ENT_QUOTES, 'utf-8');
            $december = htmlentities(__('December'), ENT_QUOTES, 'utf-8');

            $jan = htmlentities($wp_locale->get_month_abbrev(__('January')), ENT_QUOTES, 'utf-8');
            $feb = htmlentities($wp_locale->get_month_abbrev(__('February')), ENT_QUOTES, 'utf-8');
            $mar = htmlentities($wp_locale->get_month_abbrev(__('March')), ENT_QUOTES, 'utf-8');
            $apr = htmlentities($wp_locale->get_month_abbrev(__('April')), ENT_QUOTES, 'utf-8');
            $may_s = htmlentities($wp_locale->get_month_abbrev(__('May')), ENT_QUOTES, 'utf-8');
            $jun = htmlentities($wp_locale->get_month_abbrev(__('June')), ENT_QUOTES, 'utf-8');
            $jul = htmlentities($wp_locale->get_month_abbrev(__('July')), ENT_QUOTES, 'utf-8');
            $aug = htmlentities($wp_locale->get_month_abbrev(__('August')), ENT_QUOTES, 'utf-8');
            $sep = htmlentities($wp_locale->get_month_abbrev(__('September')), ENT_QUOTES, 'utf-8');
            $oct = htmlentities($wp_locale->get_month_abbrev(__('October')), ENT_QUOTES, 'utf-8');
            $nov = htmlentities($wp_locale->get_month_abbrev(__('November')), ENT_QUOTES, 'utf-8');
            $dec = htmlentities($wp_locale->get_month_abbrev(__('December')), ENT_QUOTES, 'utf-8');

            $sunday = htmlentities(__('Sunday'), ENT_QUOTES, 'utf-8');
            $monday = htmlentities(__('Monday'), ENT_QUOTES, 'utf-8');
            $tuesday = htmlentities(__('Tuesday'), ENT_QUOTES, 'utf-8');
            $wednesday = htmlentities(__('Wednesday'), ENT_QUOTES, 'utf-8');
            $thursday = htmlentities(__('Thursday'), ENT_QUOTES, 'utf-8');
            $friday = htmlentities(__('Friday'), ENT_QUOTES, 'utf-8');
            $saturday = htmlentities(__('Saturday'), ENT_QUOTES, 'utf-8');

            $sun = htmlentities($wp_locale->get_weekday_initial(__('Sunday')), ENT_QUOTES, 'utf-8');
            $mon = htmlentities($wp_locale->get_weekday_initial(__('Monday')), ENT_QUOTES, 'utf-8');
            $tue = htmlentities($wp_locale->get_weekday_initial(__('Tuesday')), ENT_QUOTES, 'utf-8');
            $wed = htmlentities($wp_locale->get_weekday_initial(__('Wednesday')), ENT_QUOTES, 'utf-8');
            $thu = htmlentities($wp_locale->get_weekday_initial(__('Thursday')), ENT_QUOTES, 'utf-8');
            $fri = htmlentities($wp_locale->get_weekday_initial(__('Friday')), ENT_QUOTES, 'utf-8');
            $sat = htmlentities($wp_locale->get_weekday_initial(__('Saturday')), ENT_QUOTES, 'utf-8');

            $firstDay = (int) get_option('start_of_week');
            $format = Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat', true);



            $str .= <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
jQuery.tools.dateinput.localize("{$locale}", {
   months: '{$january},{$february},{$march},{$april},{$may},{$june},{$july},{$august},{$september},{$october},{$november},{$december}',
   shortMonths:  '{$jan},{$feb},{$mar},{$apr},{$may_s},{$jun},{$jul},{$aug},{$sep},{$oct},{$nov},{$dec}',
   days:         '{$sunday},{$monday},{$tuesday},{$wednesday},{$thursday},{$friday},{$saturday}',
   shortDays:    '{$sun},{$mon},{$tue},{$wed},{$thu},{$fri},{$sat}'
});
jQuery.tools.dateinput.conf.lang = '{$locale}';
jQuery.tools.dateinput.conf.format = '{$format}';
jQuery.tools.dateinput.conf.firstDay = {$firstDay};

/* ]]> */
</script>
EOT;
            self::$_calendarLocalized = $str;
        }
        return $str;
    }

    public static function localizedCalendarScript() {
        if (self::$_calendarLocalized == null) {
            self::_localizeCalendar();
        }
        return self::$_calendarLocalized;
    }

    private function __parseHidden($field_name) {
        return $this->_data[$field_name];
    }

    private function __parseDateTime($field_name) {
        $str = '';
        if (isset($_POST[$field_name . '_date']) && $_POST[$field_name . '_date'] != '') {
            $d = Eabi_Ipenelo_Calendar::createDateFromFormat(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat'), $_POST[$field_name . '_date']);
            $str .= $d->format('Y-m-d');
        } else {
            $str .= '0000-00-00';
        }
        if (isset($_POST[$field_name . '_time']) && $_POST[$field_name . '_time']) {
            $d = Eabi_Ipenelo_Calendar::createDateFromFormat('H:i', $_POST[$field_name . '_time']);
            $str .= ' ' . $d->format('H:i:s');
        } else {
            $str .= ' 00:00:00';
        }
        return $str;
    }

    private function __parseCurrency($field_name) {
        if (!isset($_POST[$field_name])) {
            return '';
        }
        if (is_string($_POST[$field_name])) {
            return str_replace(',', '.', $_POST[$field_name]);
        }
        return 0;
    }

    private function __parseCheckbox($field_name) {
        if (!isset($_POST[$field_name])) {
            return 0;
        }
        if (is_string($_POST[$field_name])) {
            return $_POST[$field_name];
        }
        return 0;
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