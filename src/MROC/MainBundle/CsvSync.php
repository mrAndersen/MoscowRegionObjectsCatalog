<?php

namespace MROC\MainBundle;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Keboola\Csv\CsvFile;

class CsvSync
{
    function __construct(Kernel $kernel, ContainerInterface $container)
    {
        $this->kernel = $kernel;
        $this->container = $container;
        $this->web = $this->kernel->getRootDir().'/../web';


    }

    public function syncFromCsv()
    {
        $csv = new CsvFile($this->web.'/test.csv',';');

        foreach($csv as $k=>$v){
            $result[] = $v;
        }
    }
}
