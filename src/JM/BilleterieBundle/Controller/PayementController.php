<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PayementController extends Controller
{
    /* VVV POUR PAYPAL VVV */
    public function paypalAction(Request $request)
    {
		$session = $request->getSession();
		$router = $this->get('router');
		$payementBillet = $this->get('jm_billeterie.paybillet');
		if($payementBillet->verifPaypalPayement($request, $router) === false){
            $session->getFlashBag()->add('alert', "Un erreur est survenue.");
            $url = $this->get('router')->generate('billeterie');
            return new RedirectResponse($url);
		} else {
			$paypalLink = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=' . $payementBillet->verifPaypalPayement($request, $router)['TOKEN'];
			return $this->redirect($paypalLink);			
		}
    }
    public function paypalSuccessAction(Request $request)
    {
		$session = $request->getSession();
		$router = $this->get('router');
		$payementBillet = $this->get('jm_billeterie.paybillet');
		if(!isset($_GET['token'])){
            $session->getFlashBag()->add('alert', "Vous ne pouvez pas accedez a cette page sans Payer !");
            $url = $this->get('router')->generate('billeterie');
            return new RedirectResponse($url);
		}
		if($payementBillet->verifPaypalSuccess($request, $router)){
			$url = $this->get('router')->generate('billeterie_pdf');
			return new RedirectResponse($url);
		}
    }
    /* ^^^ POUR PAYPAL ^^^ */
    
		
    /* VVV POUR STRIPE VVV */
    public function stripeAction(Request $request)
    {   
		$session = $request->getSession();
		$payementBillet = $this->get('jm_billeterie.paybillet');
		if($payementBillet->verifStripePayement($request)){
			$url = $this->get('router')->generate('billeterie_pdf');
			return new RedirectResponse($url);
		}
    }
    /* ^^^ POUR STRIPE ^^^ */
	
	/* Une fois que le payement et les pdf ont été envoyés */
	public function doneAction()
	{
        return $this->render('JMBilleterieBundle:Ticket:done.html.twig');
	}
}

?>