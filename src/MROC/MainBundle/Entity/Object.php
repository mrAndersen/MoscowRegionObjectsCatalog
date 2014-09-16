<?php

namespace MROC\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Object
 *
 * @ORM\Table(name="object")
 * @ORM\Entity(repositoryClass="MROC\MainBundle\Entity\ObjectRepository")
 */
class Object
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=800, nullable=true)
     */
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity="ObjectType")
     * @ORM\JoinColumn(name="object_type", referencedColumnName="id", nullable=true)
     */
    private $object_type;

    /**
     * @ORM\ManyToOne(targetEntity="SaleType")
     * @ORM\JoinColumn(name="sale_type", referencedColumnName="id", nullable=true)
     */
    private $sale_type;

    /**
     * @var string
     *
     * @ORM\Column(name="owner", type="string", length=800)
     */
    private $owner;

    /**
     * @param mixed $object_type
     */
    public function setObjectType($object_type)
    {
        $this->object_type = $object_type;
    }

    /**
     * @return mixed
     */
    public function getObjectType()
    {
        return $this->object_type;
    }

    /**
     * @param string $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $sale_type
     */
    public function setSaleType($sale_type)
    {
        $this->sale_type = $sale_type;
    }

    /**
     * @return mixed
     */
    public function getSaleType()
    {
        return $this->sale_type;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Object
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }
}