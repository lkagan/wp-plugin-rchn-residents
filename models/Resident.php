<?php

class Resident
{
    protected $wpdb;
    protected $table_name;
    const UNPAID_REG_TIME_LIMIT = 3600; // 60 minutes.
    
    public function __construct()
    {
        $this->wpdb = $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . 'rchn_residents';
    }
    
    
    /**
     * Get a single resident info by ID
     *
     * @return mixed object on success, false otherwise.
     */
    public function get_by_id($id)
    {
        $sql = 'select * from ' . $this->table_name . ' where id = ' . (int) $id;
        return $this->wpdb->get_row($sql);
    }
    
    
    /**
     * Get the fields posted from the front-end form.
     *
     * @return array
     */
    protected function get_posted_fields()
    {
        // Stupid Wordpress adds slashes.
        $_POST = array_map('stripslashes', $_POST);
        
        $fields = array(
            'firstname' => $_POST['firstname'],
            'lastname' => $_POST['lastname'],
            'email' => $_POST['email']);
        
        if(!empty($_POST['username']))
        {
            $fields['username'] = $_POST['username'];
        }
        
        return $fields;
    }
    
    
    /**
     * Add a resident from a form post.
     *
     * @return mixed id of resident on success, false otherwise.
     */
    public function add()
    {
        $fields = $this->get_posted_fields();
        
        if($this->wpdb->insert($this->table_name, $fields) === false)
        {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    
    /**
     * Update a resident from a form post.
     *
     * @param integer $resident_id
     * @return bool
     */
    public function update($resident_id)
    {
        $fields = $this->get_posted_fields();
        $where = array('id' => (int)$resident_id);
        $result = $this->wpdb->update($this->table_name, $fields, $where);
        return $result !== false;
    }
    
    
    /**
     * Set the resident as paid.
     *
     * @param integer $resident_id
     * @param string $transaction_id
     * @return bool
     */
    public function set_paid($resident_id, $transaction_id)
    {
        $this->wpdb->query('start transaction');
        $sql = 'select max(citizen_number) from ' . $this->table_name .
            ' wp_rchn_residents lock in share mode;';
        $max_id = $this->wpdb->get_var($sql);
        $new_id = ++$max_id;
        $sql = 'update ' . $this->table_name . ' set status = \'paid\', ' .
            'paypal_transaction_id = \'' . esc_sql($transaction_id) . '\', ' .
            'citizen_number = ' . $new_id . ' where id = ' .
            (int) $resident_id;
        $result = $this->wpdb->query($sql);
        $this->wpdb->query('commit');
        return $result !== false;
    }
    
    
    /**
     * Get residents count.
     *
     * @param string $where
     * @return array
     */
    public function get_residents_count($where=null) 
    {
        $sql = 'select count(*) from ' . $this->table_name;
        
        if(!is_null($where))
        {
            $sql .= ' where ' . $where;
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    
    /**
     * Get residents.
     *
     * @param string $where
     * @param string $orderby
     * @param string $order
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function get_residents($where=null, $orderby=null, $order=null,
                                  $limit=null, $offset=null)
    {
        $sql = 'select * from ' . $this->table_name;
        
        if(!is_null($where))
        {
            $sql .= ' where ' . $where;
        }
        
        if(!is_null($orderby))
        {
            $sql .= ' order by ' . esc_sql($orderby);
            
            if(is_null($order))
            {
                $sql .= ' asc ';
            }
            else
            {
                $sql .= ' ' . esc_sql($order);
            }
        }
        
        if(!is_null($limit))
        {
            $sql .= ' limit ';
            
            if(!is_null($offset))
            {
                $sql .= esc_sql($offset) . ',';
            }
            
            $sql .= $limit;
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    
    /**
     * Clear out old sign-ups that were never paid.
     */
    public function clear_unpaid_signups()
    {
        $sql = 'delete from ' . $this->table_name . 
                ' where status = \'unpaid\' and created < NOW() - interval ' .
                 self::UNPAID_REG_TIME_LIMIT . ' second';
        $this->wpdb->query($sql);
    }
    
    public function get_random_winner($min)
    {
        $sql = 'select * from ' . $this->table_name .
            'where citizen_number = ' . $random_cit_num;
    }
}
