<?php

/**
 * Class to handle Paypal's IPN.  Most of the code here was taken from
 * https://github.com/paypal/ipn-code-samples/blob/master/IPN_PHP.txt
 */
class PaypalIPN
{
    protected $debug; 
    protected $use_sandbox;
    const LOG_FILE = './ipn.log';
    
    
    public function __construct($debug=false, $use_sandbox=false)
    {
        $this->debug = $debug;
        $this->use_sandbox = $use_sandbox;
    }
    
    
    /**
     * Read POST data. Reading posted data directly from $_POST causes
     * serialization issues with array data in POST. Reading raw POST data from input stream instead.
     *
     * @return array This is the equivalent of the $_POST array;
     */
    protected function get_post_data()
    {
        $raw_post_data = file_get_contents('php://input');
        
        if($this->debug)
        {
            $this->log('Received payload from Paypal: ' . $raw_post_data);
        }
        
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        
        foreach ($raw_post_array as $keyval)
        {
            $keyval = explode ('=', $keyval);
            
            if(count($keyval) == 2)
            {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }
        
        return $myPost;
    }
        
    
    /**
     * Format the posted data as a query string for sending back to Paypal.
     
     * @return string
     */
    protected function get_request_data()
    {
        $req = 'cmd=_notify-validate';
        $myPost = $this->get_post_data();
        
        // Format the post for return to Paypal via query string.
        foreach ($myPost as $key => $value)
        {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }
        
        return $req;
    }
    
    
    /**
     * Should we use the live URL or the sandbox.
     *
     * @return string
     */
    protected function get_paypal_url()
    {
        if($this->use_sandbox == true)
        {
            $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        }
        else
        {
            $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
        }
        
        return $paypal_url;
    }
    
    
    protected function set_curl_options($ch, $req)
    {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        
        if($this->debug == true) {
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        }
        
        // CONFIG: Optional proxy configuration
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        
        // Set TCP timeout to 30 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        
        // CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
        // of the certificate as shown below. Ensure the file is readable by the webserver.
        // This is mandatory for some environments.
        
        //$cert = __DIR__ . "./cacert.pem";
        //curl_setopt($ch, CURLOPT_CAINFO, $cert);
    }
    
    
    /**
     * Send a line of text to the log file.
     *
     * @param string
     */
    protected function log($msg)
    {
        error_log($msg, 0, self::LOG_FILE);
    }
    
    
    /**
     * Verify the paypal request
     */
    public function verify_paypal_request()
    {
        $req = $this->get_request_data();
        $paypal_url = $this->get_paypal_url();
        
        // Post IPN data back to PayPal to validate the IPN data is genuine
        // Without this step anyone can fake IPN data
        $ch = curl_init($paypal_url);
        
        if ($ch == FALSE) {
                return FALSE;
        }
        
        $this->set_curl_options($ch, $req);
        $res = curl_exec($ch);
        
        if (curl_errno($ch) != 0) // cURL error
        {
            if($this->debug == true)
            {        
                $this->log('Can\'t connect to PayPal to validate IPN message: ' . curl_error($ch));
            }
            
            curl_close($ch);
            return false;
        }
        
        // Log the entire HTTP response if debug is switched on.
        if($this->debug == true)
        {
            $this->log('HTTP request of validation request: '. curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req");
            $this->log('HTTP response of validation request: ' . $res);
        }

        // Split response headers and payload
        list($headers, $res) = explode("\r\n\r\n", $res, 2);
        curl_close($ch);
        return true;
        // Inspect IPN validation result and act accordingly
        if (strcmp ($res, "VERIFIED") == 0)
        {
            // Success!
            // check whether the payment_status is Completed
            // check that txn_id has not been previously processed
            // check that receiver_email is your PayPal email
            // check that payment_amount/payment_currency are correct
            // process payment and mark item as paid.
    
            // assign posted variables to local variables
            //$item_name = $_POST['item_name'];
            //$item_number = $_POST['item_number'];
            //$payment_status = $_POST['payment_status'];
            //$payment_amount = $_POST['mc_gross'];
            //$payment_currency = $_POST['mc_currency'];
            //$txn_id = $_POST['txn_id'];
            //$receiver_email = $_POST['receiver_email'];
            //$payer_email = $_POST['payer_email'];
            
            if($this->debug == true)
            {
                    $this->log("Verified IPN: $req"); 
            }
            
            return true;
        }
        else if (strcmp ($res, "INVALID") == 0)
        {
            // log for manual investigation
            // Add business logic here which deals with invalid IPN messages
            if($this->debug == true)
            {
                $this->log("Invalid IPN: $req");
            }
        }
        return false;
    }
}