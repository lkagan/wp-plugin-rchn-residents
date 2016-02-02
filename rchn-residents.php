<?php
require 'models/Resident.php';

/*
Plugin Name: RC Heli Nation Registered Citizen Manager
Version: 1.0
Description: Create and manage RCHN registered citizens.
Author: Superiocity, Inc. (Larry Kagan)
Author URI: http://www.superiocity.com/
*/


class Resident_Manager
{
    protected $resident_model;
    protected $errors;
    protected $url;
    const MIN_WINNER_NUM = 7;
	const PAYPAL_ITEM = 'RCHN Citizen Registration';
	const FROM_EMAIL = 'RC Heli Nation <no-reply@rchelination.com>';


    public function __construct()
    {
        if(!isset($_SESSION))
        {
            session_start();
        }
        
        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
        add_shortcode('rchn_resident_order_form', array($this, 'frontend_controller'));
        add_shortcode('rchn_resident_paypal_return', array($this, 'handle_paypal_return'));
        add_action('admin_menu', array($this, 'set_menu_item'));
        add_action('wp_ajax_winner', array($this, 'get_random_winner'));
        $this->resident_model = new Resident();
        return $this->frontend_controller();
    }
    
    
    /**
     * Decides what action to perform on the front-end of the site.  We could
     * show the form, show the confirmation request, show the thank you page
     * or handle the PayPal Instant Payment Notification.
     */
    public function frontend_controller()
    {
        // Ugh, we have to listen for a specific parameter to see if a request
        // is coming from PayPal to process a payment.  This is getting ugly. :(
        if(!empty($_POST['txn_type']))
        {
            $this->process_paypal_ipn();
            return;
        }
        
        // Handle general (non-paypal) requests.
        $action = empty($_REQUEST['rchn_resident_action']) ? 'show_form' :
                $_REQUEST['rchn_resident_action'];
        
        switch($action)
        {
            case 'payment_notification':
                $this->handle_payment_notification();
                break;
            case 'confirm':
                return $this->show_confirm_page();
            case 'submit':
                return $this->submit();
            case 'show_form':
            default:
                return $this->show_order_form();
        }
    }
    
    
    /**
     * Handle any clean up after the user returns to this site after paying
     * at paypal.
     */
    public function handle_paypal_return()
    {
        $_SESSION['rchn_resident_id'] = null;
    }
    
    
    /**
     * Handle the payment notification.
     */
    protected function process_paypal_ipn()
    {
        require_once 'lib/paypal-ipn.php';
        $ipn = new PaypalIPN(RCHN_CITIZEN_DEBUG, RCHN_CITIZEN_USE_SANDBOX);
        
        if(!$ipn->verify_paypal_request())
        {
            exit;
        }
        
        // Ensure all the below are valid before continuing.
        $required = array(
	        'payment_status' => 'Completed',
	        'mc_gross'       => RCHN_CITIZEN_REG_PRICE,
	        'mc_currency'    => 'USD',
	        'receiver_email' => RCHN_CITIZEN_PAYPAL_EMAIL,
	        'item_name'      => self::PAYPAL_ITEM
        );
        
        foreach($required as $key => $value)
        {
            if(empty($_POST[$key]) || $_POST[$key] != $value)
            {
                // bogus info or failed payment
                return false;
            }
        }
        
        
        // We use the 'custom' field to store the resident_id.
        if(empty($_POST['custom']) || !is_numeric($_POST['custom']))
        {
            return false;
        }
        
        $where = 'status=\'unpaid\' and id = ' . (int)$_POST['custom'];
        
        if(!$resident = $this->resident_model->get_residents($where))
        {
            return false;
        }
        
        $resident_id = $resident[0]->id;
    
        $transaction_id = empty($_POST['txn_id']) ? 'null' : esc_sql($_POST['txn_id']);
        $this->resident_model->set_paid($resident_id, $transaction_id);
        $this->send_conf_email($resident_id);
        exit;
    }
    
