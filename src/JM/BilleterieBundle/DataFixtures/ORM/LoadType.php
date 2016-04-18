<?php
// src/OC/PlatformBundle/DataFixtures/ORM/LoadCategory.php

namespace JM\BilleterieBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use JM\BilleterieBundle\Entity\Type;

class LoadCategory implements FixtureInterface
{
  // Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
  public function load(ObjectManager $manager)
  {
    // Liste des noms de catégorie à ajouter
    $names = array(
      'Basique',
      'Enfant',
      'Senior',
      'Reduit',
      'Famille'
    );

    foreach ($names as $name) {
      // On crée la catégorie
      $type = new Type();
      $type->setName($name);

      // On la persiste
      $manager->persist($type);
    }

    // On déclenche l'enregistrement de toutes les catégories
    $manager->flush();
  }
}