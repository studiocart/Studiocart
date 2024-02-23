<?php 
use Dompdf\Dompdf;
    $footer = get_option('_sc_invoice_footer');
    $font = 'Arial, Helvetica, sans-serif;';
    $id = $order->id ?? $order->ID;
    if($order) {
        require_once dirname(__FILE__).'/../vendor/autoload.php'; 
        $file = ncs_get_template_path('pdf-invoice/invoice');
        include_once $file;

        try {            
            $fileName = esc_html("invoice", 'ncs-cart')."-".get_post_timestamp($id).".pdf";
            $pdfPath = $fileName;
            $dompdf = new Dompdf();
            $content ="<html>
                <body>
                    <style>
                    @page { margin-top: 0px;padding-top:0px; }
                    body { margin-top: 0px;padding-top:0px; }
                    footer {
                        position: fixed; 
                        bottom: -35px; 
                        left: 0px; 
                        right: 0px;
                        height: 50px; 
                        font-family: ".$font.";
                        color: rgb(165,179,183);
                        font-size: 13px;
                        text-align: center;
                        line-height: 35px;
                    }
                    </style>";
            
            if($footer) {
                $content .= "<footer>".$footer."</footer>";
            }
                    
            $content .= $html."
                </body>
            </html>";
            $dompdf->loadHtml($content);
            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('8.5x11');
            // Render the HTML as PDF
            $dompdf->render();

            if ($stream) {
                // Output the generated PDF to Browser
                $dompdf->stream($fileName, array("Attachment" => $download));
            } else { 
                $invoicePath = $upload_dir['basedir'].DIRECTORY_SEPARATOR.'invoices'.DIRECTORY_SEPARATOR;
                if (!is_dir($invoicePath)) {
                    // dir doesn't exist, make it
                    mkdir($invoicePath);
                }
                //get Attachment path:
                $upload_dir   = wp_upload_dir();
                $invoicePath .= $fileName;
                $output = $dompdf->output();
                file_put_contents($invoicePath, $output);
                return $invoicePath;
            }

        } catch(EXCEPTION $ex) {
            echo $ex;
        }
    } else {
        esc_html_e("Unauthorized", "ncs-cart");
    }

die();
?>