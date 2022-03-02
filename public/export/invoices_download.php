<?php 
global $wpdb; global $sc_currency_symbol;     

include( plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/TCPDF/tcpdf.php');

class MYPDF extends TCPDF
{
    protected $processId =0;
    protected $header ='';
    protected $footer ='';
    static $errorMsg ='';
    /**
    * This method is used to override the parent class method.
    **/
    public function Header()
    {
        $this->writeHTMLCell($w='', $h='', $x='', $y='', $this->header, $border=0, $ln=0, $fill=0, $reseth=true, $align='L', $autopadding=true);

        //$this->SetLineStyle( array('width'=>0.80,'color'=> array(0,0,0)));

       // $this->Line(15,15, $this->getPageWidth()-15,15);

        //$this->Line($this->getPageWidth()-15,15, $this->getPageWidth()-15, $this->getPageHeight()-15);
        //$this->Line(15, $this->getPageHeight()-15, $this->getPageWidth()-15, $this->getPageHeight()-15);
       // $this->Line(15,15,15, $this->getPageHeight()-15);

        $image_file = get_option('_sc_company_logo');    
        if($image_file) {
            $this->Image($image_file, 14, 15, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// set document information

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$color = get_option('_sc_invoices_color');
$post_id = $_REQUEST['id'];
$postdata = get_posts($_REQUEST['id']);
//print_r(get_post_type($post_id)); exit();
if(get_post_type($post_id) == 'sc_order'){
    $sub_amount = get_post_meta( $post_id, '_sc_amount', true);
}else{
   $sub_amount = get_post_meta( $post_id, '_sc_sub_amount', true); 
}

$productname = get_the_title(get_post_meta( $post_id, '_sc_product_id', true));
$item_name = get_post_meta( $post_id , '_sc_sub_item_name', true );
if(get_post_meta( $post_id , '_sc_free_trial_days', true)){
   $free_trial_days = get_post_meta( $post_id , '_sc_free_trial_days', true);                  
   $start_date =  date("M j, Y", strtotime(get_the_time( 'Y-m-d', $post_id ) ."+".$free_trial_days." day"));
}else{
    $start_date =  get_the_time( 'M j, Y', $post_id );   
}
$installments = get_post_meta( $post_id , '_sc_sub_installments', true );
$nextdate = get_post_meta( $post_id , '_sc_sub_end_date', true );
$dateTime = DateTime::createFromFormat('Y-m-d', $nextdate);
$subinterval = get_post_meta( $post_id , '_sc_sub_interval', true );
if($dateTime !== FALSE) {
    $end_date = $dateTime->format('M j, Y');
} else {
    $installments--;
    $interval = get_post_meta( $post_id , '_sc_sub_interval', true );

    //$subinterval = get_post_meta( $post_id , '_sc_sub_interval', true );
    $interval = ($installments > 1) ? $interval.'s' : $interval; 
    if(get_post_meta( $post_id , '_sc_free_trial_days', true)){
        $free_trial_days = get_post_meta( $post_id , '_sc_free_trial_days', true);                  
        $datepay = date("Y/m/d", strtotime(get_the_time( 'Y-m-d', $post_id ) ."+".$free_trial_days." day"));
    }else{
        $datepay = get_the_time( 'Y/m/d', $post_id );   
    }
    $dateTime = DateTime::createFromFormat('Y/m/d', $datepay);
    $dateTime->add(DateInterval::createFromDateString($installments . ' ' . $interval));
    $end_date = $dateTime->format('M j, Y');
}
$nextdate = get_post_meta( $post_id , '_sc_sub_next_bill_date', true );
if (is_numeric($nextdate)) {
    $next_date = get_date_from_gmt(date( 'Y-m-d H:i:s', $nextdate ), 'M j, Y');
} else {
    $dateTime = DateTime::createFromFormat('Y-m-d', $nextdate);
    if ($dateTime !== FALSE) {
        $next_date = $dateTime->format('M j, Y');
    }
}

$order = sc_setup_order($post_id);

$bumpname='';
if(get_post_meta( $post_id , '_sc_bump_id', true )){
   $bumpname = get_the_title(get_post_meta( $post_id, '_sc_bump_id', true));
   $bumpamt = get_post_meta( $post_id, '_sc_bump_amt', true); 
}


$first_name = get_post_meta($post_id,  '_sc_firstname', true).' '. get_post_meta($post_id,  '_sc_lastname', true);
$address1 =  get_post_meta( $post_id ,  '_sc_address1', true);
$address2 = '';
if(get_post_meta( $post_id ,  '_sc_city', true)){
  $address2 .= get_post_meta( $post_id ,  '_sc_city', true).', ';  
}
if(get_post_meta( $post_id ,  '_sc_state', true)){
  $address2 .= get_post_meta( $post_id ,  '_sc_state', true).', ';  
}
if(get_post_meta( $post_id ,  '_sc_zip', true)){
  $address2 .= get_post_meta( $post_id ,  '_sc_zip', true).', ';  
}
if(get_post_meta( $post_id ,  '_sc_country', true)){
  $address2 .= get_post_meta( $post_id ,  '_sc_country', true).' ';  
}
//$state =  get_post_meta( $sub_id ,  '_sc_state', true);
//$zip =  get_post_meta( $sub_id ,  '_sc_zip', true);
$email = get_post_meta($post_id,  '_sc_email', true);
// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font

$company_name = get_option('_sc_company_name');
$company_address = get_option('_sc_company_address');

// add a page
$pdf->AddPage();


// set font
$pdf->SetFont('helvetica-neue', '', 25);
$pdf->SetTextColor(165,179,183);

$txt = <<<EOD
Invoice
EOD;
// print a block of text using Write()
$pdf->Write(0, $txt, '', 0, 'R', true, 0, false, false, 0);
$pdf->SetTextColor(0,0,LDAP_OPT_X_TLS_PROTOCOL_TLS1_0);
$pdf->SetFont('helvetica-neue', '', 10);

if(get_post_type($post_id) == 'sc_order'){
    $txt = '<div style="font-family: Arial, Helvetica, sans-serif;"><div style="font-size:12px;display:block;" width="300">Invoice Number</span><span style="font-size:12px;">'.$post_id.'</span><br/><br/></div>'; 
}else{  
  $txt = '<div style="font-family: Arial, Helvetica, sans-serif;"><span style="font-size:12px;">Invoice Nunber</span><span style="font-size:12px;">'.$post_id.'</span><br/><span style="font-weight: bold; font-size:12px;">Subscription Start Date : </span><span style="font-size:12px;">'.$start_date.'</span> <br/> <span style="font-weight: bold; font-size:12px;">Subscription End Date : </span><span style="font-size:12px;">'.$end_date.'</span></div>'; 
}
$pdf->MultiCell('', '', $txt, 0, 'R', 0, 1, '', $y, true, 0, true);

// set some text to print
$txt = <<<EOD
    $company_name
    $company_address
EOD;

// print a block of text using Write()
$pdf->Write(0, $txt, '', 0, 'R', true, 0, false, false, 0);
// ---------------------------------------------------------

$pdf->SetFont('helvetica-neue', '', 8);
$txt = '<hr />';
$y = $pdf->GetY()+5;
$pdf->MultiCell('', '', $txt, 0, 'L', 0, 1, '', $y, true, 0, true);


$pdf->SetFont('helvetica-neue', '', 8);

$txt = '<div style="font-family: Arial, Helvetica, sans-serif;"><span style="font-size:10px;text-transform:uppercase">Bill to</span><br/>
<span style="font-size:16px;">'.$first_name.'</span><br/>
<span style="font-size:12px;">'.$email.'</span><br />
<span style="font-size:12px;">'.$address1.' <br/> '.$address2.' </span><br/></div>';


$y = $pdf->GetY()+5;
$pdf->MultiCell('', '', $txt, 0, 'L', 0, 1, '', $y, true, 0, true);

$pdf->SetFont('helvetica-neue', '', 8);
if(get_post_type($post_id) == 'sc_order'){
    $txt = '<div style="font-family: Arial, Helvetica, sans-serif;"><span style="font-weight: bold; font-size:12px;">Invoice No : </span><span style="font-size:12px;">'.$post_id.'</span><br/><br/></div>'; 
}else{  
  $txt = '<div style="font-family: Arial, Helvetica, sans-serif;"><span style="font-weight: bold; font-size:12px;">Invoice No : </span><span style="font-size:12px;">'.$post_id.'</span><br/><span style="font-weight: bold; font-size:12px;">Subscription Start Date : </span><span style="font-size:12px;">'.$start_date.'</span> <br/> <span style="font-weight: bold; font-size:12px;">Subscription End Date : </span><span style="font-size:12px;">'.$end_date.'</span></div>'; 
}

//$y = $pdf->GetY()+3;
$pdf->MultiCell('', '', $txt, 0, 'R', 0, 1, '', $y, true, 0, true);

$pdf->SetY($pdf->GetY()+15);
// create some HTML content
$pdf->SetFont('helvetica-neue', 'B', 8);
$html = '
<table border="0" cellspacing="0" cellpadding="0" width="100%" style="font-family: Arial, Helvetica, sans-serif;font-size:12px;font-weight:normal">
    <tr>
        <td colspan="2">
            <table border="0" cellspacing="0" cellpadding="10" width="100%">
                 <tr>        
                    <th width="50%" style="background-color: '.$color.';color:#fff;" >Description</th> 
                    <th align="center" width="17%" style="background-color: '.$color.';color:#fff;" >Quantity</th>
                    <th align="center" width="16%" style="background-color: '.$color.';color:#fff;" >Price</th>
                    <th align="center" width="16%" style="background-color: '.$color.';color:#fff;" >Subtotal</th>
                </tr>
                <tr>
                    <td>'.$productname.' - '.$item_name.'</td> 
                    <td align="center">1</td>
                    <td align="center">'.sc_format_price($sub_amount).'</td>
                    <td align="center" style="font-weight:bold">'.sc_format_price($sub_amount).'</td>
                </tr>';

                if($bumpname !=""){
                 $html .= '<tr>
                    <td>'.$bumpname.'</td>
                    <td align="center">1</td>
                    <td align="center">'.sc_format_price($bumpamt).'</td>
                    <td align="center" style="font-weight:bold">'.sc_format_price($bumpamt).'</td>
                </tr>'; 
                $sub_amount += $bumpamt;  
                }
                if (isset($order->order_child)){
                    foreach($order->order_child as $child_order){
                        $html .= '<tr>
                            <td>'.print_r($order->order_child,true).$child_order['product_name'].'</td> 
                            <td align="center">1</td>
                            <td align="center">'.sc_format_price($child_order['amount']).'</td>
                            <td align="center" style="font-weight:bold">'.sc_format_price($child_order['amount']).'</td>
                        </tr>';  
                        $sub_amount += get_post_meta( $child_order['id'], '_sc_amount', true );
                    }
                }  
            $html .= '</table>
        </td>
    </tr>
    <tr>
        <td colspan="2"><br />
        <hr /></td>
    </tr>   
        <tr>
            <td width="60%"></td>
            <td align="rigst"  width="40%" style="width : 40%; " >           
                <table border="0" cellspacing="0" cellpadding="5"  width="100%">
                    <tr>                
                        <th width="40%" align="left">SubTotal</th>
                        <th width="60%" align="center">'.sc_format_price($sub_amount,2).'</th>
                    </tr>                   
                    <tr>               
                        <th style="border-top: 1px solid #000;" align="left" >Total</th>
                        <th style="border-top: 1px solid #000;" align="center">'.sc_format_price($sub_amount,2).'</th>
                    </tr> 
                </table>            
            </td>
        </tr>
    </table>      
   
';
/*  
    <tr>
        <td>
           
            <table border="0" cellspacing="0" cellpadding="5" width="100%">
                <tr>                
                    <th  width="77%" align="Right" >SubTotal</th>
                    <th width="23%" align="center">$'.number_format($sub_amount,2).'</th>
                </tr>
                <tr>
                    
                    <td colspan="2" align="Right"> <hr /></td>
                </tr>
                <tr>               
                    <th align="Right" >Total</th>
                    <th align="center">$'.number_format($sub_amount,2).'</th>
                </tr> 
            </table>
        </td>
    </tr> 
<tr>
        <th colspan="2">CGST @ 9%</th>
        <th>422.88</th>
    </tr>
    <tr>
        <th colspan="2">SGST @ 9%</th>
        <th>422.88</th>
    </tr> */
// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->SetFont('times', '', 8);
$txt = '*This is a system generated invoice and does not require signature.';
$pdf->MultiCell('', '', $txt, 0, 'C', 0, 1, '', '', true, 0, true);
//Close and output PDF document
$pdf_name = 'invoice';
$pdf->Output($pdf_name.'.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

?>
