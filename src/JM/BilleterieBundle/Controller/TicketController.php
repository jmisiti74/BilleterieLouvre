<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JM\BilleterieBundle\Entity\Billet;
use JM\BilleterieBundle\Entity\Panier;
use JM\BilleterieBundle\Entity\nbBillet;
use JM\BilleterieBundle\Entity\billetDate;
use JM\BilleterieBundle\Form\BilletType;
use JM\BilleterieBundle\Form\NbrBilletType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketController extends Controller
{
    public function viewAction()
    {
        return $this->render('JMBilleterieBundle:Ticket:ticketChoice.html.twig');
    }
    public function infosAction()
    {
        unset($_SESSION["Panier"]);
        return $this->render('JMBilleterieBundle:Ticket:infos.html.twig');
    }
    public function panierAction(Request $request)
    {   
        if(isset($_SESSION["Panier"])){
            $em = $this->getDoctrine()->getManager();
            $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
            $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
            $panier = $repositoryPanier->find($_SESSION["Panier"]);
            if(isset($panier)){
                $listeBillets = $repositoryBillet->findBy(
                    array('panier' => $panier)
                );
                return $this->render('JMBilleterieBundle:Ticket:panier.html.twig', array(
                    'panier' => $listeBillets,
                ));
            }
        }
        $session = $request->getSession();
        $session->getFlashBag()->add('alert', "Le panier est vide !");
        $url = $this->get('router')->generate('billeterie');
        return new RedirectResponse($url);
    }
    
    public function panierclearAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($_SESSION["Panier"]);
        $listeBillets = $repositoryBillet->findBy(
                    array('panier' => $panier)
                );
        foreach ($listeBillets as $billet){
            $codeUnique = $billet->getCodeUnique();
            if($codeUnique == $id ){
                $em->remove($billet);
            }
        }
        $em->flush();
        return $this->redirect($this->generateUrl('billeterie_panier'));
    }
    
    public function addTicketE1Action($demiJour, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if(!isset($_SESSION["Panier"]) || empty($_SESSION["Panier"])){
            $panier = new Panier();
            $em->persist($panier);
            $em->flush();
            $_SESSION["Panier"] = $panier->getId();
        }
        $nbBillet = new nbBillet();
        $form = $this->get('form.factory')->create(new NbrBilletType(), $nbBillet);
        if($form->handleRequest($request)->isValid()) {
            $nombreDeBillet = $nbBillet->getNombre();
            $_SESSION["dateReservation"] = $nbBillet->getDateReservation();
            $_SESSION["email"] = $nbBillet->getEmail();
            $_SESSION["nom"] = $nbBillet->getNom();
            $url = $this->get('router')->generate(
                'billeterie_ticketadde2',
                array('demiJour' => $demiJour,
                      'nbBillet' => $nombreDeBillet
                     )
            );
            return new RedirectResponse($url);
        }
        return $this->render('JMBilleterieBundle:Ticket:beforeAddTicket.html.twig', array(
          'form' => $form->createView(),
          'demiJour' => $demiJour,
        ));
    }
    
    public function addTicketE2Action($demiJour, $nbBillet, Request $request)
    {   
        $em = $this->getDoctrine()->getManager();
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($_SESSION["Panier"]);
        if(!isset($_SESSION["dateReservation"]) || !isset($_SESSION["email"])){
            $request->getSession()->getFlashBag()->add('alert', 'La date de réservation ou l\'e-mail n\'est pas valide.');
            return $this->redirect($this->generateUrl('billeterie'));
        }
        if($nbBillet <= 0) {
            /* Chargement du service de tarification de billet */
            $tarificateurDeBillet = $this->get('jm_billeterie.tarifbillet');
            
            $listeBillets = $repositoryBillet->findBy(
                array('panier' => $panier)
            );
            if(empty($listeBillets)){
                $request->getSession()->getFlashBag()->add('alert', 'Liste de billets vide...');
                return $this->redirect($this->generateUrl('billeterie'));
            }
            if($tarificateurDeBillet->setPrixBillet($listeBillets)){
                $request->getSession()->getFlashBag()->add('alert', 'Tous les billets pour la famille ' . $_SESSION["nom"] . ' ont été valider.');
                return $this->redirect($this->generateUrl('billeterie'));
            }
            $session = $request->getSession();
            $session->getFlashBag()->add('alert', "STOP!!");
            $url = $this->get('router')->generate('billeterie');
            return new RedirectResponse($url);  
            
        } else if($nbBillet >= 30) {
            $request->getSession()->getFlashBag()->add('alert', 'Attention, vous ne pouvez pas créer plus de 30 billets d\'un coups ! ');
            return $this->redirect($this->generateUrl('billeterie'));            
        } else {
            /* Chargement de l'entity manager */
            /* Chargement du repository de la liste des billets pris par date */
            $repository = $em->getRepository('JMBilleterieBundle:billetDate');
            /* Création d'un nouveau billet et chargement des properties */
            $billet = new Billet();
            $billet->setDateReservation($_SESSION["dateReservation"]);
            $billet->setEmail($_SESSION["email"]);
            $billet->setNom($_SESSION["nom"]);
            $billet->setPanier($panier);
            /* Chargement du formulaire de création de billet */
            $form = $this->get('form.factory')->create(new BilletType(), $billet);
            /* Chargement du service de vérification de billet */
            $verificateurDeBillet = $this->get('jm_billeterie.verifbillet');

            if($form->handleRequest($request)->isValid()) {
                /* Si le formulaire est OK, création du code unique du billet via la fonction uniqid() */
                $billet->setCodeUnique(uniqid('', true));
                /* Si le billet est en demi-journée le billet passe en mode demi-journée */
                if($demiJour == 'true'){
                    $billet->setDemiJour(true);                
                } else {
                    /* Sinon, il est en mode journée */
                    $billet->setDemiJour(false);
                }

                /* On récupère la date de la reservation du billet */
                $dateReservation = $billet->getDateReservation();

                /* On vérifie que le billet est correct vià le service de vérification */
                if($verificateurDeBillet->isValidBillet($billet)){
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
                    } else {
                        /* SI il éxiste déjà une ligne pour cette date, on ajoute juste une place prise */
                        foreach ($billetDates as $billetDate) {
                            $billetDate->addPlacePrise();
                            $em->persist($billetDate);
                        }
                    }
                    /* On flush le tout pour le sauvegarder */
                    $em->persist($billet);
                    $em->flush();
                    /* ^-^-^ CETTE PARTIE DOIT ÊTRE DEPLACER VERS LA ZONE DE PAYEMENT ^-^-^ */

                    /* Ajout d'un flashbag de confirmation de création du billet */
                    $request->getSession()->getFlashBag()->add('alert', 'Billet enregistrer avec succès dans le panier');
                } else {
                    /* Si le billet n'est pas valider on affiche un méssage d'érreur */
                    $request->getSession()->getFlashBag()->add('alert', $_SESSION["Error"]);
                    return $this->redirect($this->generateUrl('billeterie'));                    
                }
                $nbBillet -= 1;
                $url = $this->get('router')->generate(
                'billeterie_ticketadde2',
                array('demiJour' => $demiJour,
                      'nbBillet' => $nbBillet
                     )
                );
                return new RedirectResponse($url);
            }
        }
        return $this->render('JMBilleterieBundle:Ticket:AddTicket.html.twig', array(
            'form' => $form->createView(),
            'demiJour' => $demiJour,
            'nbBillet' => $nbBillet,
        ));
    }
}