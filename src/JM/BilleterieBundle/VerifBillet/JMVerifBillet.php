<?php

namespace JM\BilleterieBundle\VerifBillet;
use JM\BilleterieBundle\Entity\Billet;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class JMVerifBillet
{
	private $em;
	
	public function __construct(EntityManager $manager) 
	{
		$this->em = $manager;
	}
    public function isValidBillet(Billet $billet, Request $request)
    {
        $session = $request->getSession();
        /* verification de la validité du billet (ce fait a chaque création de nouveau billet) */
        $dateNow = new \DateTime;
        $dateReservation = $billet->getDateReservation();
        $demiJour = $billet->getDemiJour();
        $repository = $this->em->getRepository('JMBilleterieBundle:BilletDate');
        $billetDates = $repository->findBy(
            array('date' => $dateReservation)
        );
        $billetDatesPurge = $repository->findAll();
        $repository = $this->em->getRepository('JMBilleterieBundle:JourInterdit');
        $joursInterdit = $repository->findAll();
        /* On vérifie que la réservation n'est pas pour hier ou + */
        if(!(date($dateReservation->format('Ymd')) >= date($dateNow->format('Ymd')))){
            $session->set('error', 'Vous ne pouvez pas commander de billet pour un jour déjà passé.');
            return false;
        }
        
        /* On vérifie que la réservation n'est pas pour les jours interdits */
        foreach ($joursInterdit as $jourInterdit){
            $j = $jourInterdit->getJour();
            if(($dateReservation->format('j-n') === $j) || ($dateReservation->format('%w') === $j)){
                $session->set('error', $jourInterdit->getMessage());
                return false;
            }
            
        }
        
        /* On vérifie si la réservation est pour le jour même && en journée, si oui on regarde si l'heure (14H) n'est pas passer */
        if(($demiJour === false) && (date($dateReservation->format('m/y/d')) == date($dateNow->format('m/y/d'))) && (date($dateNow->format('H')) >= '14' )){
            $session->set('error', 'Vous ne pouvez pas commander de billet "Journée" pour le jour même à partir de 14H.');
            return false;
        }
        
        /* On vérifie que moins de 1000 billet ont été achetée */
        foreach ($billetDates as $placePrises){
            $placePrise = $placePrises->getPlacePrise();
        }
        if(isset($placePrise)){
            if($placePrise >= 1000){
                $session->set('error', 'Il n\'y a plus de place pour le ' . $dateReservation->format('d/m/Y ') . '.');
                return false;
            }
        }
        /* Lancement de la purge de billets */
        foreach($billetDatesPurge as $k=>$billetPurge){
            $datePurge = $billetPurge->getDate();
            if($datePurge < $dateNow){
                $this->em->remove($billetDatesPurge[$k]);
            }
        }
        
        return true;
    }
}

?>