    /**
     * Handle front-end form submit.
     */
    public function submit()
    {
        if(!empty($_SESSION['rchn_resident_id']))
        {
            // Ensure the resident still exists in the DB.
            if($this->resident_model->get_by_id((int) $_SESSION['rchn_resident_id']))
            {
                return $this->update_resident($_SESSION['rchn_resident_id']);
            }
        }
        
        return $this->add_resident();
    }
    
    
    /**
     * The user chose to edit their information.
     */
    public function update_resident()
    {
        $this->validate_form();
        
        if(!empty($this->errors))
        {
            return $this->show_order_form();
        }
        
        // Try to update the resident record.
        if(($this->resident_model->update($_SESSION['rchn_resident_id'])) === false)
        {
            $msg = 'Sorry, an internal error occured.  Please try again in a ' .
                'few minutes.';
            $this->errors = array($msg); 
            return $this->show_order_form();
        }
        
        // Success!  Request user confirmation of info.
        $location = $this->url . '?rchn_resident_action=confirm';
        header('location: ' . $location);
        exit;
    }
    
    
    
    /**
     * Add the resident to the database after validating the data.
     */
    public function add_resident()
    {
        $this->validate_form();
        
        if(!empty($this->errors))
        {
            return $this->show_order_form();
        }
        
        // Try to insert the resident record.
        if(($resident_id = $this->resident_model->add()) === false)
        {
            $msg = 'Sorry, an internal error occured.  Please try again in a ' .
                'few minutes.';
            $this->errors = array($msg); 
            return $this->show_order_form();
        }
        
        // Success!  Request user confirmation of info.
        $_SESSION['rchn_resident_id'] = $resident_id;
        $location = $this->url . '?rchn_resident_action=confirm';
        header('location: ' . $location);
        exit;
    }
    
    
    /**
     * Set form validation errors if they exist.
     */
    protected function validate_form()
    {
        $errors = array();
        
        if(empty($_POST['firstname']))
        {
            $errors[] = 'First name is required.';
        }
        
        if(empty($_POST['lastname']))
        {
            $errors[] = 'Last name is required.';
        }
        
        if(empty($_POST['email']))
        {
            $errors[] = 'Email address is required.';
        }
        else
        {
            if(!is_email($_POST['email']))
            {
                $errors[] = 'Email address is not valid.';
            }
        }
        
        
        // Check if this email address has already registered.
        if(empty($errors) && !empty($_POST['email']))
        {
            $where = 'email = \'' . esc_sql($_POST['email']) .
                    '\' and status = \'paid\'';
            $unique = !$this->resident_model->get_residents_count($where);     
            
            if(!$unique)
            {
                $msg = 'Sorry, that email address is already registered as a ' .
                    'member of the nation.';
                $errors[] = $msg;
            }
        }
        
        $this->errors = $errors;
    }
        
    
    /**
     * Display the order form
     *
     * @return string
     */
    public function show_order_form()
    {
        ob_start();
        $resident = $this->get_resident_for_form();
        $username = $this->get_wp_username();        
        include 'views/order-form.php';
        $form = ob_get_clean();
        return $form;
    }
    
    
    /**
     * Get an object to use for populating the form.
     *
     * @return stdClass
     */
    protected function get_resident_for_form()
    {
        // Setup a blank resident to use for a couple of cases.
        $resident = new stdClass;
        $resident->firstname = '';
        $resident->lastname = '';
        $resident->email = '';
        $resident->username = '';
        
        if(!empty($_REQUEST['rchn_resident_action']))
        {
            // Coming from the verification page but user wants to edit info.
            if($_REQUEST['rchn_resident_action'] == 'edit')
            {
                $res = $this->resident_model->get_by_id($_SESSION['rchn_resident_id']);
                
                if(!empty($res))
                {
                    return $res;
                }
                else
                // Couldn't find the record in the DB.
                {
                    // The empty object created at the top of this method.
                    return $resident;  
                }
            }
            // The form was posted
            else if($_REQUEST['rchn_resident_action'] == 'submit') 
            {
                // Populate data from post if exists, or just create an empty object.
                $_POST = array_map('stripslashes', $_POST);
                $resident = new stdClass;
                $resident->firstname = empty($_POST['firstname']) ? '' : $_POST['firstname'];
                $resident->lastname = empty($_POST['lastname']) ? '' : $_POST['lastname'];
                $resident->email = empty($_POST['email']) ? '' : $_POST['email'];
                $resident->username = empty($_POST['username']) ? '' : $_POST['username'];
                return $resident;
            }
        }
        
        // If we got down here, no form was posted and no resident ID exists
        // in the session so send back an empty object.
        return $resident;
    }
    
    
    /**
     * Display the confirmation page
     *
     * @return string
     */
    public function show_confirm_page()
    {
        $resident = $this->resident_model->get_by_id($_SESSION['rchn_resident_id']);
        
        // User took too long so the unpaid record was deleted.  Send them back
        // to the main form.
        if(empty($resident))
        {
            header('location: ' . $this->url);
            exit;
        }
        
        ob_start();
        include 'views/order-confirm.php';
        $form = ob_get_clean();
        return $form;
    }
    
    
    /**
     * Get the wordpress username for pre-populating the order form.
     *
     * @return string 
     */
    protected function get_wp_username()
    {
        if(isset($_POST['username']))
        {
            return $_POST['username'];
        }
        else if(!empty($GLOBALS['user_ID']))
        {
            if(empty($GLOBALS['current_user']))
            {
                get_currentuserinfo();
            }
            
            return $GLOBALS['current_user']->user_login;
        }
        else
        {
            return '';
        }
    }
    
    
    /**
     * Get posted data suitable for html output
     *
     * @return string
     */
    public function form_data($var_name, $quote_flags = null)
    {
        $quote_flags = empty($quote_flags) ? ENT_COMPAT | ENT_HTML_401 : $quote_flags;
        
        if(!empty($_POST[$var_name]))
        {
            return htmlentities(stripslashes($_POST[$var_name]), $quote_flags);
        }
        else
        {
            return '';
        }
    }
    
    
    /**
     * Email a confirmation to the new resident of the nation.
     *
     * @param integer $resident_id
     */
    protected function send_conf_email($resident_id)
    {
        $resident = $this->resident_model->get_by_id($resident_id);
        ob_start();
        include 'views/order-email.php';
        $message = ob_get_clean();
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: ' . self::FROM_EMAIL;
        $subject = 'RC Heli Nation Registered Citizen Confirmation';
        mail($resident->email, $subject, $message, $headers);
    }

    
    /**
     * Set the menu item in the admin area.
     */
    public function set_menu_item()
    {
        add_menu_page('RCHN Registered Citizens', 'RCHN Citizens', 'import',
                         'rchn-residents', array($this, 'admin_residents_page'),
                         'dashicons-groups', '2.26');
    }
    
    
    /**
     * Display the admin page within the admin area.
     */
    public function admin_residents_page()
    {
        require 'lib/resident-table.php';
        $resident_model = new Resident();
        $resident_table = new Resident_Table($resident_model);
        $resident_table->prepare_items();
        require 'views/listing.php';
    }


    public function get_random_winner()
    {
        $where = 'status = \'paid\'';
        $max = $this->resident_model->get_residents_count($where);
        $random = rand(self::MIN_WINNER_NUM, $max);
        $where = 'citizen_number = ' . $random;
        
        if($residents = $this->resident_model->get_residents($where))
        {
            $resident = $residents[0];
            $result = "
                <strong>Citizen #:</strong> {$resident->citizen_number}<br />
                <strong>Name:</strong> " . htmlentities($resident->firstname) .
                                ' ' . htmlentities($resident->lastname) . "<br />
                <strong>Email:</strong> " . htmlentities($resident->email) . "<br />
                <strong>RCHN Username:</strong> " . htmlentities($resident->username) . "<br />";
            print($result);
            exit;
        }
    }
}

new Resident_Manager();
