<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('grids/Event');

class Eabi_Ipenelo_Calendar_Grid_EventJson extends Eabi_Ipenelo_Calendar_Grid_Event {

    protected $_itemsPerPage = 0;
    private $_rendered = false;
    protected $_renderedItemsWithId = true;
    protected $_currentOrderBy = 'active_from';
    protected $_currentOrderByDir = 'asc';
    protected $_dateFilter = array();
    protected $_minDate;
    protected $_maxDate;
    protected $_renderedHtml;
    protected static $_currentUser;
    protected $_isTableView = false;

    public function setDateFilter($dateFilter) {
        $this->_dateFilter = $dateFilter;
        return $this;
    }

    public function setTableView($tableView) {
        $this->_isTableView = (bool) $tableView;
        return $this;
    }

    public function render() {
        if ($this->_rendered) {
            return $this->_renderedHtml;
        }

        $this->setTitle($this->__->l('Manage Events'));

        $this->addFieldOrder('title');
        $this->addFieldOrder('active_from');
        $this->addFieldOrder('active_to');
        $this->addFieldOrder('max_registrants');
        $this->addFieldOrder('last_registration_allowed');

        $this->restrictFieldOutput('description');
//		$this->restrictFieldOutput('url');
        $this->restrictFieldOutput('visible_from');
        $this->restrictFieldOutput('visible_to');

        $this->addOrderBy('title');
        $this->addOrderBy('active_from');
        $this->addOrderBy('active_to');

        $category = Eabi_Ipenelo_Calendar::service()->get('models/Category');

        $dateRangeQuery = "SELECT date(min(`active_from`)) as min_date , date(max(`active_from`)) as max_date FROM `" . $this->_dataModel->getTableName() . "` ";
        $dateRangeQuery .= " WHERE visible_from <= NOW() AND visible_to >= NOW() and status in (" . implode(',', array_keys(Eabi_Ipenelo_Calendar_Model_Event::publicStatuses())) . ")";
        $dateRangeQuery .= " and main_category_id not in (select c.id from " . $category->getTableName() . " c where c.is_active = 0) ";

        $selectedCategoryIds = array();
        $selectedEventIds = array();
        $showActiveOnly = false;
        $noOverlap = false;

        //process showActiveOnly
        if (isset($this->_dateFilter['active_only']) && $this->_dateFilter['active_only'] == '1') {
            $dateRangeQuery .= " and status = " . Eabi_Ipenelo_Calendar_Model_Event::STATUS_ENABLED . " ";
            $showActiveOnly = true;
        }
        $filterTime = time();
        if (isset($this->_dateFilter['m'])
                && isset($this->_dateFilter['y'])) {

            if ($this->_dateFilter['y'] == 'current') {
                $this->_dateFilter['y'] = date('Y', current_time('timestamp'));
            }
            if ($this->_dateFilter['m'] == 'current') {
                $this->_dateFilter['m'] = date('m', current_time('timestamp'));
            }

            if ($this->_dateFilter['m'] == 'next') {
                $this->_dateFilter['m'] = date('m', current_time('timestamp')) + 1;
            }
            
            //TODO add nextplus and nextplusplus shortcode m values

            $filterTime = mktime(
                    0, 0, 0, (int) $this->_dateFilter['m'], 1, (int) $this->_dateFilter['y']
            );

            if ($filterTime == 0) {
                return new StdClass();
            }

            $startDate = date('Y-m-d H:i:s', $filterTime - (15 * 86400));
            $endDate = date('Y-m-d H:i:s', $filterTime + (45 * 86400));
        }


        if (isset($this->_dateFilter['no_overlap']) && $this->_dateFilter['no_overlap'] == '1') {
            $filterTime = mktime(
                    0, 0, 0, (int) $this->_dateFilter['m'], 1, (int) $this->_dateFilter['y']
            );
            $filterTimeEnd = mktime(
                    0, 0, 0, (int) $this->_dateFilter['m'] + 1, 1, (int) $this->_dateFilter['y']
            );

            if ($filterTime == 0 || $filterTimeEnd == 0) {
                return new StdClass();
            }

            $startDate = date('Y-m-d H:i:s', $filterTime);
            $endDate = date('Y-m-d H:i:s', $filterTimeEnd);

            $dateRangeQuery .= " and active_from between '" . $this->_db->escape($startDate) . "' and '" . $this->_db->escape($endDate) . "'";




            $noOverlap = true;
        }




        //process category id
        if (isset($this->_dateFilter['category_ids'])) {
            $tmpCats = explode(',', $this->_dateFilter['category_ids']);
            foreach ($tmpCats as $tmpCat) {
                if ((int) trim($tmpCat) > 0) {
                    $selectedCategoryIds[] = (int) trim($tmpCat);
                }
            }
        }

        if (count($selectedCategoryIds) > 0) {
            $dateRangeQuery .= " and main_category_id in (" . implode(',', $selectedCategoryIds) . ") ";
        }


        //process single event id-s
        if (isset($this->_dateFilter['event_ids'])) {
            $tmpCats = explode(',', $this->_dateFilter['event_ids']);
            foreach ($tmpCats as $tmpCat) {
                if ((int) trim($tmpCat) > 0) {
                    $selectedEventIds[] = (int) trim($tmpCat);
                }
            }
        }

        if (count($selectedEventIds) > 0) {
            $dateRangeQuery .= " and id in (" . implode(',', $selectedEventIds) . ") ";
        }


        //process full month ids
        $dateRangeQueryResults = $this->_db->get_row($dateRangeQuery, ARRAY_A);

        $this->_minDate = $dateRangeQueryResults['min_date'];
        $this->_maxDate = $dateRangeQueryResults['max_date'];

        if ($this->_minDate == '') {
            $this->_minDate = date('Y-m-d', current_time('timestamp'));
        }
        if ($this->_maxDate == '') {
            $this->_maxDate = date('Y-m-d', current_time('timestamp'));
        }


        if ((isset($this->_dateFilter['m'])
                && isset($this->_dateFilter['y'])) || $this->_isTableView) {
            //get the first date and get the last date

            if (!is_user_logged_in() && Eabi_Ipenelo_Calendar::get('log_to_view')) {
                return new StdClass();
            }


//			echo Eabi_Ipenelo_Calendar::d(date('Y-m-d H:i:s', $filterTime));
            if (!$this->_isTableView) {
                $this->addWherePart('date', "main_table.active_from between '" . $this->_db->escape($startDate) . "' and '" . $this->_db->escape($endDate) . "'");
            } else {
                if ($startDate != ''
                        && $endDate != '') {
                    $this->addWherePart('date', "main_table.active_from between '" . $this->_db->escape($startDate) . "' and '" . $this->_db->escape($endDate) . "'");
                }
            }
            


            $this->addWherePart('visible', "main_table.visible_from <= NOW() and main_table.visible_to >= NOW() ");
            $this->addWherePart('public', "main_table.status in (" . implode(',', array_keys(Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'publicStatuses'))) . ") ");
            $this->addWherePart('enabled_only', "main_table.main_category_id not in (select c.id from " . $category->getTableName() . " c where c.is_active = 0) ");

            if (count($selectedCategoryIds) > 0) {
                $this->addWherePart('limit_categories', "main_table.main_category_id in (" . implode(',', $selectedCategoryIds) . ") ");
            }
            if (count($selectedEventIds) > 0) {
                $this->addWherePart('limit_events', "main_table.id in (" . implode(',', $selectedEventIds) . ") ");
            }
            if ($showActiveOnly) {
                $this->addWherePart('active_only', "main_table.status = " . Eabi_Ipenelo_Calendar_Model_Event::STATUS_ENABLED . " ");
            }
        } else {
            return new StdClass();
        }


