<?php

namespace JM\BilleterieBundle\TarificationBillet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;
use JM\BilleterieBundle\Entity\Billet;

class JMTarificationBillet
{
	private $em;
    
	public function __construct(EntityManager $manager) 
	{
		$this->em = $manager;
	}
    function setPrixBillet($listeBillets)
    {
        $nbEnfant = 0;
        $nbAdulte = 0;
        $billetFamille = 0;
        $age = 0;
        $billetTarifReduit = false;
        foreach($listeBillets as $billet){
            $billetTarifReduit = $billet->getTarifReduit();
            $age = $billet->getAge();
            if($age >= 60){
                $billet->setPrix(12);
            } else if($age < 60 && $age >= 12){
                $billet->setPrix(16);
            } else if($age < 12 && $age >= 4){
                $billet->setPrix(8);
            } else if($age < 4){
                $billet->setPrix(0);
            }
            
            if($billet->getPrix() > 10){
                if($billetTarifReduit){
                    $billet->setPrix(10);
                }
            }
            
            if($age >= 4 && $age < 18){
                $nbEnfant++;
            } else if($age >= 18){
                $nbAdulte++;
            }
        }
        while($nbEnfant >= 2){
            if($nbAdulte >= 2){
                $billetFamille = 4;
                $ie = 2;
                $ia = 2;
                foreach($listeBillets as $billet){
                    $ageBis = $billet->getAge();
                    $prixBis = $billet->getPrix();
                    if($billetFamille > 0 && !($prixBis === 99)){
                        if($ie > 0 && $ageBis < 18 && $ageBis >= 4){
                            $billet->setPrix(99);
                            $ie--;
                            $billetFamille--;
                        } else if($ia > 0 && $ageBis >= 18){
                            $billet->setPrix(99);
                            $ia--;
                            $billetFamille--;
                        }
                    }
                }
            }
            $nbAdulte -= 2;
            $nbEnfant -= 2;
        }
        foreach($listeBillets as $billet){
            $this->em->persist($billet);
        }
        $this->em->flush();
        return true;
    }
}