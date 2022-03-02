<?php 
use Dompdf\Dompdf;
if(!empty($_REQUEST['sc-invoice'])){
    $post_id = intval($_REQUEST['sc-invoice']);
    $download = (!isset($_REQUEST['dl'])) ? true : (boolean) $_REQUEST['dl'];
    $order = sc_get_user_orders(get_current_user_id(), $status='any', $post_id);
    if($order) {
        require_once dirname(__FILE__).'/../vendor/autoload.php'; 
        include_once dirname(__FILE__).'/../templates/pdf-invoice/invoice.php';

        try {
            $fileName = esc_html("invoice", 'ncs-cart')."-".time().".pdf";
            $pdfPath = $fileName;
            $dompdf = new Dompdf();
            $content ="<html>
                <body>
                    <style>
                    @page { margin-top: 0px;padding-top:0px; }
                    body { margin-top: 0px;padding-top:0px; }
                    </style>
                    ".$html."
                </body>
            </html>";
            $dompdf->loadHtml($content);
            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('8.5x11');
            // Render the HTML as PDF
            $dompdf->render();
            // Output the generated PDF to Browser
            $dompdf->stream($fileName, array("Attachment" => $download));
        } catch(EXCEPTION $ex) {
            echo $ex;
        }
    } else {
        esc_html_e("Unauthorized", "ncs-cart");
    }
} else {
    esc_html_e("Looks like you are missing something. Please try again later.", "ncs-cart");
}
die();
?>