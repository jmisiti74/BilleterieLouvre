<?php

namespace JM\BilleterieBundle\VerifBillet;
use JM\BilleterieBundle\Entity\Billet;
use JM\BilleterieBundle\Entity\JourInterdit;
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
        $repository = $this->em->getRepository('JMBilleterieBundle:JourInterdit');
        $joursInterdit = $repository->findAll();
        /* On vérifie que la réservation n'est pas pour hier ou + */
        if(!(date($dateReservation->format('Ymd')) >= date($dateNow->format('Ymd')))){
            $_SESSION["Error"] = 'Vous ne pouvez pas commander de billet pour un jour déjà passé.';
            return false;
        }
        
        /* On vérifie que la réservation n'est pas pour les jours interdits */
        foreach ($joursInterdit as $jourInterdit){
            $j = $jourInterdit->getJour();
            if(($dateReservation->format('j n') === $j) || ($dateReservation->format('%w') === $j)){
                $_SESSION["Error"] = $jourInterdit->getMessage();
                return false;
            }
            
        }
        
        /* On vérifie si la réservation est pour le jour même && en journée, si oui on regarde si l'heure (14H) n'est pas passer */
        if(($demiJour === false) && (date($dateReservation->format('m/y/d')) == date($dateNow->format('m/y/d'))) && (date($dateNow->format('H')) >= '14' )){
            $_SESSION["Error"] = 'Vous ne pouvez pas commander de billet "Journée" pour le jour même à partir de 14H.';
            return false;
        }
        
        /* On vérifie que moins de 1000 billet ont été achetée */
        foreach ($billetDates as $placePrises) {
            $placePrise = $placePrises->getPlacePrise();
        }
        if(isset($placePrise)){
            if($placePrise >= 1000){
                $_SESSION["Error"] = 'Il n\'y a plus de place pour le ' . $dateReservation->format('d/m/Y ') . '.';
                return false;
            }
        }
        
        return true;
    }
}