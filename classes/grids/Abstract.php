<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

/**
 *  This class aims to display an array of Eabi_Ipenelo_Calendar_Form_Abstract instances and display them in a grid.
 *  Grid, which allows the ordering of elements to be set, and a set of massactions can be added.
 *  
 * 
 *  
 */
abstract class Eabi_Ipenelo_Calendar_Grid_Abstract {
    protected $_db;

    /**
      Type of Form, each grid row represents to.
     * @var Eabi_Ipenelo_Calendar_Form_Abstract

     */
    protected $_formModel;

    /**
      Type of data, each grid row represents to.
      Is used to fetch the getTableName() method for instance.
     */
    protected $_dataModel;

    /**
      Title of the grid
     */
    protected $_title;

    /**
      Link, which will redirect to the edit screen of the grid row.
      %id% will be replaced with the correct id.

     */
    protected $_editLink;

    /**
      Holds the actual fetched results in order to display them in a grid.

     */
    protected $_results = array();

    /**
      Holds the fetched rendered results in a structure
      array[] = array(
      'label' =>
      'value' =>
      'valueText' =>
      );

     */
    protected $_renderedItems = array();
    protected $_ids = array();

    /**
      Holds the defined actions, which can be performed under each row.

     */
    protected $_actions = array();

    /**
      Holds the field_name's which are allowed to be sortable.

     */
    protected $_allowedOrderBys = array();

    /**
      Number of elements allowed to be displayed at once.
      Can be changed by configuration variable.

     */
    protected $_itemsPerPage = 20;

    /**
      Currently displayed page number.
      Numbering starts from 1

     */
    protected $_curPage;

    /**
      Currently selected order by defition.
      Defaults to blank.
     */
    protected $_currentOrderBy;

    /**
      Currently selected order direction.
      Works with $_curretOrderBy field.
     */
    protected $_currentOrderByDir;

    /**
      Holds the currently defined massactions.

     */
    protected $_massActions = array();

    /**

      Adds a quick filter to the top left on the grid.
      For example you can add status as a quick filter.
      And it generates links, which on clicking filter the grid.
     */
    protected $_quickFilters = array();

    /**
      Holds the buttons which are displayed right to next of the title.

     */
    protected $_buttons = array();

    /**
      Holds the array of Javascript snippets.
      Will be merged at the end of _render() method.

     */
    protected $_js = array();
    protected $_quickFilterResult;
    protected $_alwaysOnUrlParams = array();
    protected $_whereParts = array();

    /**
      If set to true, then $_renderedItems will be rendered with ID field of each row.
      If this property is set to false, then only the fields are rendered, which are allowed to be rendered.


     */
    protected $_renderedItemsWithId = false;

    /**
      Constructs the Grid which displays list of database table rows in administration panel.
      @param $formModel - Form type to display on each row (form will be set to readonly)
      @param $dataModel - Data type, which fills each row in the form.

      Both arguments should be blank, since they only represent the types.
      Actual data fetching itself is done via this class instance.


     */
    public function __construct(Eabi_Ipenelo_Calendar_Form_Abstract $formModel, $dataModel) {
        $this->_formModel = $formModel;
        $this->_dataModel = $dataModel;
    }
    
    public function setDb($db) {
        $this->_db = &$db;
    }

    public function addWherePart($name, $whereClause) {
        $this->_whereParts[$name] = $whereClause;
        return $this;
    }

    public function removeWherePart($name) {
        if (isset($this->_whereParts[$name])) {
            unset($this->_whereParts[$name]);
        }
        return $this;
    }

    public function addAlwaysOnUrlParam($name, $value) {
        $this->_alwaysOnUrlParams[$name] = $value;
        return $this;
    }

    public function removeAlwaysOnUrlParam($name) {
        if (isset($this->_alwaysOnUrlParam[$name])) {
            unset($this->_alwaysOnUrlParam[$name]);
        }
        return $this;
    }

    public function addMassAction($field_name, $label, $url, $confirm = null, $additional = null) {
        $this->_massActions[$field_name] = array(
            'field_name' => $field_name,
            'label' => $label,
            'url' => $url,
            'confirm' => $confirm,
            'additional' => $additional,
        );
        return $this;
    }

