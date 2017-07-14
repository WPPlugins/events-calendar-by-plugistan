<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
class Eabi_Ipenelo_Calendar_Shortcode_Calendar {

    const SHORTCODE = 'ipenelo_calendar';

    private $_ajaxUrl;
    protected $_defaultAttributes = array(
        'size' => 'medium',
        'type' => 'calendar',
        'position' => 'bottom',
        'y' => 'current',
        'm' => 'current',
        'category_ids' => '',
        'event_ids' => '',
        'active_only' => 'false',
        'no_overlap' => 'false',
    );
    protected $_calroots = array(
        'medium' => 'calroot',
        'small' => 'calrootsmall',
        'large' => 'calrootlarge',
    );
    protected $_types = array(
        'list',
        'calendar',
    );
    protected $_positions = array(
        'left',
        'right',
        'bottom',
        'top',
    );

    public function __construct() {
        
    }

    public function handleMessages($attributes, $content, $code = '') {
        $html = '';
        $html .= Eabi_Ipenelo_Calendar::displayErrors();
        $html .= Eabi_Ipenelo_Calendar::displayMessages();
        return $html;
    }

    /**
      Handles shortcode self::SHORTCODE

     */
    public function handleShortCode($attributes, $content, $code = '') {
        //mix the attributes with default attributes.
        $attributes = shortcode_atts($this->_defaultAttributes, $attributes);

        $html = '';
        if (!is_user_logged_in() && Eabi_Ipenelo_Calendar::get('log_to_view')) {
            return '';
        }

        if (!in_array($attributes['type'], $this->_types)) {
            $attributes['type'] = 'calendar';
        }
        if (!in_array($attributes['position'], $this->_positions)) {
            $attributes['position'] = 'bottom';
        }

        $this->_ajaxUrl = admin_url('admin-ajax.php');

        $toFilter = array('category_ids', 'event_ids');

        if ($attributes['event_ids'] == 'current' && isset($_REQUEST['event_id'])) {
            $attributes['event_ids'] = (int) $_REQUEST['event_id'];
        }

        foreach ($toFilter as $f) {
            preg_replace('/[^0-9,]/', '', $attributes[$f]);
        }

        $toFilter = array('m', 'y');

        foreach ($toFilter as $f) {
            preg_replace('/[^0-9A-Za-z]/', '', $attributes[$f]);
        }


        $dateFilterAttributes = array(
            'm' => $attributes['m'],
            'y' => $attributes['y'],
            'category_ids' => $attributes['category_ids'],
            'event_ids' => $attributes['event_ids'],
            'active_only' => ($attributes['active_only'] == 'true') ? '1' : '0',
            'no_overlap' => ($attributes['no_overlap'] == 'true') ? '1' : '0',
        );


        if ($attributes['type'] == 'list') {
            $eventJson = Eabi_Ipenelo_Calendar::service()->get('grids/EventJson');

            if ($dateFilterAttributes['m'] == 'all' || $dateFilterAttributes['y'] == 'all') {
                unset($dateFilterAttributes['m']);
                unset($dateFilterAttributes['y']);
            }

            $eventJson->setDateFilter(
                    $dateFilterAttributes
            );
            $eventJson->setTableView(true);
            $_items = (array) $eventJson->render();
            $params = array(
                'items' => $_items,
            );
            if (Eabi_Ipenelo_Calendar::get('disable_registration')) {
                $html = $this->_template->parse('calendar-basiclist.phtml', $params);
            } else {
                $html = $this->_template->parse('calendar-list.phtml', $params);
            }
            $html .= $this->_getListJs();
            return $html;
        }

        $calroot = 'calroot';
        if (isset($this->_calroots[$attributes['size']])) {
            $calroot = $this->_calroots[$attributes['size']];
        } else {
            $calroot = Eabi_Ipenelo_Calendar::get('calendar_size', 'calroot');
        }

        $idcode = '-' . hexdec(uniqid());
        $ajaxUrl = $this->_ajaxUrl;

        $html .= Eabi_Ipenelo_Calendar::service()->getStatic('forms/Abstract', 'localizedCalendarScript');

//        $html .= Eabi_Ipenelo_Calendar_Form_Abstract::localizedCalendarScript();

        $eventJson = Eabi_Ipenelo_Calendar::service()->get('grids/EventJson');
        $eventJson->setDateFilter(
                $dateFilterAttributes
        );
        $eventJson->render();
        $minDate = $eventJson->getMinDate();
        $maxDate = $eventJson->getMaxDate();
        $jQueryStyle = "";
        if (Eabi_Ipenelo_Calendar::get('color_calendar')) {
            $jQueryStyle = "jQuery(value).attr('style', 'background: ' + color + ';');";
        }
        $closeText = addslashes($this->__->l('Close'));
        $titleText = addslashes($this->__->l('Title'));
        $priceText = addslashes($this->__->l('Price'));
        $statusText = addslashes($this->__->l('Status'));
        $position = $attributes['position'];
        if ($position == 'left' || $position == 'right') {
            $position = 'center ' . $position;
        } else {
            $position = $position . ' center';
        }
        $iconHeight = (int) Eabi_Ipenelo_Calendar::get('image_thumb_height', 32);
        $iconWidth = (int) Eabi_Ipenelo_Calendar::get('image_thumb_width', 32);
        $tbHeight = (int) Eabi_Ipenelo_Calendar::get('tb_height', 600);
        $tbWidth = (int) Eabi_Ipenelo_Calendar::get('tb_width', 1024);


        if ($tbHeight == 0) {
            $tbHeight = 600;
        }
        if ($tbWidth == 0) {
            $tbWidth = 1024;
        }

        $disableRegistration = Eabi_Ipenelo_Calendar::get('disable_registration') ? 'true' : 'false';
        $disableInfoIcon = Eabi_Ipenelo_Calendar::get('disable_infoicon') ? 'true' : 'false';
        $categoryIds = $dateFilterAttributes['category_ids'];
        $eventIds = $dateFilterAttributes['event_ids'];
        $activeOnly = $dateFilterAttributes['active_only'];
        $noOverlap = $dateFilterAttributes['no_overlap'];
        $fullDayText = addslashes($this->__->l('All day'));

        $html .= <<<EOT

<!-- wrapper element -->
<div id="calendar-wrapper{$idcode}" class="ipenelo-calendar-wrapper ipenelo-{$calroot}">
	<input type="text" id="ipenelo-calendar{$idcode}" name="mydate{$idcode}" value="" class="ipenelo-calendar-input" />
</div>
<div class="ipenelo-calendar-free"></div>

<!-- large date display -->

<div id="theform{$idcode}" style="display:none;" class="ipenelo-calendar-form"></div>

<script type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function() {
		jQuery('body').append('<div id="theday{$idcode}" class="ipenelo-calendar-day"></div>');
		jQuery("#ipenelo-calendar{$idcode}").dateinput( {
		
			// closing is not possible
			onHide: function()  {
				return false; 
			},
			'css' : { 'root': 'calroot{$idcode}',
			'rootclass' : '{$calroot}' },
			onShow: function() {
				this.getInput().attr('readonly', 'readonly');
				this.getConf().checkMonth(
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getYearWithinView(),
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getMonthWithinView(),
					this
				);
			},
			'min' : '{$minDate}',
			'max' : '{$maxDate}',
	
			// when date changes update the day display
			change: function(e, date)  {
				var sqlDate = this.getValue("yyyy-mm-dd"),
				data = this.getConf().fetchedData,
				html = '',
				tmpDate = '&nbsp;';
				if (typeof(data) == 'object' && data[sqlDate]) {
					html += '<span title="{$closeText}" class="ipenelo-close" onclick="jQuery(\'#ipenelo-calendar{$idcode}\').data(\'dateinput\').getConf().closeTip();">&nbsp;</span>';
					html += '<table>';
					html += '<thead>';
					
					if (data[sqlDate][0]) {
						tmpDate = data[sqlDate][0]['active_from']['valueText'].split(' ')[0];
					}
					
					html += '<th class="background" colspan="2">' + tmpDate + '</th>';
					html += '<th class="title">{$titleText}</th>';
					if (!{$disableRegistration}) {
						html += '<th class="price">{$priceText}</th>';
						html += '<th class="status">{$statusText}</th>';
					}
					html += '</thead>';
					html += '<tbody>';
					for (var i = 0, cnt = data[sqlDate].length; i < cnt; i++) {
						var color = data[sqlDate][i]['background']['value'];
						
						if (color.length > 7) {
							color = "url('" + color + "') no-repeat scroll center center transparent";
						}

						html += '<tr>';
						html += '<td class="background" >';
						html += '<span class="ipenelo-category" style="height:{$iconHeight}px;width:{$iconWidth}px;background: ' + color + ';">&nbsp;</span>'
						html += '&nbsp';
						html += '</td>';
						html += '<td class="time">';
						if (data[sqlDate][i]['active_from']['valueText'].split(' ')[1]) {
							html += data[sqlDate][i]['active_from']['valueText'].split(' ')[1];
							if (data[sqlDate][i]['active_from']['valueText'].split(' ')[2]) {
								html += data[sqlDate][i]['active_from']['valueText'].split(' ')[2];
							}
						} else {
							html += '{$fullDayText}';
						}
						if (data[sqlDate][i]['active_from']['value'].substring(0, 10) != data[sqlDate][i]['active_to']['value'].substring(0, 10)) {
							html += ' ';
							html += '<span class="ipenelo-small">';
							html += '...' + data[sqlDate][i]['active_to']['valueText'].split(' ')[0];
							html += '</span>';
						}
						html += '</td>';
						html += '<td class="title">';

						if (data[sqlDate][i]['url_click_title']['value'] == '1') {
							html += '<a href="' + data[sqlDate][i]['url']['valueText'] + '">';
							html += data[sqlDate][i]['title']['valueText'];
							html += '</a>';
						} else {
							html += data[sqlDate][i]['title']['valueText'];
						}
						if (!{$disableInfoIcon}) {
							html += ' <a class="info-icon" onclick="jQuery(\'#ipenelo-calendar{$idcode}\').data(\'dateinput\').getConf().register(\'' + data[sqlDate][i]['title']['valueText'] + '\',' + data[sqlDate][i]['id']['value'] + '); return false;">';
							html += 'i';
							html += '</a>';
						}

						
						html += '</td>';
						
						if (!{$disableRegistration}) {
							html += '<td class="price">';
							html += data[sqlDate][i]['cost']['valueText'];
							html += '</td>';
							html += '<td class="status">';
	
							html += '<a class="ipenelo-register" onclick="jQuery(\'#ipenelo-calendar{$idcode}\').data(\'dateinput\').getConf().register(\'' + data[sqlDate][i]['title']['valueText'] + '\',' + data[sqlDate][i]['id']['value'] + '); return false;">' + data[sqlDate][i]['is_free']['valueText'] + '</a>';
							html += '</td>';
						}
						html += '</tr>';
						
					}
					html += '</tbody>';
					html += '</table>';
//					jQuery("#theday{$idcode}").show();
					jQuery("#theday{$idcode}").html(html);
					
					//do the tooltip
					if (this.getConf().tApi) {
						this.getConf().tApi.hide();
						this.getConf().tApi = null;
					}
					
					var calCurrent = this.getCalendar().find('#calcurrent')
					calCurrent.tooltip({
						tip: '#theday{$idcode}',
						tipClass: 'ipenelo-calendar-day',
						position: '{$position}',
						offset: [0, 0],
						delay: 3000,
						events: {
							def: 'click,blur'
						}
					});
					this.getConf().tApi = calCurrent.data('tooltip');
					this.getConf().tApi.show();
					
					this.reposition();
				} else {
					jQuery("#theday{$idcode}").hide();
					this.reposition();
				}
			},
			closeTip: function() {
				if (this.tApi) {
					this.tApi.hide();
					this.tApi = null;
				}
			},
			
			'register' : function(caption, event_id) {
				jQuery.post('{$ajaxUrl}', {
					'action' : 'ipenelo_calendar_get_registrants',
					'event_id' : event_id,
					'form_id' : 'ipenelo-calendar{$idcode}'
				},
				function(data) {
					if (typeof(data) == 'object') {
						jQuery("#theform{$idcode}").html(data['content']);
						
						tb_show(caption, '#TB_inline?height={$tbHeight}&width={$tbWidth}&inlineId=theform{$idcode}' ,false);
						 jQuery('#TB_window').unload(function() {
						 	jQuery('div.error').remove();
						 	jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getConf().reload();
						 });
	

					}
				},
				'json'
				);

				
			},

			'submitData' : function(event_id, form_data, pmInfo) {
				var dataToSubmit = {
					'action': 'ipenelo_calendar_get_registrants',
					'event_id' : event_id,
					'register' : 'true'
				},
				payment_extra = {};
				
				if (typeof(pmInfo) == 'object' || typeof(pmInfo) == 'array') {
					for (var i = 0; i < pmInfo.length; i++) {
						payment_extra[pmInfo[i]['name']] = pmInfo[i]['value'];
					}
				}
				
				for (var i = 0; i < form_data.length; i++) {
					dataToSubmit[form_data[i]['name']] = form_data[i]['value'];
				}
				dataToSubmit['payment_data'] = JSON.stringify(payment_extra);
				dataToSubmit['form_id'] = 'ipenelo-calendar{$idcode}';
				
				jQuery.post('{$ajaxUrl}', 
				dataToSubmit
				,
				function(data) {
					if (typeof(data) == 'object') {
						jQuery("#TB_ajaxContent").html(data['content']);
						
					}
				},
				'json'
				);

				
			},
			'reload' : function() {
				var dataObject = jQuery('#ipenelo-calendar{$idcode}').data('dateinput'),
				dates = jQuery.makeArray(dataObject.getCalendar().find('#calweeks a')),
				isOff = true,
				prevDate = false,
				curDate = false,
				switched = false
				;
				for (var i = 0, c = dates.length; i < c; i++) {
					curDate = parseInt(jQuery(dates[i]).attr('href').replace('#', ''), 10);
					if (!isOff && curDate < prevDate) {
						isOff = true;
						switched = true;
					}
					if (isOff && curDate < prevDate && !switched) {
						isOff = false;
						switched = true;
					}
					if (isOff) {
						jQuery(dates[i]).removeAttr('style');
						jQuery(dates[i]).attr('class', dataObject.getConf().css.off);
					} else {
						jQuery(dates[i]).removeAttr('style');
						jQuery(dates[i]).removeAttr('class');
					}
					
					prevDate = curDate;
					switched = false;
				}
				

				if (dataObject.getConf().css.sunday) {
					dataObject.getCalendar().find('#' + dataObject.getConf().css.weeks).find('.' + dataObject.getConf().css.week).each(function() {
						var beg = dataObject.getConf().firstDay ? 7 - dataObject.getConf().firstDay : 0;
						jQuery(this).children().slice(beg, beg + 1).addClass(dataObject.getConf().css.sunday);		
					});	
				} 


				dataObject.getConf().checkMonth(
					dataObject.getYearWithinView(),
					dataObject.getMonthWithinView(),
					dataObject,
					true
				);

			},


			onPrev : function(event) {
				this.getConf().checkMonth(
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getYearWithinView(),
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getMonthWithinView(),
					this
				);
			},
			"onNext" : function(event) {
				this.getConf().checkMonth(
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getYearWithinView(),
					jQuery('#ipenelo-calendar{$idcode}').data('dateinput').getMonthWithinView(),
					this
				);
			},
			'fetchedData' : [],
			'keys' : false,
			
			"checkMonth" : function(year, month, dataObject, makeClick) {
				var prevMonth, prevYear = year, nextMonth, nextYear = year;
				month = month + 1;
				
				prevMonth = month - 1;
				nextMonth = month + 1;
				
				if (prevMonth < 1) {
					prevMonth = 12;
					prevYear = prevYear - 1;
				}
				if (nextMonth > 12) {
					nextMonth = 1;
					nextYear = nextYear + 1;
				}
				
				
				if (month < 10) {
					month = '0' + month;
				}
				if (prevMonth < 10) {
					prevMonth = '0' + prevMonth;
				}
				if (nextMonth < 10) {
					nextMonth = '0' + nextMonth;
				}
        		var calendar = dataObject.getCalendar();
				var conf = dataObject.getConf();
				var classes = conf.css.off + ' ' + conf.css.disabled;
				
				jQuery.post('{$ajaxUrl}', {
					'action' : 'ipenelo_calendar_get_events',
					'y' : year,
					'm' : month,
					'no_overlap' : '{$noOverlap}',
					'active_only' : '{$activeOnly}',
					'event_ids' : '{$eventIds}',
					'category_ids' : '{$categoryIds}'
				}, 
					function(data) {
					
						if (typeof(data) == 'object') {
							conf.fetchedData = data;

							calendar.find('#calweeks a.' + conf.css.off + '').each(function(index, value) {
								var id = jQuery(value).attr('href').replace('#', ''),
								isNext = id < 15,
								color;
								if (id < 10) {
									id = '0' + id;
								}
								if (isNext) {
									if (data[nextYear + '-' + nextMonth + '-' + id]) {
										//it is valid
										color = data[nextYear + '-' + nextMonth + '-' + id][0]['background']['value'];
										if (color.length > 7) {
											color = "url('" + color + "') no-repeat scroll center center transparent";
										}
										{$jQueryStyle}
										
										
									} else {
										jQuery(value).addClass(classes);
									}
	
	
								} else {
									if (data[prevYear + '-' + prevMonth + '-' + id]) {
										//it is valid
										color = data[prevYear + '-' + prevMonth + '-' + id][0]['background']['value'];
										if (color.length > 7) {
											color = "url('" + color + "') no-repeat scroll center center transparent";
										}
										{$jQueryStyle}
									} else {
										jQuery(value).addClass(classes);
									}
								}
							});
	
							
	
							calendar.find('#calweeks a:not([class]), #calweeks a[class="' + conf.css.focus + '"], #calweeks a[class="' + conf.css.sunday + '"]').each(function(index, value) {
								var id = jQuery(value).attr('href').replace('#', '');
								if (id < 10) {
									id = '0' + id;
								}
								if (data[year + '-' + month + '-' + id]) {
									//it is valid
									color = data[year + '-' + month + '-' + id][0]['background']['value'];
									if (color.length > 7) {
										color = "url('" + color + "') no-repeat scroll center center transparent";
									}
									{$jQueryStyle}
									if (makeClick && jQuery(value).attr('id') == conf.css.current) {
										jQuery(value).click();
									}
								} else {
									jQuery(value).addClass(classes);
								}
							});
						
						} else {
							conf.fetchedData = [];
						}



					},
					'json'
				);
				
			}
		
			// set initial value and show dateinput when page loads	
		}).data("dateinput").setValue(new Date());
		setTimeout(function() {jQuery("#ipenelo-calendar{$idcode}").click();}, 200);
		;
	});
/* ]]> */
</script>


EOT;

