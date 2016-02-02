<?php
require_once 'class-wp-list-table.php';


class Resident_Table extends WP_List_Table_RCHN
{
	const ROWS_PER_PAGE = 25;
    protected $resident_model;
    
    
    public function __construct(Resident $resident_model)
    {
        $this->resident_model = $resident_model;
        
        $args = array(
            'singular'  => 'citizen', 
            'plural'    => 'citizens', 
            'ajax'	    => false
        );
        
        parent::__construct($args);
    }
     
     
    /**
     * Define the behavior of getting the data for each column.
     *
     * @param stdClass $item This is the record
     * @param string $column_name
     */
    protected function column_default($item, $column_name)
    {
        return $item->$column_name;
    }
    
    
    /**
     * Define the columns that are going to be used in the table
     * 
     * @return array The array of columns to use with the table
     */
    function get_columns()
    {
       return array(
           'citizen_number' => __('Citizen Number'),
           'firstname'      => __('First name'),
           'lastname'       => __('Last name'),
           'email'          => __('Email'),
           'username'       => __('RCHN username')
       );
    }
    
    
    /**
     * Columns that will be sortable
     * 
     * @return array The array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        return array(
            'citizen_number' => array('citizen_number', false),
            'firstname' => array('firstname', false),
            'lastname'  => array('lastname', false), 
            'email'     => array('email', false),
            'username'  => array('username' ,false)
        );
    }
    
    
    /**
     * Prepare the data for display.
     */
    function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $where = 'status = \'paid\'';
        $orderby = empty($_GET['orderby']) ? 'citizen_number' : $_GET['orderby'];
        $order = empty($_GET['order']) ? 'asc' :  $_GET['order'];
        $offset = empty($_GET['paged']) ? 0 : ((int)$_GET['paged'] -1) * self::ROWS_PER_PAGE;
        $current_page = $this->get_pagenum();
        $this->items = $this->resident_model->get_residents($where, $orderby,
                                        $order, self::ROWS_PER_PAGE, $offset);
        $total_items = count($this->items);
        
        for($i = 0; $i < count($this->items); ++$i)
        {
            foreach($this->items[$i] as $key => $value)
            {
                $this->items[$i]->$key = htmlentities($value);
            }
        }
        
        $this->set_pagination_args(
            array(
                'total_items' => $this->resident_model->get_residents_count($where),
                'per_page'    => self::ROWS_PER_PAGE
            )
        );
    }
}