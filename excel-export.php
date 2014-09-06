<?php
require_once 'lib/PHPExcel.php';
require_once 'lib/PHPExcel/Writer/Excel2007.php';
require_once  '../../../wp-blog-header.php';
require_once 'models/Resident.php';


class RCHNResidentExport
{
    protected $objPHPExcel;
    protected $resident_model;
    protected $residents;
    
    function __construct()
    {
        if(!is_user_logged_in())
        {
            exit;
        }
        
        $this->objPHPExcel = new PHPExcel();
        $this->residents_model= new Resident();
        $this->residents = $this->residents_model->get_residents('status = \'paid\'');
        $this->set_labels();
        $this->build_sheet();
        $this->objPHPExcel->getDefaultStyle()->getNumberFormat()
                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                
        // Set the active sheet to be the first one.
        $this->objPHPExcel->setActiveSheetIndex(0);
    }
    
    
    /**
     * Build the first sheet
     */
    protected function build_sheet()
    {
        $this->objPHPExcel->setActiveSheetIndex(0);
        $this->objPHPExcel->getActiveSheet()->getStyle('A1:E1')->applyFromArray(array('font' => array('bold'=>true)));
        $this->set_headers();
        $this->set_content();
    }
    
    
    /**
     * Set the labels 
     */
    protected function set_labels()
    {
        $this->labels = array(
            'citizen_number'    => 'Citizen #',
            'firstname'         => 'First Name',
            'lastname'          => 'Last Name',
            'email'             => 'Email',
            'username'          => 'RCHN Username'
        );
    }
    
    
    /**
     * Set the headers
     */
    protected function set_headers()
    {
        $active_sheet = $this->objPHPExcel->getActiveSheet();
        $col = 'a';
        $row = 1;
           
        // Set the labels
        foreach($this->labels as $key => $value)
        {
            $active_sheet->SetCellValue($col . $row, $value);
            $active_sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
    }
    
    
    /**
     * Set the content of the sheet.
     */
    protected function set_content()
    {
        $active_sheet = $this->objPHPExcel->getActiveSheet();
        $row = 2;
        $total_residents = count($this->residents);
        
        foreach($this->residents as $resident)
        {
            $col = 'a';
            $active_sheet->SetCellValue($col++ . $row, $resident->citizen_number);
            $active_sheet->SetCellValue($col++ . $row, $resident->firstname);
            $active_sheet->SetCellValue($col++ . $row, $resident->lastname);
            $active_sheet->SetCellValue($col++ . $row, $resident->email);
            $active_sheet->SetCellValue($col++ . $row, $resident->username);
            $row++;
        }
    }
    
    
    /**
     * Send the export to the browser.
     */
    public function export()
    {
        $objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");;
        header("Content-Disposition: attachment;filename=citizens.xls"); 
        header("Content-Transfer-Encoding: binary");
        $objWriter->save('php://output');
    }
}

$rchn_exporter = new RCHNResidentExport();
$rchn_exporter->export();