<?php

namespace JM\BilleterieBundle\VerifBillet;
use JM\BilleterieBundle\Entity\Billet;
use JM\BilleterieBundle\Entity\billetDate;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;

class JMVerifBillet
{
	private $em;
	
	public function __construct(EntityManager $manager) 
	{
		$this->em = $manager;
	}
    public function isValidBillet(Billet $billet)
    {
        $dateNow = new \DateTime;
        $dateReservation = $billet->getDateReservation();
        $demiJour = $billet->getDemiJour();
        $repository = $this->em->getRepository('JMBilleterieBundle:billetDate');
        $billetDates = $repository->findBy(
            array('date' => $dateReservation)
        );
        
        /* On vérifie que la réservation n'est pas pour les jours interdits */
        if(($dateReservation->format('j n') === '1 5') || ($dateReservation->format('j n') === '1 11') || ($dateReservation->format('j n') === '25 12') || ($dateReservation->format('%w') === '%2')){
            return false;
        }
        
        /* On vérifie que la réservation n'est pas pour hier ou + */
        if(date($dateReservation->format('m/y/d')) >= date($dateNow->format('m/y/d'))){
            
        } else {
            return false;
        }
        
        /* On vérifie si la réservation est pour le jour même && en journée, si oui on regarde si l'heure (14H) n'est pas passer */
        if(($demiJour === false) && (date($dateReservation->format('m/y/d')) == date($dateNow->format('m/y/d'))) && (date($dateNow->format('H')) >= '14' )){
            return false;
        }
        
        /* On vérifie que moins de 1000 billet ont été achetée */
        foreach ($billetDates as $placePrises) {
            $placePrise = $placePrises->getPlacePrise();
        }
        if(isset($placePrise)){
            if($placePrise >= 1000){
                return false;
            }
        }
        
        return true;
    }
}