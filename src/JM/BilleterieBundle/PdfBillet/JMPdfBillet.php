<?php

namespace JM\BilleterieBundle\PdfBillet;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class JMPdfBillet
{
	private $em;
	
	private $mailer;
	
	private $twig;
	
	public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer, EntityManager $manager)
	{
		$this->em = $manager;
		$this->mailer = $mailer;
		$this->twig = $twig;
	}
	public function pdfSendMail(Request $request, $html2pdf)
	{
		$session = $request->getSession();
        $repository = $this->em->getRepository('JMBilleterieBundle:BilletDate');
        $repositoryPanier = $this->em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $this->em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($session->get('Panier'));
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        $html2pdf->pdf->SetSubject('Billet Musée du Louvre');
        $html2pdf->pdf->SetAuthor('Musée du Louvre');
        $html2pdf->pdf->SetTitle('Billet_Musée_Louvre');
		$message = \Swift_Message::newInstance()
			->setSubject('Billet Musée du Louvre')
			->setFrom(array('museelouvre@mdl.fr' => 'Musée du louvre'))
			->setBody('')
			->addPart('Bonjour, voilà vos billets pour le musée du Louvre. N\'oubliez pas de venir avec vos justificatifs !')
		;
        foreach ($listeBillets as $billet){
			if($billet->getPayer()){
				$content = $this->twig->render('JMBilleterieBundle:Ticket:pdf.html.twig', array(
					'nom' => $billet->getNom(),
					'prenom' => $billet->getPrenom(),
					'dateReservation' => $billet->getDateReservation(),
					'dateNaissance' => $billet->getDateNaissance(),
					'prix' => $billet->getPrix(),
					'tarifReduit' => $billet->getTarifReduit(),
					'codeUnique' => $billet->getCodeUnique(),
				));
				$html2pdf->WriteHTML($content);
				$message->setTo(array($billet->getEmail() => $billet->getPrenom()));
			}
        }
		$content_PDF = $html2pdf->Output('billets.pdf', 'F');
		$message->attach(\Swift_Attachment::fromPath('billets.pdf'));
		$this->mailer->send($message);
		return true;
    }
	public function pdfWhoMail(Request $request)
	{
		$session = $request->getSession();
        $repositoryBillet = $this->em->getRepository('JMBilleterieBundle:Billet');
        $repositoryPanier = $this->em->getRepository('JMBilleterieBundle:Panier');
        $panier = $repositoryPanier->find($session->get('Panier'));
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        foreach ($listeBillets as $billet){
			if($billet->getPayer()){
				$mail = $billet->getEmail();
			}
        }
		return $mail;
    }
}

?>