    public function removeMassAction($field_name) {
        if (isset($this->_massActions[$field_name])) {
            unset($this->_massActions[$field_name]);
        }
        return $this;
    }

    public function addQuickFilter($field_name, $options) {
        $this->_quickFilters[$field_name] = array(
            'field_name' => $field_name,
            'options' => $options,
        );
        return $this;
    }

    public function removeQuickFilter($field_name) {
        if (isset($this->_quickFilters[$field_name])) {
            unset($this->_quickFilters[$field_name]);
        }
        return $this;
    }

    public function addButton($name, $label, $href) {
        $this->_buttons[$name] = array(
            'name' => $name,
            'label' => $label,
            'href' => $href,
        );
        return $this;
    }

    public function removeButton($name) {
        if (isset($this->_buttons[$name])) {
            unset($this->_buttons[$name]);
        }
        return $this;
    }

    public function setEditLink($link) {
        $this->_editLink = $link;
    }

    public function setTitle($title) {
        $this->_title = $title;
    }

    public function setItemsPerPage($itemsPerPage) {
        $this->_itemsPerPage = (int) $itemsPerPage;
    }

    public function addOrderBy($field_name) {
        $this->_allowedOrderBys[$field_name] = $field_name;
        return $this;
    }

    public function removeOrderBy($field_name) {
        if (isset($this->_allowedOrderBys[$field_name])) {
            unset($this->_allowedOrderBys[$field_name]);
        }
        return $this;
    }

    abstract public function render();

    /**
     * Used to change the order of the fields displayed in the Grid
     * For example if you have fields field1, field2, field3 and you would like to order them in reverse then you would call:
     * $gridInstance->addFieldOrder('field3');
     * $gridInstance->addFieldOrder('field2');
     * $gridInstance->addFieldOrder('field1');
     * 
     * If the grid has more fields than you have specified the order for, then the unordered fields will be rendered after the ordered fields.
     * 
     * @param string $field_name
     */
    protected function addFieldOrder($field_name) {
        call_user_func(array(get_class($this->_formModel), 'addFieldOrder'), $field_name);
        return $this;
    }

    protected function restrictFieldOutput($field_name) {
        call_user_func(array(get_class($this->_formModel), 'addRestrictedOutput'), $field_name);
        return $this;
    }

    protected $_count;

    public function count() {
        if ($this->_count === null) {
            $query = "select count(*) from `" . $this->_dataModel->getTableName() . "` main_table ";
            //todo filter

            $whereParts = array();
            $currentFilter = array(
                'name' => null,
                'value' => null,
            );

            //process the instance whereparts

            foreach ($this->_whereParts as $wherePart) {
                $whereParts[] = $wherePart;
            }

            //process the filter
            if (isset($_GET['filter']) && isset($this->_quickFilters[$_GET['filter']])
                    && isset($_GET['value'])
                    && isset($this->_quickFilters[$_GET['filter']]['options'][$_GET['value']])) {
                $whereParts[] = 'main_table.' . $_GET['filter'] . ' = ' . $this->_db->escape($_GET['value']);

                $currentFilter = array(
                    'name' => $_GET['filter'],
                    'value' => $_GET['value'],
                );
            }

            $where = '';
            if (count($whereParts) > 0) {
                $where .= ' WHERE ' . implode(' AND ', $whereParts);
                $query .= $where;
            }


            $this->_count = (int) $this->_db->get_var($query);
        }
        return $this->_count;
    }

