<?php

namespace JM\BilleterieBundle\TarificationBillet;
use Doctrine\ORM\EntityManager;
use JM\BilleterieBundle\Entity\Tarifs;

class JMTarificationBillet
{
	private $em;
    
	public function __construct(EntityManager $manager) 
	{
		$this->em = $manager;
	}
    function setPrixBillet($listeBillets)
    {
        /* Définition des variables utiles */
        $nbEnfant = 0;
        $nbAdulte = 0;
        $billetFamille = 0;
        $age = 0;
        $billetTarifReduit = false;
        $repository = $this->em->getRepository('JMBilleterieBundle:Tarifs');
        $tarifs = $repository->findAll();
        /* definition des tarifs */
        foreach($tarifs as $tarif){
            define($tarif->getType(), $tarif->getPrix(), true);
        }
        /* Pour chaque billets (avec le même nom de famille) le tri est fait en amont */
        foreach($listeBillets as $billet){
            /* On verifie s'il a un tarif reduit */
            $billetTarifReduit = $billet->getTarifReduit();
            /* Recuperation de l'âge */
            $age = $billet->getAge();
            /* Definition des prix en fonction de l'âge */
            if($age >= 60){
                $billet->setPrix(senior);
            } else if($age < 60 && $age >= 12){
                $billet->setPrix(normal);
            } else if($billetTarifReduit){
                $billet->setPrix(tarifReduit);                
            } else if($age < 12 && $age >= 4){
                $billet->setPrix(enfant);
            } else if($age < 4){
                $billet->setPrix(bebe);
            }
            
            /* Vérification du nombre d'adulte et du nombre d'enfant */
            if($age >= 4 && $age < 18){
                $nbEnfant++;
            } else if($age >= 18){
                $nbAdulte++;
            }
        }
        
        
        /* tant qu'il y a 2 adultes et 2 enfants on applique le tarif famille */
        while($nbEnfant >= 2){
            if($nbAdulte >= 2){
                $billetFamille = 4;                
                /* ie & ia pour faire le décompte pour être sur de n'appliquer le tarif famille qu'a 2enfant et 2adulte */
                $ie = 2;
                $ia = 2;
                foreach($listeBillets as $billet){
                    /* Recuperation de l'age et du prix pour comparaison */
                    $ageBis = $billet->getAge();
                    $prixBis = $billet->getPrix();
                    /* On vérifie que le billet n'a pas déjà été passé en tarif famille */
                    if($billetFamille > 0 && $prixBis != 99){
                        if($ie > 0 && $ageBis < 18 && $ageBis >= 4){
                            $billet->setPrix(famille);
                            $ie--;
                            $billetFamille--;
                        } else if($ia > 0 && $ageBis >= 18){
                            $billet->setPrix(famille);
                            $ia--;
                            $billetFamille--;
                        }
                    }
                }
            }
            $nbAdulte -= 2;
            $nbEnfant -= 2;
        }
        /* Une fois la tarification faite, on envoie les billets en Base de donnée */
        foreach($listeBillets as $billet){
            $this->em->persist($billet);
        }
        $this->em->flush();
        return true;
    }
}
?>