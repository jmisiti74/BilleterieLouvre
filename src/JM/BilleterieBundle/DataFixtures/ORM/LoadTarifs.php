<?php
// src/OC/PlatformBundle/DataFixtures/ORM/LoadCategory.php

namespace JM\BilleterieBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use JM\BilleterieBundle\Entity\Tarifs;

class LoadTarifs implements FixtureInterface
{
  // Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
  public function load(ObjectManager $manager)
  {
    // Liste des noms de catégorie à ajouter
      $prix = array(
          '0',
          '8',
          '10',
          '12',
          '16',
          '99'
          
      );      
      $type = array(
          'bebe',
          'enfant',
          'tarifReduit',
          'senior',
          'normal',
          'famille'  
      );
      
      foreach (array_combine($prix, $type) as $prix => $type) {
          // On crée la catégorie
          $tarifs = new Tarifs();
          $tarifs->setPrix($prix);
          $tarifs->setType($type);

          // On la persiste
          $manager->persist($tarifs);
      }
    // On déclenche l'enregistrement de toutes les catégories
    $manager->flush();
  }
}