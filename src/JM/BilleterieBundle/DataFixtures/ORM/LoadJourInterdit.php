<?php
// src/OC/PlatformBundle/DataFixtures/ORM/LoadCategory.php

namespace JM\BilleterieBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use JM\BilleterieBundle\Entity\JourInterdit;

class LoadJourInterdit implements FixtureInterface
{
  // Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
  public function load(ObjectManager $manager)
  {
    // Liste des noms de catégorie à ajouter
      $jours = array(
          '1 5',
          '1 11',
          '25 12',
          '1 1',
          '28 3',
          '1 5',
          '8 5',
          '5 5',
          '16 5',
          '15 8',
          '1 11',
          '11 11',
          '%2',
          '%0'
      );      
      $messages = array(
          '1 5',
          '1 11',
          '25 12',
          '1 1',
          '28 3',
          '1 5',
          '8 5',
          '5 5',
          '16 5',
          '15 8',
          '1 11',
          '11 11',
          '%2',
          '%0'      
      );
      
      foreach (array_combine($jours, $messages) as $jour => $message) {
          // On crée la catégorie
          $jourInterdit = new JourInterdit();
          $jourInterdit->setJour($jour);
          $jourInterdit->setMessage($message);

          // On la persiste
          $manager->persist($jourInterdit);
      }
    // On déclenche l'enregistrement de toutes les catégories
    $manager->flush();
  }
}