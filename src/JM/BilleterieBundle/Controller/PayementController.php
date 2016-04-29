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
    /* VVV POUR STRIPE VVV */
    public function stripeAction()
    {   
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('JMBilleterieBundle:billetDate');
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($_SESSION["Panier"]);
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