    protected function getResults() {

        $query = "select * from `" . $this->_dataModel->getTableName() . "` main_table ";

        $whereParts = array();
        $currentFilter = array(
            'name' => null,
            'value' => null,
        );

        //process the instance whereparts

        foreach ($this->_whereParts as $wherePart) {
            $whereParts[] = $wherePart;
        }

        //process the filter
        if (isset($_GET['filter']) && isset($this->_quickFilters[$_GET['filter']])
                && isset($_GET['value'])
                && isset($this->_quickFilters[$_GET['filter']]['options'][$_GET['value']])) {
            $whereParts[] = 'main_table.' . $_GET['filter'] . ' = ' . $this->_db->escape($_GET['value']);

            $currentFilter = array(
                'name' => $_GET['filter'],
                'value' => $_GET['value'],
            );
        }

        $where = '';
        if (count($whereParts) > 0) {
            $where .= ' WHERE ' . implode(' AND ', $whereParts);
            $query .= $where;
        }


        if (isset($_GET['orderby']) && isset($this->_allowedOrderBys[$_GET['orderby']])) {
            $this->_currentOrderBy = $_GET['orderby'];
            $this->_currentOrderByDir = 'asc';
            if (isset($_GET['order'])) {
                if ($_GET['order'] == 'asc') {
                    $query .= ' order by main_table.' . $_GET['orderby'] . ' asc';
                }
                if ($_GET['order'] == 'desc') {
                    $query .= ' order by main_table.' . $_GET['orderby'] . ' desc';
                    $this->_currentOrderByDir = 'desc';
                }
            }
        } else if ($this->_currentOrderBy != '' && in_array($this->_currentOrderByDir, array('asc', 'desct'))) {
            $query .= ' order by main_table.' . $this->_currentOrderBy . ' ' . $this->_currentOrderByDir;
        }
        $this->_curPage = 1;
//		echo $query;
        if ($this->_itemsPerPage > 0) {
            if (isset($_GET['paged']) && is_numeric($_GET['paged']) && $_GET['paged'] > 0) {
                $paged = (int) $_GET['paged'];
                $this->_curPage = $paged;
                $query .= ' limit ' . (($paged - 1) * $this->_itemsPerPage) . ', ' . $this->_itemsPerPage;
            } else {
                $query .= ' limit 0, ' . ($this->_itemsPerPage);
            }
        }
        $this->_results = $this->_db->get_results($query, OBJECT_K);


        //process the quick filter result
        if (count($this->_quickFilters) > 0 && $this->_quickFilterResult == null) {
            //add the all items variable
            $this->_quickFilterResult = array();


            foreach ($this->_quickFilters as $field_name => $quickFilter) {
                $this->_quickFilterResult[$field_name] = array();

                $this->_quickFilterResult[$field_name][] = array(
                    'label' => $this->__->l('All items'),
                    'href' => $this->getQuickFilterLink(),
                    'count' => (int) $this->_db->get_var("select count(*) from `" . $this->_dataModel->getTableName() . "` main_table " . $where),
                    'current' => $currentFilter['name'] == null,
                );


                //fetch the counts
                $query = "select count(*) as cnt, main_table." . $field_name . " as f from `" . $this->_dataModel->getTableName() . "` main_table " . $where . " group by " . $field_name;
                $filterResults = $this->_db->get_results($query, OBJECT);

                $indexedFilterResult = array();
                foreach ($filterResults as $filterResult) {
                    $indexedFilterResult[$filterResult->f] = $filterResult->cnt;
                }

                foreach ($quickFilter['options'] as $value => $label) {
                    if (isset($indexedFilterResult[$value])) {
                        $this->_quickFilterResult[$field_name][] = array(
                            'label' => $label,
                            'href' => $this->getQuickFilterLink($field_name, $value),
                            'count' => (int) $indexedFilterResult[$value],
                            'current' => $currentFilter['name'] == $field_name && $currentFilter['value'] == $value,
                        );
                    } else {
                        /*
                          $this->_quickFilterResult[] = array(
                          'label' => $label,
                          'href' => $this->getQuickFilterLink($field_name, $value),
                          'count' => 0,
                          'current' => $currentFilter['name'] == $field_name && $currentFilter['value'] == $value,
                          );
                         */
                    }
                }
            }
        }





        return $this->_results;
    }

    public function addAction($name, $title, $link, $params = array()) {
        $this->_actions[$name] = array(
            'name' => $name,
            'title' => $title,
            'link' => $link,
            'params' => $params,
        );
        return $this;
    }

