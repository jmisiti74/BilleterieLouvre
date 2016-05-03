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
          '8 5',
          '5 5',
          '16 5',
          '15 8',
          '11 11',
          '%2',
          '%0'
      );      
      $messages = array(
          'Le 1er mais est un jour férié. Nous sommes fermé.',
          'Le 1er novembre est un jour férié. Nous sommes fermé.',
          'Le 25 décembre c\'est noël. Nous sommes fermé.',
          'Le 1er janvier c\'est le nouvel an. La réservation n\'est pas disponnible.',
          'Le 28 mars est un jour férié. La réservation n\'est pas disponnible.',
          'Le 8 mai est un jour férié. La réservation n\'est pas disponnible.',
          'Le 5 mai est un jour férié. La réservation n\'est pas disponnible.',
          'le 16 mai est un jour férié. La réservation n\'est pas disponnible.',
          'le 15 août est un jour férié. La réservation n\'est pas disponnible.',
          'Le 11 novembre est un jour férié. La réservation n\'est pas disponnible.',
          'Le musée est fermé le mardi.',
          'Le dimanche, la réservation n\'est pas disponnible.'      
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