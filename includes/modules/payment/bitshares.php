<?php

/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2011-2014 Bitshares
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
 

// On some installs, duplicate function definition errors were being thrown.
if(!(function_exists('tep_remove_order')))
{
    require 'bitshares/remove_order.php';
}

class bitshares
{

    /**
     * @var
     */
    public $code;

    /**
     * @var
     */
    public $title;

    /**
     * @var
     */
    public $description;

    /**
     * @var
     */
    public $enabled;

    /**
     * @var
     */
    private $invoice;

    /**
     */
    function bitshares ()
    {
        global $order;

        $this->code        = 'bitshares';
        $this->title       = MODULE_PAYMENT_BITSHARES_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_BITSHARES_TEXT_DESCRIPTION;
        $this->sort_order  = MODULE_PAYMENT_BITSHARES_SORT_ORDER;
        $this->enabled     = ((MODULE_PAYMENT_BITSHARES_STATUS == 'True') ? true : false);

        if ((int)MODULE_PAYMENT_BITSHARES_ORDER_STATUS_ID > 0)
        {
            $this->order_status = MODULE_PAYMENT_BITSHARES_ORDER_STATUS_ID;
            $payment='bitshares';
        }
        else if ($payment=='bitshares')
        {
            $payment='';
        }

        if (is_object($order))
        {
            $this->update_status();
        }

        $this->email_footer = MODULE_PAYMENT_BITSHARES_TEXT_EMAIL_FOOTER;
    }

    /**
     */
    function update_status () {
        global $order;

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_BITSHARES_ZONE > 0) )
        {
            $check_flag  = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . intval(MODULE_PAYMENT_BITSHARES_ZONE) . "' and zone_country_id = '" . intval($order->billing['country']['id']) . "' order by zone_id");

            while ($check = tep_db_fetch_array($check_query))
            {
                if ($check['zone_id'] < 1)
                {
                    $check_flag = true;
                    break;
                }
                elseif ($check['zone_id'] == $order->billing['zone_id'])
                {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false)
            {
                $this->enabled = false;
            }
        }

       
    }

    /**
     * @return boolean
     */
    function javascript_validation ()
    {
        return false;
    }

    /**
     * @return array
     */
    function selection ()
    {
        return array('id' => $this->code, 'module' => $this->title);
    }

    /**
     * @return boolean
     */
    function pre_confirmation_check ()
    {
        return false;
    }

    /**
     * @return boolean
     */
    function confirmation ()
    {
        return false;
    }

    /**
     * @return boolean
     */
    function process_button ()
    {
        return false;
    }

    /**
     * @return false
     */
    function before_process ()
    {
        global $insert_id, $order;
 
        // change order status to value selected by merchant
        tep_db_query("update ". TABLE_ORDERS. " set orders_status = " . intval(MODULE_PAYMENT_BITSHARES_UNPAID_STATUS_ID) . " where orders_id = ". intval($insert_id));
        return false;
    }

    /**
     * @return false
     */
    function after_process ()
    {
		global $insert_id, $order;  
		$url = 'bitshares\redirect2bitshares.php?order_id='.$insert_id.'&code='.$order->info['currency'].'&total='.$order->info['total']; 
		$_SESSION['cart']->reset(true);
		tep_redirect($url);
        return false;
    }

    /**
     * @return boolean
     */
    function get_error ()
    {
        return false;
    }

    /**
     * @return integer
     */
    function check ()
    {
        if (!isset($this->_check))
        {
            $check_query  = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_BITSHARES_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }

        return $this->_check;
    }

    /**
     */
    function install ()
    {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            ."values ('Enable Bitshares Module', 'MODULE_PAYMENT_BITSHARES_STATUS', 'False', 'Do you want to accept payments via Bitshares?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now());");
        
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            ."values ('Unpaid Order Status', 'MODULE_PAYMENT_BITSHARES_UNPAID_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Automatically set the status of unpaid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            ."values ('Paid Order Status', 'MODULE_PAYMENT_BITSHARES_PAID_STATUS_ID', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) "
            ."values ('Payment Zone', 'MODULE_PAYMENT_BITSHARES_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            ."values ('Sort Order of Display.', 'MODULE_PAYMENT_BITSHARES_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
    }

    /**
     */
    function remove ()
    {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * @return array
     */
    function keys()
    {
        return array(
            'MODULE_PAYMENT_BITSHARES_STATUS',
            'MODULE_PAYMENT_BITSHARES_UNPAID_STATUS_ID',
            'MODULE_PAYMENT_BITSHARES_PAID_STATUS_ID',
            'MODULE_PAYMENT_BITSHARES_SORT_ORDER',
            'MODULE_PAYMENT_BITSHARES_ZONE');
    }
}