    private function getLink($suffix) {
        global $plugin_page;


        $href = admin_url('admin.php?page=' . $plugin_page);
        $appends = array();
        foreach ($this->_alwaysOnUrlParams as $key => $value) {
            $appends[] = htmlspecialchars($key) . '=' . htmlspecialchars($value);
        }
        if (count($this->_alwaysOnUrlParams)) {
            if (strpos($href, '?') > 0) {
                $href .= '&amp;' . implode('&amp;', $appends);
            } else {
                $href .= '?' . implode('&amp;', $appends);
            }
        }
        if (strpos($href, '?') > 0) {
            $href .= '&amp;' . $suffix;
        } else {
            $href .= '?' . $suffix;
        }
        return $href;
    }

    protected function getQuickFilterLink($field_name = null, $value = null) {
        $state = '';
        if ($value == null) {
            $value = '';
        }
        if ($field_name != null) {
            $state = 'filter=' . $field_name . '&amp;value=' . htmlspecialchars($value);
        }
        return $this->getLink($state);
    }

    protected function getPageLink($pageNum) {
        $pageNum = (int) $pageNum;
        if ($pageNum < 1) {
            $pageNum = 1;
        }
        if ($pageNum > ceil($this->count() / $this->_itemsPerPage)) {
            $pageNum = ceil($this->count() / $this->_itemsPerPage);
        }
        $query = 'paged=' . $pageNum;
        if ($this->_currentOrderBy != null) {
            $query .= 'orderby=' . $this->_currentOrderBy;
            $query .= 'order=' . $this->_currentOrderByDir;
        }
        if (isset($_GET['filter']) && isset($_GET['value'])) {
            $query .= '&amp;filter=' . htmlspecialchars($_GET['filter']);
            $query .= '&amp;value=' . htmlspecialchars($_GET['value']);
        }

        return $this->getLink($query);
    }

    protected function getOrderByLink($field_name) {
        $query = '';
        if (isset($_GET['filter']) && isset($_GET['value'])) {
            $query .= '&amp;filter=' . htmlspecialchars($_GET['filter']);
            $query .= '&amp;value=' . htmlspecialchars($_GET['value']);
        }

        if (isset($_GET['orderby']) && isset($this->_allowedOrderBys[$_GET['orderby']])
                && $_GET['orderby'] == $field_name) {
            if (isset($_GET['order'])) {
                if ($_GET['order'] == 'asc') {
                    return $this->getLink('orderby=' . $field_name . '&amp;order=desc' . $query);
                }
                if ($_GET['order'] == 'desc') {
                    return $this->getLink('orderby=' . $field_name . '&amp;order=asc' . $query);
                }
            }
        }
        return $this->getLink('orderby=' . $field_name . '&amp;order=asc' . $query);
    }

    protected function getOrderByCss($field_name) {
        if (isset($_GET['orderby']) && isset($this->_allowedOrderBys[$_GET['orderby']])
                && $_GET['orderby'] == $field_name) {
            if (isset($_GET['order'])) {
                if ($_GET['order'] == 'asc') {
                    return 'sorted asc';
                }
                if ($_GET['order'] == 'desc') {
                    return 'sorted desc';
                }
            }
        }
        return 'sortable desc';
    }

    protected function _afterFormInstance(Eabi_Ipenelo_Calendar_Form_Abstract $form) {
        
    }

