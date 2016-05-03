<?php

namespace JM\BilleterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BilletDate
 *
 * @ORM\Table(name="billet_date")
 * @ORM\Entity(repositoryClass="JM\BilleterieBundle\Repository\BilletDateRepository")
 */
class BilletDate
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", unique=true)
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="placePrise", type="integer")
     */
    private $placePrise;


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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return BilletDate
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set placePrise
     *
     * @param integer $placePrise
     *
     * @return BilletDate
     */
    public function setPlacePrise($placePrise)
    {
        $this->placePrise = $placePrise;

        return $this;
    }

    /**
     * Get placePrise
     *
     * @return int
     */
    public function getPlacePrise()
    {
        return $this->placePrise;
    }

    /**
     * Get placePrise
     *
     * @return int
     */
    public function addPlacePrise()
    {
        $this->placePrise += 1;
        
        return $this;
    }
}