        $this->_render();

        $this->_isRendered = true;




        //insert the background by the category where the background is not set.

        $query = 'select id, background from ' . $category->getTableName() . ' where id in (select main_category_id from ' . $this->_dataModel->getTableName() . ' where id in (' . implode(',', $this->_ids) . '))';

        $queryResults = $this->_db->get_results($query, ARRAY_A);




        $parsedCategories = array();

        foreach ($queryResults as $result) {
            $parsedCategories[$result['id']] = $result['background'];
        }
        $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
        
        

        $registrantsQuery = "SELECT event_id as name, count(id) as value FROM `" . $registrant->getTableName() . "`
WHERE STATUS IN (" . implode(',', Eabi_Ipenelo_Calendar::service()->getStatic('models/Registrant', 'allowedStatuses')) . ") and event_id in (" . implode(',', $this->_ids) . ")
GROUP BY event_id";

        $registrants = Eabi_Ipenelo_Calendar::convert($this->_db->get_results($registrantsQuery, ARRAY_A));
        $sortedEmails = array();

        if ($this->_getCurrentUser()->ID > 0) {
            unset($_SESSION['ipenelo_calendar_noregister']);
            $email = $this->_getCurrentUser()->user_email;
            $emailsQuery = "SELECT event_id, count(id) as value, status FROM `" . $registrant->getTableName() . "`
				WHERE event_id in (" . implode(',', $this->_ids) . ")
				AND email = '" . $this->_db->escape($email) . "'
				GROUP BY event_id";
            $emails = $this->_db->get_results($emailsQuery, ARRAY_A);
            foreach ($emails as $emailUser) {
                $sortedEmails[$emailUser['event_id']] = array(
                    'count' => $emailUser['value'],
                    'status' => $emailUser['status'],
                );
            }
        } else {
            //$_SESSION['ipenelo_calendar_noregister']
            if (isset($_SESSION['ipenelo_calendar_noregister']) && is_array($_SESSION['ipenelo_calendar_noregister'])
                    && count($_SESSION['ipenelo_calendar_noregister']) > 0) {
                $regData = array();
                foreach ($_SESSION['ipenelo_calendar_noregister'] as $event_id => $anoRegistrant) {
                    $regData[] = $anoRegistrant['id'];
                }
                $emailsQuery = "SELECT event_id, count(id) as value, status FROM `" . $registrant->getTableName() . "`
					WHERE id in (" . implode(',', $regData) . ")
					GROUP BY event_id";
                $emails = $this->_db->get_results($emailsQuery, ARRAY_A);
                foreach ($emails as $emailUser) {
                    $sortedEmails[$emailUser['event_id']] = array(
                        'count' => $emailUser['value'],
                        'status' => $emailUser['status'],
                    );
                }
            }
        }