        return $html;
    }

    private static $_listJsLoaded;

    private function _getListJs() {
        if (self::$_listJsLoaded) {
            return '';
        }
        $ajaxUrl = $this->_ajaxUrl;
        self::$_listJsLoaded = true;
        $tbHeight = (int) Eabi_Ipenelo_Calendar::get('tb_height', 600);
        $tbWidth = (int) Eabi_Ipenelo_Calendar::get('tb_width', 1024);
        if ($tbHeight == 0) {
            $tbHeight = 600;
        }
        if ($tbWidth == 0) {
            $tbWidth = 1024;
        }
        return <<<EOT
<script type="text/javascript">
/* <![CDATA[ */
function ipenelo_calendar_register(a,b,c){jQuery.post("{$ajaxUrl}",{action:"ipenelo_calendar_get_registrants",event_id:b},function(b){if(typeof b=="object"){jQuery("#theform"+c).html(b["content"]);tb_show(a,"#TB_inline?height={$tbHeight}&width={$tbWidth}&inlineId=theform"+c,false);jQuery("#TB_window").unload(function(){jQuery("div.error").remove()})}},"json")}function eabi_ipenelo_calendar_submitData(a,b,c){var d={action:"ipenelo_calendar_get_registrants",event_id:a,register:"true"},e={};if(typeof c=="object"||typeof c=="array"){for(var f=0;f<c.length;f++){e[c[f]["name"]]=c[f]["value"]}}for(var f=0;f<b.length;f++){d[b[f]["name"]]=b[f]["value"]}d["payment_data"]=JSON.stringify(e);jQuery.post("{$ajaxUrl}",d,function(a){if(typeof a=="object"){jQuery("#TB_ajaxContent").html(a["content"])}},"json")}
    /* ]]> */
</script>

EOT;
        return <<<EOT
<script type="text/javascript">
/* <![CDATA[ */

function eabi_ipenelo_calendar_submitData(event_id, form_data, pmInfo) {
	var dataToSubmit = {
		'action': 'ipenelo_calendar_get_registrants',
		'event_id' : event_id,
		'register' : 'true'
	},
	payment_extra = {};
				
	if (typeof(pmInfo) == 'object' || typeof(pmInfo) == 'array') {
		for (var i = 0; i < pmInfo.length; i++) {
			payment_extra[pmInfo[i]['name']] = pmInfo[i]['value'];
		}
	}
				
	for (var i = 0; i < form_data.length; i++) {
		dataToSubmit[form_data[i]['name']] = form_data[i]['value'];
	}
	dataToSubmit['payment_data'] = JSON.stringify(payment_extra);
				
	jQuery.post('{$ajaxUrl}', 
		dataToSubmit
		,
		function(data) {
			if (typeof(data) == 'object') {
				jQuery("#TB_ajaxContent").html(data['content']);
			}
		},
		'json'
	);
}



function ipenelo_calendar_register(caption, event_id, id_code) {
	jQuery.post('{$ajaxUrl}', {
		'action' : 'ipenelo_calendar_get_registrants',
		'event_id' : event_id
		},
		function(data) {
			if (typeof(data) == 'object') {
				jQuery("#theform" + id_code).html(data['content']);
						
				tb_show(caption, '#TB_inline?height={$tbHeight}&width={$tbWidth}&inlineId=theform' + id_code ,false);
				jQuery('#TB_window').unload(function() {
					jQuery('div.error').remove();
					
					//reload page
				});
	

			}
		},
		'json'
	);

}

/* ]]> */
</script>

EOT;
    }

    /**
      Handler function to generate shortcodes in the admin edit mode.

     */
    public function insertAdminForm() {

        /*

          'type' => 'calendar',
          'size' => 'medium',

          'position' => 'bottom',
          'active_only' => 'false',
          'no_overlap' => 'false',

          'y' => 'current',
          'm' => 'current',
          'category_ids' => '',
          'event_ids' => '',


         */

        $html = '';
        $typeText = $this->__->l('Event list type');

        $typeCalendarText = $this->__->l('Calendar');
        $typeListText = $this->__->l('Table');

        $pleaseSelectText = $this->__->l(' -- Please select -- ');
        $sizeText = $this->__->l('Calendar size (Calendar view only)');
        $sizeSmallText = $this->__->l('Small');
        $sizeMediumText = $this->__->l('Medium');
        $sizeLargeText = $this->__->l('Large');

        $positionText = $this->__->l('Calendar event window position (Calendar view only)');

        $positionBottomText = $this->__->l('Bottom');
        $positionTopText = $this->__->l('Top');
        $positionLeftText = $this->__->l('Left');
        $positionRightText = $this->__->l('Right');

        $yesText = $this->__->l('Yes');
        $noText = $this->__->l('No');
        $activeOnlyText = $this->__->l('Show only active events');
        $overlapText = $this->__->l('Show events only within the selected month');

        $allText = $this->__->l('All events');
        $currentText = $this->__->l('Current');
        $nextText = $this->__->l('Next');
        $monthText = $this->__->l('Month');
        $yearText = $this->__->l('Year');
        $currentEventText = sprintf($this->__->l('Type \'current\' to get the event ID from URL request parameter. Example: %s/?event_id=x '), get_option('siteurl'));

        $eventIdsText = $this->__->l('Filter by event ids, comma separated');
        $categoryIdsText = $this->__->l('Filter by category ids, comma separated');
        $sendToEditorText = $this->__->l('Insert Calendar shortcode');

        $years = '';
        for ($i = (date('Y') - 10); $i < (date('Y') + 10); $i++) {
            $years .= '<option value="' . $i . '"';
            if ($i == date('Y')) {
//				$years .= ' selected="selected"';
            }
            $years .= '>';
            $years .= $i;
            $years .= '</option>';
        }

        $months = '';
        for ($i = 1; $i <= 12; $i++) {
            $months .= '<option value="' . $i . '"';
            if ($i == date('m')) {
//				$months .= ' selected="selected"';
            }
            $months .= '>';
            $months .= $i;
            $months .= '</option>';
        }



        $html .= <<<EOT
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_type">{$typeText}</label></th>
                <td>
                    <select name="ipenelo_calendar[type]" id="ipenelo_calendar_type" >
                    	<option value="calendar">{$typeCalendarText}</option>
                    	<option value="list">{$typeListText}</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_size">{$sizeText}</label></th>
                <td>
                    <select name="ipenelo_calendar[size]" id="ipenelo_calendar_size" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="small">{$sizeSmallText}</option>
                    	<option value="medium">{$sizeMediumText}</option>
                    	<option value="large">{$sizeLargeText}</option>
                    </select>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_position">{$positionText}</label></th>
                <td>
                    <select name="ipenelo_calendar[position]" id="ipenelo_calendar_position" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="bottom">{$positionBottomText}</option>
                    	<option value="top">{$positionTopText}</option>
                    	<option value="left">{$positionLeftText}</option>
                    	<option value="right">{$positionRightText}</option>
                    </select>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_active_only">{$activeOnlyText}</label></th>
                <td>
                    <select name="ipenelo_calendar[active_only]" id="ipenelo_calendar_active_only" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="true">{$yesText}</option>
                    	<option value="false">{$noText}</option>
                    </select>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_no_overlap">{$overlapText}</label></th>
                <td>
                    <select name="ipenelo_calendar[no_overlap]" id="ipenelo_calendar_no_overlap" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="true">{$yesText}</option>
                    	<option value="false">{$noText}</option>
                    </select>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_y">{$yearText}</label></th>
                <td>
                    <select name="ipenelo_calendar[y]" id="ipenelo_calendar_y" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="current">{$currentText}</option>
                    	<option value="all">{$allText}</option>
                    	{$years}
                    </select>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_m">{$monthText}</label></th>
                <td>
                    <select name="ipenelo_calendar[m]" id="ipenelo_calendar_m" >
                    	<option value="">{$pleaseSelectText}</option>
                    	<option value="current">{$currentText}</option>
                    	<option value="next">{$nextText}</option>
                    	<option value="all">{$allText}</option>
                    	{$months}
                    </select>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_category_ids">{$categoryIdsText}</label></th>
                <td>
                    <input type="text" size="40" style="width:95%;" name="ipenelo_calendar[category_ids]" id="ipenelo_calendar_category_ids" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="ipenelo_calendar_event_ids">{$eventIdsText}</label></th>
                <td>
                    <input type="text" size="40" style="width:95%;" name="ipenelo_calendar[event_ids]" id="ipenelo_calendar_event_ids" />
                    <br>
                    {$currentEventText}
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="button" onclick="return ipenelo_calendarAdmin.sendToEditor(this.form);" value="{$sendToEditorText}" />
        </p>

EOT;
        echo $html;
        return $html;
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
    protected $_template;
    
    public function setTemplate($template) {
        $this->_template = $template;
    }
    
    
}