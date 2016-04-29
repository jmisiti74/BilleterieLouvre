<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PdfController extends Controller
{
    public function pdfAction()    
    {   
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('JMBilleterieBundle:billetDate');
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($_SESSION["Panier"]);
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        $html2pdf = new \Html2Pdf_Html2Pdf('L','A4','fr');
        $html2pdf->pdf->SetSubject('Billet Musée du Louvre');
        $html2pdf->pdf->SetAuthor('Musée du Louvre ©');
            $html2pdf->pdf->SetTitle('Billet_Musée_Louvre');
        foreach ($listeBillets as $billet){
            $content = $this->renderView('JMBilleterieBundle:Ticket:pdf.html.twig', array(
                'nom' => $billet->getNom(),
                'prenom' => $billet->getPrenom(),
                'dateReservation' => $billet->getDateReservation(),
                'dateNaissance' => $billet->getDateNaissance(),
                'prix' => $billet->getPrix(),
                'tarifReduit' => $billet->getTarifReduit(),
                'codeUnique' => $billet->getCodeUnique(),
            ));
            $html2pdf->WriteHTML($content);
        }
        $html2pdf->Output('exemple.pdf');
        return $response;      
    }
}