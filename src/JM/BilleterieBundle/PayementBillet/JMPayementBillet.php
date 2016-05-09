<?php

namespace JM\BilleterieBundle\PayementBillet; 

use Symfony\Component\HttpFoundation\RedirectResponse;
use JM\BilleterieBundle\Entity\BilletDate;
use JM\BilleterieBundle\Entity\Billet;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class JMPayementBillet
{    
	private $paypalAccVendeur;
	
	private $paypalUser;
	
	private $paypalPwd;
	
	private $paypalSignature;
	
	private $paypalVersion;
	
	private $stripeKey;
	
	private $em;
	
	public function __construct(EntityManager $manager, $paypal_ACC_VENDEUR, $paypal_USER, $paypal_PWD, $paypal_SIGNATURE, $paypal_VERSION, $stripe_KEY)
	{
		/* Definition de variables utiles */
		$this->em = $manager;
		$this->paypalAccVendeur = $paypal_ACC_VENDEUR;
		$this->paypalUser = $paypal_USER;
		$this->paypalPwd = $paypal_PWD;
		$this->paypalSignature = $paypal_SIGNATURE;
		$this->paypalVersion = $paypal_VERSION;
		$this->stripeKey = $stripe_KEY;
	}
    function verifPaypalPayement(Request $request, $router)
    {
		$session = $request->getSession();
        $repositoryPanier = $this->em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $this->em->getRepository('JMBilleterieBundle:Billet');
		/* Verification si le panier est vide ou non */
		if(!($session->has('Panier'))){
			$session->getFlashBag()->add('alert', "Panier vide !");
			$url = $router->generate('billeterie');
			return new RedirectResponse($url);
		}
        $panier = $repositoryPanier->find($session->get('Panier'));
		$listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
		/* Generation des url au format brute */
		$curl = $router->generate('billeterie_pay_paypal_cancel', array(), true);
		$rurl = $router->generate('billeterie_pay_paypal_success', array(), true);
		/* Definition des parametre de la commande Paypal */
		$params = array(
			'METHOD' => 'SetExpressCheckout',
			'VERSION' => $this->paypalVersion,
			'USER' => $this->paypalUser,
			'SIGNATURE' => $this->paypalSignature,
			'PWD' => $this->paypalPwd,
			'RETURNURL' => $rurl,
			'CANCELURL' => $curl,
			
			'PAYMENTREQUEST_0_AMT' => $panier->getPrixTotal(),
			'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
			
			'L_PAYMENTREQUEST_0_NAME0' => 'Billet Musée du Louvre',
			'L_PAYMENTREQUEST_0_AMT0' => $panier->getPrixTotal()
		);
		$params = http_build_query($params);
		/* Le endpoint a passer en format production */
		$endpoint = 'https://api-3T.sandbox.paypal.com/nvp';
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $endpoint,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_VERBOSE => 1
		));
		$response = curl_exec($curl);
		$responseArray = array();
		parse_str($response, $responseArray);
		return $responseArray;
    }
	
    function verifPaypalSuccess(Request $request, $router)
    {
		$session = $request->getSession();
		$repository = $this->em->getRepository('JMBilleterieBundle:BilletDate');
        $repositoryPanier = $this->em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $this->em->getRepository('JMBilleterieBundle:Billet');
		if(!($session->has('Panier'))){
			$session->getFlashBag()->add('alert', "Panier vide !");
			$url = $this->get('router')->generate('billeterie');
			return new RedirectResponse($url);
		}
        $panier = $repositoryPanier->find($session->get('Panier'));
		$listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
		$qty = 0;
		/* Definition du prix pour une verification */
		$prixTotal = $panier->getPrixTotal();
		$prixTotal = $prixTotal . '.00';
		foreach($listeBillets as $billet){
			$qty++;
		}
		/* Generation des url au format brute */
		$curl = $router->generate('billeterie_pay_paypal_cancel', array(), true);
		$rurl = $router->generate('billeterie_pay_paypal_success', array(), true);
		
		/* Les params utiles a paypal pour recuperer les infos sur la vente */
		$params = array(
			'METHOD' => 'GetExpressCheckoutDetails',
			'VERSION' => $this->paypalVersion,
			'TOKEN' => $_GET['token'],
			'USER' => $this->paypalUser,
			'SIGNATURE' => $this->paypalSignature,
			'PWD' => $this->paypalPwd
		);
		$params = http_build_query($params);
		/* Le endpoint a passer en format production */
		$endpoint = 'https://api-3T.sandbox.paypal.com/nvp';
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $endpoint,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_VERBOSE => 1
		));
		$response = curl_exec($curl);
		$responseArray = array();
		parse_str($response, $responseArray);
		if(curl_errno($curl)){
			curl_close($curl);
		} else {
			/* Verification de la Vente */
			if($responseArray['ACK'] === 'Success'){
				/* es-que l'argent donné est bien de la même valeur que le prix */
				if($responseArray['PAYMENTREQUEST_0_ITEMAMT'] === $prixTotal){
					/* es-que l'argent a bien été reversé au bon vendeur */
					if($responseArray['PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID'] === $this->paypalAccVendeur){
						/* Si tout est bon, mise a jour du nombre de billets vendu pour cette date et on passe les billets en mode "Payer" */
						foreach($listeBillets as $billet){
							$dateReservation = $billet->getDateReservation();
							$billetDates = $repository->findBy(
								 array('date' => $dateReservation)
							);
							$billet->setPayer(true);
							/* SI il n'éxiste pas de ligne pour la date du billet, on en crée une et on ajoute 1 place prise */
							if(!isset($billetDates[0])){
								$billetDate = new BilletDate();
								$billetDate->setDate($dateReservation);
								$billetDate->addPlacePrise();
								$this->em->persist($billetDate);
								$this->em->flush();
							} else {
								/* SI il éxiste déjà une ligne pour cette date, on ajoute juste une place prise */
								foreach ($billetDates as $billetDate) {
									$billetDate->addPlacePrise();
								}
							}
							$this->em->persist($billetDate);
							$this->em->persist($billet);
						}
						$this->em->flush();
					}
					return true;
				}
			} else {
				return false;
			}
			curl_close($curl);
		}
    }
    function verifStripePayement(Request $request)
    {
		$session = $request->getSession();
        $repository = $this->em->getRepository('JMBilleterieBundle:BilletDate');
        $repositoryPanier = $this->em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $this->em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($session->get('Panier'));
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        $prixTotal = $panier->getPrixTotal();
        \Stripe\Stripe::setApiKey($this->stripeKey);

        // Get the credit card details submitted by the form
        $token = $_POST['stripeToken'];

        // Create the charge on Stripe's servers - this will charge the user's card
        try {
          $charge = \Stripe\Charge::create(array(
            "amount" => ($prixTotal * 100), // amount in cents, again
            "currency" => "eur",
            "source" => $token,
            "description" => "Payement de billets"
            ));
            foreach($listeBillets as $billet){
                $billet->setPayer(true);
                /* On récupere la date de la reservation du billet */
                $dateReservation = $billet->getDateReservation();
                /* Chargement de la liste des billets pris par date on ne récupére que pour la date du billet */
                $billetDates = $repository->findBy(
                     array('date' => $dateReservation)
                );                

                /* SI il n'éxiste pas de ligne pour la date du billet, on en crée une et on ajoute 1 place prise */
                if(!isset($billetDates[0])){
                    $billetDate = new BilletDate();
                    $billetDate->setDate($dateReservation);
                    $billetDate->addPlacePrise();
                    $this->em->persist($billetDate);
					$this->em->flush();
                } else {
                    /* SI il éxiste déjà ne ligne pour cette date, on ajoute juste une place prise */
                    foreach ($billetDates as $billetDate) {
                        $billetDate->addPlacePrise();
                    }
					$this->em->persist($billetDate);
					$this->em->persist($billet);
                }
            }
			$this->em->flush();
        } catch(\Stripe\Error\Card $e) {
            $session = $request->getSession();
            $session->getFlashBag()->add('alert', "Carte invalide");
        }
		return true;
    }
}

?>