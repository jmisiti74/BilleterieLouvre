<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JM\BilleterieBundle\Entity\Billet;
use JM\BilleterieBundle\Form\BilletType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketController extends Controller
{
    /**
     * @Route("/")
     */
    public function viewAction()
    {
        return $this->render('JMBilleterieBundle:Ticket:ticketChoice.html.twig');
    }
    public function panierAction(Request $request)
    {
        if (isset($_SESSION["Panier"])){
            return $this->render('JMBilleterieBundle:Ticket:panier.html.twig', array(
                'panier' => $_SESSION["Panier"]
            ));
            
        }
        $session = $request->getSession();
        $session->getFlashBag()->add('alert', "Le panier est vide !");
        $url = $this->get('router')->generate('billeterie');
        return new RedirectResponse($url);
    }
    public function addTicketAction(Request $request)
    {    
        $billet = new Billet();
        $form = $this->get('form.factory')->create(new BilletType(), $billet);
        
        if ($form->handleRequest($request)->isValid()) {
            
            if(!isset($_SESSION["Panier"])){
                $_SESSION["Panier"] = array();
            }
            $billet->setCodeUnique(uniqid('', true));
            array_push($_SESSION["Panier"], $billet);
            $request->getSession()->getFlashBag()->add('alert', 'Billet enregistrer avec succès dans le panier');

            return $this->redirect($this->generateUrl('billeterie'));
        }

        return $this->render('JMBilleterieBundle:Ticket:addTicket.html.twig', array(
          'form' => $form->createView(),
        ));
    }
}