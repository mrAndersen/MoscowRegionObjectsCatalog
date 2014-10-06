<?php

namespace MROC\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ObjectRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ObjectRepository extends EntityRepository
{

    public function getIdAddressList($land_missing_color,$default_color)
    {
        $em = $this->getEntityManager();
        $q = $em->createQueryBuilder()->select('n.coordinates','n.id','t.color','t.id as ot','IDENTITY(n.sale_type) as st','n.registered_land')
            ->from('MROCMainBundle:Object','n');
        $q->where($q->expr()->isNotNull('n.coordinates'))
            ->join('MROCMainBundle:ObjectType','t','WITH','n.object_type = t.id');

        $result = $q->getQuery()->getResult(); $data = array();

        foreach($result as $k=>$v){
            $temp = array();

            $temp['coordinates'] = explode(' ',$v['coordinates']);
            $temp['coordinates'] = array_reverse($temp['coordinates']);
            $temp['ot'] = $v['ot'];
            $temp['st'] = $v['st'];
            $temp['id'] = $v['id'];

            if($v['registered_land']){
                if($v['color']){
                    $temp['color'] = $v['color'];
                }else{
                    $temp['color'] = $default_color;
                }
            }else{
                $temp['color'] = $land_missing_color;
            }

            $data[] = $temp;
        }
        return $data;
    }

    public function getTop($n = 5)
    {
        $em = $this->getEntityManager();
        $q = $em->createQueryBuilder()->select('n')
            ->from('MROCMainBundle:Object','n')
            ->orderBy('n.rating','desc')
            ->setMaxResults($n)
            ->getQuery();

        $result = $q->getResult();
        return $result;
    }

    /**
     * @return mixed
     */
    public function getElementsCount()
    {
        $em = $this->getEntityManager();
        $query = $em->getConnection()->prepare('select count(*) as count from '.$this->getClassMetadata()->getTableName());
        $query->execute();
        $st = $query->fetch();
        return $st['count'];
    }

    /**
     * @param $node Object
     * @return array
     */
    public function getRank($node)
    {
        $em = $this->getEntityManager();
        $t_name = $em->getClassMetadata($this->getClassName())->getTableName();

        $q = $em->getConnection()->prepare('select z.rank from (select t.id, t.rating, @rownum := @rownum + 1 as rank from '.$t_name.' t, (select @rownum :=0) r order by rating desc) as z where id = :id');
        $q->bindValue(':id',$node->getId());
        $q->execute();

        $result = $q->fetch();
        return $result['rank'];
    }


}