    protected function _render() {

        $results = $this->getResults();
        $html = '';

        $html .= '<div class="wrap">';
        $html .= "\r\n";

        //title icon
        $html .= '<div class="icon32" id="icon-options-general"><br></div>';
        $html .= "\r\n";

        //title itself
        $html .= '<h2>' . htmlspecialchars($this->_title);

        foreach ($this->_buttons as $button) {
            $html .= '<a href="' . htmlspecialchars($button['href']) . '" class="add-new-h2">';
            $html .= htmlspecialchars($button['label']);
            $html .= '</a>';
        }

        $html .= '</h2>';

        $html .= "\r\n";
        $html .= Eabi_Ipenelo_Calendar::displayErrors();
        $html .= Eabi_Ipenelo_Calendar::displayMessages();

        if ($this->_quickFilterResult != null) {
            $html .= '<ul class="subsubsub">';

            foreach ($this->_quickFilterResult as $filterResultCategory) {
                $filterHtml = array();
                foreach ($filterResultCategory as $filterResult) {
                    $currentClass = '';
                    if ($filterResult['current']) {
                        $currentClass = 'current';
                    }
                    $filterHtml[] = '<li>'
                            . '<a href="' . $filterResult['href'] . '" class="' . $currentClass . '">'
                            . $filterResult['label'] . ' '
                            . '<span class="count">'
                            . '(' . $filterResult['count'] . ')'
                            . '</span>'
                            . '</a>'
                            . '</li>'
                    ;
                }
                $html .= implode(' | ', $filterHtml);
                $html .= '<br/>';
            }

            $html .= '</ul>';
        }


        $html .= '<form id="posts-filter" method="get" action="">';

        global $plugin_page;

        if ($plugin_page != '') {
            $curPage = $plugin_page;
            $html .= '<input type="hidden" name="page" value="' . htmlspecialchars($curPage) . '" />';
        }


        $html .= '<div class="tablenav top">';

        //massactions go here
        $html .= '<div class="alignleft actions">';


        if (count($this->_massActions) > 0) {
            $js = <<<EOT
	function eabi_ipenelo_massaction_select_handle(elem) {
			var elemData = {}, select = jQuery(elem), ids = [];

EOT;
            $html .= '<select name="action" onchange="eabi_ipenelo_massaction_select_handle(this);" id="massAction">';
            $html .= '<option value="" selected="selected">' . $this->__->l('Mass actions') . '</option>';
            foreach ($this->_massActions as $field_name => $massAction) {
                $html .= '<option value="' . htmlspecialchars($field_name) . '"';
                $html .= '>';
                $html .= htmlspecialchars($massAction['label']);
                $html .= '</option>';

                $url = addslashes($massAction['url']);

                $additional = "'additional': false";

                if ($massAction['additional'] != '') {
                    $additional = "'additional' : '" . addslashes($massAction['additional']) . "'";
                }

                if ($massAction['confirm'] != '') {
                    $confirm = addslashes($massAction['confirm']);
                    //confirm
                    $js .= <<<EOT

	elemData['{$field_name}'] = { 'confirm' : '{$confirm}', 'url' : '{$url}', {$additional} };

EOT;
                } else {
                    $js .= <<<EOT

	elemData['{$field_name}'] = { 'confirm' : null, 'url' : '{$url}', {$additional} };

EOT;
                }

                //additional
            }
            $html .= '</select>';
            $html .= '<input type="submit" value="' . $this->__->l('Apply') . '" class="button-secondary action" id="doAction" name="" onclick="if (jQuery(\'#massAction\').val() == \'\') { alert(\'' . addslashes($this->__->l('Select action first')) . '\'); return false; }" />';
            $selectIds = addslashes($this->__->l('Please select items first!'));
            $selectStatuses = addslashes($this->__->l('%status% is required!'));


            $js .= <<<EOT
	
	//remove previous click handler
	jQuery('#doAction').unbind('click');
	
	jQuery('#massActionExtra').remove();
	//add after here
	if (elemData[select.val()] && elemData[select.val()]['additional']) {
		select.after(elemData[select.val()]['additional']);
	}
	

	//add the new clik handler
	jQuery('#doAction').click(function(e) {
		ids = [];
		jQuery('[name="post[]"]:checked').each(function(index, element) {
			ids.push(jQuery(element).val());
		});
		if (ids.length == 0) {
			alert('{$selectIds}');
			return e.preventDefault();
		}
		if (elemData[select.val()] && elemData[select.val()]['additional']) {
			if (jQuery('#massActionExtra').val() == '') {
				alert('{$selectStatuses}'.replace('%status%', jQuery('#massActionExtra option[value=""]').text()));
				return e.preventDefault();
			}
		}
		
		return e;
		
	});
	

		//add click handler
		jQuery('#doAction').click(function(e) {
			var targetLink = false;
			if (!e.isDefaultPrevented()) {
				if (elemData[select.val()] && elemData[select.val()]['confirm']) {

					var confirmR = confirm(elemData[select.val()]['confirm']);
					if (confirmR) {
					} else {
						return e.preventDefault();
					}

				}
				if (elemData[select.val()]) {
					//get selected ids, send to url
					targetLink = elemData[select.val()]['url'].replace('%id%', ids.join(','));
					if (elemData[select.val()] && elemData[select.val()]['additional']) {
						targetLink += '&';
						targetLink += jQuery('#massActionExtra').attr('name');
						targetLink += '=';
						targetLink += jQuery('#massActionExtra').val();
					}
					document.location.href = targetLink;
					return e.preventDefault();



				}


			}
		});



}

EOT;

            $this->_js[] = '<script type="text/javascript">
				//<![CDATA[
	' . $js . '
	//]]>
</script>';
        }




        $html .= '</div>'; //end of massactions

        $html .= '<div class="tablenav-pages">';
        $html .= '<span class="displaying-num">';

        if ($this->count() == 1) {
            $html .= $this->count() . ' ' . $this->__->l('Element');
        } else {
            $html .= $this->count() . ' ' . $this->__->l('Elements');
        }

        $html .= '</span>';

        if ($this->_itemsPerPage > 0 && $this->count() > $this->_itemsPerPage) {
            $html .= '<span class="pagination-links">';

            $firstDisabled = '';
            $previousDisabled = '';
            $nextDisabled = '';
            $lastDisabled = '';

            $numPages = ceil($this->count() / $this->_itemsPerPage);
            if ($this->_curPage <= 1) {
                $firstDisabled = 'disabled';
                $previousDisabled = 'disabled';
            }
            if ($this->_curPage >= $numPages) {
                $lastDisabled = 'disabled';
                $nextDisabled = 'disabled';
            }


            $html .= '<a href="' . $this->getPageLink(1) . '" class="first-page ' . $firstDisabled . '" title="' . htmlspecialchars($this->__->l('Go to first page')) . '">&laquo;&laquo;</a>';

            $html .= '<a href="' . $this->getPageLink($this->_curPage - 1) . '" class="prev-page ' . $previousDisabled . '" title="' . htmlspecialchars($this->__->l('Go to previous page')) . '">&laquo;</a>';

            $html .= '<span class="paging-input">';

            $html .= '<input type="text" size="1" name="paged" value="' . $this->_curPage . '" title="' . $this->__->l('Page') . '"/>';

            $html .= sprintf($this->__->l(' of <span class="total-pages">%d</span>'), $numPages);

            $html .= '</span>';




            $html .= '<a href="' . $this->getPageLink($this->_curPage + 1) . '" class="next-page ' . $nextDisabled . '" title="' . htmlspecialchars($this->__->l('Go to next page')) . '">&raquo;</a>';
            $html .= '<a href="' . $this->getPageLink($numPages) . '" class="last-page ' . $lastDisabled . '" title="' . htmlspecialchars($this->__->l('Go to last page')) . '">&raquo;&raquo;</a>';

            $html .= '</span>';
        }



        $html .= '</div>'; //end of div.tablenav top

        $html .= '<br class="clear">';
        $html .= '</div>';


        //form

        $html .= '<table cellspacing="0" class="wp-list-table widefat fixed posts">';

        $rowHeader = $this->_formModel->newInstance(array());

        $this->_afterFormInstance($rowHeader);

        $rowHeader->render();
        $html .= '<thead>';
        $html .= '<tr>';

        if (count($this->_massActions) > 0) {
            $html .= '<th scope="col" class="manage-column check-column">';
            $html .= '<input type="checkbox" onclick="if (this.checked) { jQuery(\'[name=\\\'post[]\\\']\').attr(\'checked\', \'checked\'); } else { jQuery(\'[name=\\\'post[]\\\']\').removeAttr(\'checked\') };">';
            $html .= '</th>';
        }


        //make the first one wider
        $width = 'width="35%"';

        foreach ($rowHeader->getLabels() as $field_name => $label) {
            if (isset($this->_allowedOrderBys[$field_name])) {
                $dir = $this->getOrderByCss($field_name);
                $html .= '<th class="manage-column ' . $dir . '" scope="col" ' . $width . '>';
                $html .= '<a href="' . $this->getOrderByLink($field_name) . '">';
                $html .= '<span>' . $label . '</span>';
                $html .= '<span class="sorting-indicator"></span>';
                $html .= '</a>';
                $html .= '</th>';
            } else {
                $html .= '<th class="manage-column" scope="col" ' . $width . '>' . $label . '</th>';
            }
            $width = '';
        }
        $html .= '</tr>';
        $html .= '</thead>';


        $html .= '<tfoot>';
        $html .= '<tr>';
        if (count($this->_massActions) > 0) {
            $html .= '<th scope="col" class="manage-column check-column">';
            $html .= '<input type="checkbox" onclick="if (this.checked) { jQuery(\'[name=\\\'post[]\\\']\').attr(\'checked\', \'checked\'); } else { jQuery(\'[name=\\\'post[]\\\']\').removeAttr(\'checked\') };">';
            $html .= '</th>';
        }
        foreach ($rowHeader->getLabels() as $field_name => $label) {
            if (isset($this->_allowedOrderBys[$field_name])) {
                $dir = $this->getOrderByCss($field_name);
                $html .= '<th class="manage-column ' . $dir . '" scope="col" ' . $width . '>';
                $html .= '<a href="' . $this->getOrderByLink($field_name) . '">';
                $html .= '<span>' . $label . '</span>';
                $html .= '<span class="sorting-indicator"></span>';
                $html .= '</a>';
                $html .= '</th>';
            } else {
                $html .= '<th class="manage-column" scope="col" ' . $width . '>' . $label . '</th>';
            }
//			$html .= '<th class="manage-column" scope="col">'.$label.'</th>';
        }
        $html .= '</tr>';
        $html .= '</tfoot>';


        $html .= '<tbody>';

        $firstElementName = array_keys($rowHeader->getLabels());
        $firstElementName = reset($firstElementName);


        foreach ($results as $id => $result) {
            $this->_ids[$id] = $result->id;

            $rowItem = $this->_formModel->newInstance((array) $result);
            $rowItem->setReadOnly(true);
            $rowItem->setFormElementHtml('<td>${INPUT}</td>' . "\n\r");
            $this->_afterFormInstance($rowItem);

            $titleHtml = '<td class=""><strong>';
            $titleHtml .= '<a class="row-title" href="' . admin_url(str_replace('%id%', $result->id, $this->_editLink)) . '" title="' . $this->__->l('Edit') . ' ' . htmlspecialchars($result->$firstElementName) . '">';
            $titleHtml .= '${INPUT}';
            $titleHtml .= '</a>';
            $titleHtml .= '</strong>';

            if (count($this->_actions) > 0) {
                $titleHtml .= '<div class="row-actions">';
                $actionsHtml = array();
                foreach ($this->_actions as $name => $action) {
                    $str = '';
                    $str .= '<span class="' . $name . '">';
                    $str .= '<a href="' . admin_url(str_replace('%id%', $result->id, $action['link'])) . '" title="' . htmlspecialchars($result->$firstElementName) . '">';
                    $str .= htmlspecialchars($action['title']);
                    $str .= '</a>';
                    $str .= '</span>';
                    $actionsHtml[] = $str;
                }
                $titleHtml .= implode(' | ', $actionsHtml);

                $titleHtml .= '</div>';
            }

            $titleHtml .= '</td>';

            $rowItem->setFormElementHtml($titleHtml . "\r\n", $firstElementName);


            $rowItem->setRenderOnlyCore(true);
            $html .= '<tr>';
            if (count($this->_massActions) > 0) {
                $html .= '<th scope="row">';
                $html .= '<input type="checkbox" value="' . $result->id . '" name="post[]">';
                $html .= '</th>';
            }

            //set the _renderedItems

            $html .= $rowItem->render();
            $this->_renderedItems[] = $rowItem->getRenderedFieldsRaw($this->_renderedItemsWithId);
            $html .= '</tr>';
        }

        if (count($results) == 0) {
            $html .= '<tr class="no-items"><td colspan="' . count($rowHeader->getLabels()) . '" class="colspanchange">';
            $html .= htmlspecialchars($this->__->l('No items found'));
            $html .= '</td></tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</form>';

        foreach ($this->_js as $js) {
            $html .= $js;
        }

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
    
}