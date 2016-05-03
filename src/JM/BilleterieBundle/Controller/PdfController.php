<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PdfController extends Controller
{
    public function pdfAction(Request $request)    
    {   
		$session = $request->getSession();
		$payementBillet = $this->get('jm_billeterie.pdfbillet');
        $html2pdf = new \Html2Pdf_Html2Pdf('L','A4','fr');
		if($payementBillet->pdfSendMail($request, $html2pdf)){
            $session->getFlashBag()->add('alert', "Vos billets vous ont été envoyé à l'adresse mail : " . $payementBillet->pdfWhoMail($request) . ".");
			$url = $this->get('router')->generate('billeterie_after_payement');
			return new RedirectResponse($url);
		}
    }
}

?>