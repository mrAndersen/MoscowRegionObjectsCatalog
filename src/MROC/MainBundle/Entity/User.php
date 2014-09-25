<?php
// src/Acme/UserBundle/Entity/User.php

namespace MROC\MainBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="MROC\MainBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="municipal_id", type="integer", nullable=true)
     */
    private $municipal_id;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getMunicipalId()
    {
        return $this->municipal_id;
    }

    /**
     * @param int $municipal_id
     */
    public function setMunicipalId($municipal_id)
    {
        $this->municipal_id = $municipal_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}