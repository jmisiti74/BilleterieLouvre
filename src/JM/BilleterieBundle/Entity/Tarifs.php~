<?php

namespace JM\BilleterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tarifs
 *
 * @ORM\Table(name="tarifs")
 * @ORM\Entity(repositoryClass="JM\BilleterieBundle\Repository\TarifsRepository")
 */
class Tarifs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="prix", type="integer")
     */
    private $prix;

    /**
     * @var string
     *
     * @ORM\Column(name="circonstance", type="string", length=255)
     */
    private $circonstance;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set prix
     *
     * @param integer $prix
     *
     * @return Tarifs
     */
    public function setPrix($prix)
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * Get prix
     *
     * @return int
     */
    public function getPrix()
    {
        return $this->prix;
    }

    /**
     * Set circonstance
     *
     * @param string $circonstance
     *
     * @return Tarifs
     */
    public function setCirconstance($circonstance)
    {
        $this->circonstance = $circonstance;

        return $this;
    }

    /**
     * Get circonstance
     *
     * @return string
     */
    public function getCirconstance()
    {
        return $this->circonstance;
    }
}
