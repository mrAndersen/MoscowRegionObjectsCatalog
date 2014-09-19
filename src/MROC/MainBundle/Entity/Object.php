<?php

namespace MROC\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

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
     * @var float
     *
     * @ORM\Column(name="rating", type="float", nullable=true)
     */
    private $rating;

    /**
     * @var integer
     *
     * @ORM\Column(name="votes", type="integer", nullable=true)
     */
    private $votes;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", nullable=true)
     */
    private $image;
    /**
     *
     * @var string
     *
     * @ORM\Column(name="image_t", type="string", nullable=true)
     */
    private $image_t;

    /**
     * @var boolean
     *
     * @ORM\Column(name="override", type="boolean", nullable=true)
     */
    private $override;

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
     * @ORM\Column(name="owner", type="string", length=800, nullable=true)
     */
    private $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="coordinates", type="string", length=100, nullable=true)
     */
    private $coordinates;

    private $path = null;

    /**
     * @param string $coordinates
     */
    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
    }

    /**
     * @return string
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @param string $image_t
     */
    public function setImageT($image_t)
    {
        $this->image_t = $image_t;
    }

    /**
     * @return string
     */
    public function getImageT()
    {
        return $this->image_t;
    }

    /**
     * @param int $votes
     */
    public function setVotes($votes)
    {
        $this->votes = $votes;
    }

    /**
     * @return int
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param boolean $override
     */
    public function setOverride($override)
    {
        $this->override = $override;
    }

    /**
     * @return boolean
     */
    public function getOverride()
    {
        return $this->override;
    }

    /**
     * @param float $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

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

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir();
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir();
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'objects';
    }

    public function upload()
    {
        if($this->getImage() !== null){
            /** @var Image $image */
            $image = ImageManagerStatic::make($this->image->getRealPath());
            $name = md5($this->getOwner().mt_rand(0,999999999));
            $ext = $this->image->guessExtension();

            $image->save($this->getUploadRootDir().'/'.$name.'.'.$ext);
            $this->image = '/'.$this->getUploadDir().'/'.$name.'.'.$ext;

            $image->resize(null, 100, function ($constraint) {
                $constraint->aspectRatio();
            });

            $image->save($this->getUploadRootDir().'/'.$name.'_t.'.$ext);
            $this->image_t = '/'.$this->getUploadDir().'/'.$name.'_t.'.$ext;

            return true;
        }else{
            return false;
        }
    }

















}