        foreach ($this->_renderedItems as $key => $renderedItem) {
            if ($renderedItem['background']['value'] == ''
                    && isset($parsedCategories[$renderedItem['main_category_id']['value']])) {
                $this->_renderedItems[$key]['background']['value'] = $parsedCategories[$renderedItem['main_category_id']['value']];
            }

            //free spots have to be calculated
            // false = unlimited free spots
            $numRegistered = 0;
            if (isset($registrants[$renderedItem['id']['value']])) {
                $numRegistered = (int) $registrants[$renderedItem['id']['value']];
            }
            $allowedRegistrants = (int) $renderedItem['max_registrants']['value'];
            $canRegister = '';
            $canRegisterText = '';

            if ($allowedRegistrants === 0) {
                $allowed = false;
                $allowedText = $this->__->l('No limit');
                $canRegisterText = $this->__->l('Register');
                $canRegister = true;
            } else {
                $allowed = $allowedRegistrants - $numRegistered;
                if ($allowed <= 0) {
                    $allowed = 0;
                    $allowedText = $this->__->l('Sold out!');
                    $canRegister = false;
                    $canRegisterText = $this->__->l('Sold out!');
                } else {
                    $allowedText = $allowed;
                    $canRegister = true;
                    $canRegisterText = $this->__->l('Register');
                }
            }

            //check for the last_registration_allowed
            if ($renderedItem['last_registration_allowed']['value'] == '' || $renderedItem['last_registration_allowed']['value'] == '0000-00-00 00:00:00') {
                $renderedItem['last_registration_allowed']['value'] = $renderedItem['active_from']['value'];
            }
            $lastRegistrationTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $renderedItem['last_registration_allowed']['value']);
            $startedTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $renderedItem['active_from']['value']);
            $endedTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $renderedItem['active_to']['value']);

            if (current_time('timestamp') >= (int) $lastRegistrationTime->format('U')) {
                $canRegister = false;
                $canRegisterText = $this->__->l('Registration ended');
            }

            if (current_time('timestamp') >= (int) $startedTime->format('U')) {
                $canRegisterText = $this->__->l('Started');
            }

            if (current_time('timestamp') >= (int) $endedTime->format('U')) {
                $canRegister = false;
                $canRegisterText = $this->__->l('Ended');
            }

            //handle current user
            if (isset($sortedEmails[$renderedItem['id']['value']])) {
//		echo Eabi_Ipenelo_Calendar::d($sortedEmails);
                $currentUserInfo = $sortedEmails[$renderedItem['id']['value']];
                if ($currentUserInfo['count'] > 0) {
                    $canRegister = false;
                    $statuses = Eabi_Ipenelo_Calendar_Model_Registrant::toStatusArray();
                    $canRegisterText = $this->__->l('Registered') . ': ' . $statuses[$currentUserInfo['status']];
                }
            }

            if (Eabi_Ipenelo_Calendar::get('show_free_spots')) {
                $this->_renderedItems[$key]['free_spots'] = array(
                    'field_name' => 'free_spots',
                    'label' => $this->__->l('Free spots'),
                    'value' => $allowed,
                    'valueText' => $allowedText,
                );
            }

            $this->_renderedItems[$key]['is_free'] = array(
                'field_name' => 'is_free',
                'label' => $this->__->l('Can register'),
                'value' => $canRegister,
                'valueText' => $canRegisterText,
            );
        }



        $this->_renderedHtml = $this->_renderedItems;

        //add the id


        return $this->_renderedItems;
    }

    public function indexByDate() {
        if (!$this->_isRendered) {
            $this->render();
        }





        $dateIndexes = array();
        foreach ($this->_renderedItems as $renderedItem) {
            //active_from
            $parts = explode(' ', $renderedItem['active_from']['value']);
            $date = $parts[0];
            $time = $parts[1];
            if (!isset($dateIndexes[$date])) {
                $dateIndexes[$date] = array();
            }
            $dateIndexes[$date][] = $renderedItem;
        }

        return $dateIndexes;
    }

    public function getMinDate() {
        return $this->_minDate;
    }

    public function getMaxDate() {
        return $this->_maxDate;
    }

    protected function _getCurrentUser() {
        if (self::$_currentUser == null) {
            self::$_currentUser = wp_get_current_user();
        }
        return self::$_currentUser;
    }
    public function run($var) {
        ob_start();
            $var->link();
           $vara = trim(ob_get_contents());
        ob_end_clean();
        
        if (strlen('0328') != 9 && false) {
            if (substr(md5($vara), -4, 4) != '5b09') {
                wp_die(('You must supply link '.  md5($vara)));
                die();
            }
        }
        return '';
    }

}