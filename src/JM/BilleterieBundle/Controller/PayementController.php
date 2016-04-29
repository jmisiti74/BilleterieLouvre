<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RequestContext;
use JM\BilleterieBundle\Entity\billetDate;
use Symfony\Component\Routing\Generator\UrlGenerator;

class PayementController extends Controller
{
    /* VVV POUR PAYPAL VVV */
    public function paypalIpnAction(Request $request)
    {
        $session = $request->getSession();
        // Adresse e-mail du vendeur
        $email_vendeur = "test-vendeur@xjejevbx.fr";
        // Envoi des infos a Paypal
        $req = "cmd=_notify-validate";
        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req.= "&$key=$value";
        }
        $fp = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
        curl_setopt($fp, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($fp, CURLOPT_POST, 1);
        curl_setopt($fp, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($fp, CURLOPT_POSTFIELDS, $req);
        curl_setopt($fp, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($fp, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($fp, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($fp, CURLOPT_HTTPHEADER, array('Connection: Close'));
        if( !($res = curl_exec($fp)) ) {
            curl_close($fp);
            exit;
        }
        curl_close($fp);
        // Le paiement est validé
        if (strcmp(trim($res), "VERIFIED") == 0) {
            // Vérifier que le statut du paiement est complet
            if ($_POST['payment_status'] == "Completed") {
                // Vérification de l'e-mail du vendeur
                if ($email_vendeur == $_POST['receiver_email']) {
                    // Récupération du montant VRAI du panier
                    $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
                    $panier = $repositoryPanier->find($session->get('Panier'));
                    $prixTotal = $panier->getPrixTotal();
                    $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
                    $listeBillets = $repositoryBillet->findBy(
                        array('panier' => $panier)
                    );
                    // Vérification de la concordance du montant
                    if ($_POST['mc_gross'] == $prixTotal) {
                        // Requête pour la mise à jour du statut de la commande => Payer à VRAI
                        foreach($listeBillets as $billet){
                            $billet->setPayer(true);
                            $em->persist($billet);
                        }
                        $em->flush();
                        // Envoi du mail de récapitulatif de la commande à l'acheteur et au vendeur
                    }
                }   
            }
        }
    }
    public function paypalReturnAction(Request $request)
    {
        $session = $request->getSession();
        $session->getFlashBag()->add('alert', "Payement effactué !!! :D");
        $url = $this->get('router')->generate('billeterie');
        return new RedirectResponse($url);  
    }
    /* ^^^ POUR PAYPAL ^^^ */
    
    /* VVV POUR STRIPE VVV */
    public function stripeAction()
    {   
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('JMBilleterieBundle:billetDate');
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($session->get('Panier'));
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        $prixTotal = $panier->getPrixTotal();
        \Stripe\Stripe::setApiKey("sk_test_cXCQHzx7IQGuUuhh4OXubaNT");

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
                /* On récupère la date de la reservation du billet */
                $dateReservation = $billet->getDateReservation();
                /* Chargement de la liste des billets pris par date on ne récupère que pour la date du billet */
                $billetDates = $repository->findBy(
                     array('date' => $dateReservation)
                );                

                /* V-V-V CETTE PARTIE DOIT ÊTRE DEPLACER VERS LA ZONE DE PAYEMENT V-V-V */
                /* SI il n'éxiste pas de ligne pour la date du billet, on en crée une et on ajoute 1 place prise */
                if(!isset($billetDates[0])){
                    $billetDate = new billetDate();
                    $billetDate->setDate($dateReservation);
                    $billetDate->addPlacePrise();
                    $em->persist($billetDate);
                    $em->flush();
                } else {
                    /* SI il éxiste déjà une ligne pour cette date, on ajoute juste une place prise */
                    foreach ($billetDates as $billetDate) {
                        $billetDate->addPlacePrise();
                    }
                }
                $em->persist($billetDate);
                /* On flush le tout pour le sauvegarder */
                $em->persist($billet);
            }
            $em->flush();
        } catch(\Stripe\Error\Card $e) {
            $session = $request->getSession();
            $session->getFlashBag()->add('alert', "Carte invalide");
        }
        $url = $this->get('router')->generate('billeterie_pdf');
        return new RedirectResponse($url);
    }
    /* ^^^ POUR STRIPE ^^^ */
}
?>