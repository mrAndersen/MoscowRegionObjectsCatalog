<?php

namespace MROC\AdminBundle\Helpers;


use Intervention\Image\ImageManagerStatic;

class YaMap
{
    public function getLatLon($geocode)
    {
        $url = 'http://geocode-maps.yandex.ru/1.x/?format=json&geocode=';
        $url = $url.$geocode;

        try{
            $json = file_get_contents($url);
            $array = json_decode($json,true);

            $coordinates = $array['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
        }catch (\Exception $e){
            $coordinates = null;
        }

        return $coordinates;
    }

    function getLatLonFromImage($path)
    {
        $exif = exif_read_data($path);

        if($exif['GPSLongitude'] && $exif['GPSLatitude']){
            $lon = number_format($this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']),6);
            $lat = number_format($this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']),6);

            return $lon.' '.$lat;
        }else{
            return null;
        }
    }


    function getGps($exifCoord, $hemi) {

        $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    function gps2Num($coordPart) {

        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }
}
