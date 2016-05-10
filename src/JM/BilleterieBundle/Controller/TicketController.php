<?php
namespace JM\BilleterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JM\BilleterieBundle\Entity\Billet;
use JM\BilleterieBundle\Entity\Panier;
use JM\BilleterieBundle\Entity\NbBillet;
use JM\BilleterieBundle\Form\BilletType;
use JM\BilleterieBundle\Form\NbrBilletType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketController extends Controller
{
    public function viewAction()
    {
        return $this->render('JMBilleterieBundle:Ticket:ticketChoice.html.twig');
    }
    public function panierAction(Request $request)
    {   
        $session = $request->getSession();
        if($session->has('Panier')){
            $em = $this->getDoctrine()->getManager();
            $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
            $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
            $panier = $repositoryPanier->find($session->get('Panier'));
            $prixTotal = 0;
            $famille = 0;
            if(isset($panier)){
                $listeBillets = $repositoryBillet->findBy(
                    array('panier' => $panier)
                );
                foreach($listeBillets as $k=>$billet){
                    $prix = $billet->getPrix();
                    if($billet->getPayer()){
                        unset($listeBillets[$k]);
                        $prix = 0;
                    }
                    if($prix == 99){
                        $famille++;
                    } else {
                        $prixTotal += $prix;
                    }
                    if($famille == 4){
                        $famille = 0;
                        $prixTotal += 35;
                    }
                }
                $panier->setPrixTotal($prixTotal);
                $em->persist($panier);
                $em->flush();
                if(empty($listeBillets)){
                    $session->getFlashBag()->add('alert', "Le panier est vide !");
                    $url = $this->get('router')->generate('billeterie');
                    return new RedirectResponse($url);
                }
                return $this->render('JMBilleterieBundle:Ticket:panier.html.twig', array(
                    'panier' => $listeBillets,
                    'prixTotal' => $panier->getPrixTotal(),
                    'panierId' => $session->get('Panier'),
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
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($session->get('Panier'));
        $listeBillets = $repositoryBillet->findBy(
                    array('panier' => $panier)
                );
        foreach ($listeBillets as $billet){
            $codeUnique = $billet->getCodeUnique();
            if($codeUnique == $id ){
                $nom = $billet->getNom();
                $em->remove($billet);
            }
        }
        $em->flush();
        $listeBillets = $repositoryBillet->findBy(
            array('panier' => $panier)
        );
        $listeBilletsNom = array();
        foreach($listeBillets as $billet)
        {
            $nomBis = $billet->getNom();
            if($nomBis === $nom){
                array_push($listeBilletsNom, $billet);
            }
        }
        $tarificateurDeBillet = $this->get('jm_billeterie.tarifbillet');
        if($tarificateurDeBillet->setPrixBillets($listeBilletsNom)){
            return $this->redirect($this->generateUrl('billeterie_panier'));            
        }
        
        return $this->redirect($this->generateUrl('billeterie'));
    }
    
    public function addTicketE1Action($demiJour, Request $request)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
		$panierVerif = $session->get('Panier');
		if(empty($panierVerif)){
            $panier = new Panier();
            $em->persist($panier);
            $em->flush();
            $session->set('Panier', $panier->getId());
        } else if(!isset($panierVerif)){
            $panier = new Panier();
            $em->persist($panier);
            $em->flush();
            $session->set('Panier', $panier->getId());			
		}
        $nbBillet = new NbBillet();
        $form = $this->get('form.factory')->create(new NbrBilletType(), $nbBillet);
        if($form->handleRequest($request)->isValid()) {
            $session->set('dateReservation', $nbBillet->getDateReservation());
            $session->set('email', $nbBillet->getEmail());
            $session->set('nom', $nbBillet->getNom());
            $url = $this->get('router')->generate(
                'billeterie_ticketadde2',
                array('demiJour' => $demiJour,
                      'nbBillet' => $nbBillet->getNombre(),
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
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        /* Chargement du service de tarification de billet */   
        $tarificateurDeBillet = $this->get('jm_billeterie.tarifbillet');
        $repositoryPanier = $em->getRepository('JMBilleterieBundle:Panier');
        $repositoryBillet = $em->getRepository('JMBilleterieBundle:Billet');
        $panier = $repositoryPanier->find($session->get('Panier'));
        if(!$session->has('dateReservation') || !$session->has('email')){
            $request->getSession()->getFlashBag()->add('alert', 'La date de réservation ou l\'e-mail est invalide.');
            return $this->redirect($this->generateUrl('billeterie'));
        }
        if($nbBillet <= 0) {         
            $listeBillets = $repositoryBillet->findBy(
                array('panier' => $panier)
            );
            if(empty($listeBillets)){
                $request->getSession()->getFlashBag()->add('alert', 'Liste de billet vide...');
                return $this->redirect($this->generateUrl('billeterie'));
            }
            if($tarificateurDeBillet->setPrixBillets($listeBillets)){
                $request->getSession()->getFlashBag()->set('alert', 'Tous les billets pour la famille ' . $session->get('nom') . ' ont été validés.');
                return $this->redirect($this->generateUrl('billeterie'));
            }
            $session = $request->getSession();
            $url = $this->get('router')->generate('billeterie');
            return new RedirectResponse($url);
            
        } else if($nbBillet > 30) {
            $request->getSession()->getFlashBag()->add('alert', 'Attention, vous ne pouvez pas créer plus de 30 billets d\'un coups ! ');
            return $this->redirect($this->generateUrl('billeterie'));            
        } else {
            /* Création d'un nouveau billet et chargement des properties */
            $billet = new Billet();
            $billet->setDateReservation($session->get('dateReservation'));
            $billet->setEmail($session->get('email'));
            $billet->setNom($session->get('nom'));
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

                /* On vérifie que le billet est correct vià le service de vérification */
                if($verificateurDeBillet->isValidBillet($billet, $request)){
                    /* Ajout d'un flashbag de confirmation de création du billet */
					$nbBillet -= 1;
                    $request->getSession()->getFlashBag()->add('alert', 'Billet enregistré avec succès dans le panier il vous en reste ' . $nbBillet . ' à faire.');
                } else {
                    /* Si le billet n'est pas valider on affiche un méssage d'érreur */
                    $request->getSession()->getFlashBag()->add('alert', $session->get('error'));
                    return $this->redirect($this->generateUrl('billeterie'));                    
                }
                $em->persist($tarificateurDeBillet->setPrixBillet($billet));
                $em->flush();
                $url = $this->get('router')->generate(
                'billeterie_ticketadde2',
                array('demiJour' => $demiJour,
                      'nbBillet' => $nbBillet
                     )
                );
                return new RedirectResponse($url);
            }
        }
        return $this->render('JMBilleterieBundle:Ticket:addTicket.html.twig', array(
            'form' => $form->createView(),
            'demiJour' => $demiJour,
            'nbBillet' => $nbBillet,
        ));
    }
}

?>