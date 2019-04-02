<?php
namespace geosamples\backend\controller;
use \geosamples\backend\controller\UserController as User;
use \geosamples\backend\controller\FileController as File;
use \geosamples\backend\controller\MailerController as Mailer;
use MongoDB;
use MongoDuplicateKeyException;

class RequestController
{

    function ConfigFile()
    {
        $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        return $config;
    }

    function Request_data_awaiting($id){
        $file    = new File();
        $config  = $file->ConfigFile();
        $bdd     = strtolower($config['authSource']);
        $url     = 'http://' . $config['ESHOST'] . '/' . $bdd . '/'.$config['COLLECTION_NAME'].'_sandbox/'.$id;
        $curlopt = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PORT           => $config['ESPORT'],
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
        );
        $response = self::Curlrequest($url, $curlopt);
        $response = json_decode($response, true);
        return $response;
    }


    function Request_all_data_awaiting(){
        $file    = new File();
        $config  = $file->ConfigFile();
        $bdd     = strtolower($config['authSource']);
        $url     = 'http://' . $config['ESHOST'] . '/' . $bdd . '/_search?type='.$config['COLLECTION_NAME'].'_sandbox&size=10000';
        $curlopt = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PORT           => $config['ESPORT'],
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
        );
        $response = self::Curlrequest($url, $curlopt);
        $response = json_decode($response, true);
       // var_dump($response['hits']['hits']);
        $array=array();
        foreach ($response['hits']['hits'] as $key => $value) {
            if ($value['_source']['INTRO']['ACCESS_RIGHT']=='Awaiting') {

             $array[]=$value['_id'];
         }
     }
     return $array;
 }


    /**
     *  Methode d'execution des Requetes CURL
     *
     *  @param $url :
     *          Url a appeler
     *  @param $curlopt :
     *            Option a ajouter
     *     @return $rawData:
     *            Données Json recu
     */
    function Curlrequest($url, $curlopt)
    {
        $ch = curl_init();
        $curlopt = array(
            CURLOPT_URL => $url
        ) + $curlopt;
        curl_setopt_array($ch, $curlopt);
        $rawData = curl_exec($ch);
        curl_close($ch);
        return $rawData;
    }

    /**
     *  Methode de requetes vers elasticsearch
     *
     *
     */
    function Request_all_poi()
    {

        $config = self::ConfigFile();
        $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . "/_search?type=" . $config['COLLECTION_NAME'] ."&size=10000";
        $postcontent = '{ "_source": { 
            "includes": [ "INTRO.SAMPLING_DATE","INTRO.TITLE","INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY","INTRO.DATA_DESCRIPTION","INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME","INTRO.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE","INTRO.SUPPLEMENTARY_FIELDS.NAME_REFERENT","INTRO.SUPPLEMENTARY_FIELDS.FIRST_NAME_REFERENT",
            "INTRO.SAMPLING_DATE","INTRO.SAMPLING_POINT","INTRO.MEASUREMENT","DATA.FILES" ] 
        }}';
        $curlopt = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_PORT => $config['ESPORT'],
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => "Content-type: application/json",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postcontent
        );
        $response = self::Curlrequest($url, $curlopt);
        $response = json_decode($response, true);
        $response = $response['hits']['hits'];
        $responses = array();
        $return = array();
        foreach ($response as $key => $value)
        {

            $current = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

           

            if (!$return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]])
            {
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_DATE'] = $value['_source']['INTRO']['SAMPLING_DATE'];
               
                if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $value['_source']['INTRO']['SUPPLEMENTARY_FIELDS'];
                }else{
                    $supplementary['CORE_DETAILS']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'];
                    $supplementary['BLOCK']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['BLOCK'];
                    $supplementary['SAMPLE_NAME']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'];
                    //$supplementary['DESCRIPTION']=$value['_source']['INTRO']['DATA_DESCRIPTION'];
                    $supplementary['REFERENT']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['REFERENT'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $supplementary;
                }
         

                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['TITLE'] = $value['_source']['INTRO']['TITLE'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['DATA_DESCRIPTION'] = $value['_source']['INTRO']['DATA_DESCRIPTION'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['INSTITUTION'] = $value['_source']['INTRO']['INSTITUTION'];

                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_POINT'] = $value['_source']['INTRO']['SAMPLING_POINT'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LAT'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LONG'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                foreach ($value['_source']['DATA']['FILES'] as $key => $file)
                {
                     if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                        if (exif_imagetype($file['ORIGINAL_DATA_URL']))
                        {
                            $return[$current][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['PICTURES'][$key] = $file;
                        }
                    }
                }
            }
            //if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['MEASUREMENT'][] = $value['_source']['INTRO']['MEASUREMENT'];
            //}

        }
        //var_dump($return);
        //unset($return[$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']['SUPPLEMENTARY_FIELDS']);
        $responses = $return;
        $responses = json_encode($responses);
        return $responses;
    }

    /**
     *  Methode de requetes vers elasticsearch
     *
     *
     */
    function Request_data_with_sort($sort)
    {
        $lithology = '';
        $mesure = '';
        $sort = json_decode($sort, true);
       /* if ($sort['lithology'])
        {
           $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
       }*/
        if ($sort['lithology'])  // HOST LITOHLOGY SCANDIUM
        {
            $host_litho = 'INTRO.SUPPLEMENTARY_FIELDS.HOST_LITHOLOGY_OR_PROTOLITH:"' . urlencode($sort['lithology']) . '"%20AND%20';
        }
       if ($sort['lithology3'])
        {
           $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY_3:"' . urlencode($sort['lithology3']) . '"%20AND%20';
       }
       if ($sort['host_litho'])
    {
        $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.HOST_LITHOLOGY_OR_PROTOLITH:"' . urlencode($sort['host_litho']) . '"%20AND%20';
    }
       if ($sort['mindate'] and $sort['maxdate'])
       {
        $date = 'INTRO.SAMPLING_DATE:[' . $sort['mindate'] . '%20TO%20' . $sort['maxdate'] . ']%20AND%20';
    }
    if ($sort['mesure'])
    {
        $mesure = 'INTRO.MEASUREMENT.ABBREVIATION:"' . urlencode($sort['mesure']) . '"%20AND%20';
    }
    if ($sort['lat'] and $sort['lng'])
    {
        $geo = 'INTRO.SAMPLING_POINT.LONGITUDE:[' . $sort['lat']['lat1'] . '%20TO%20' . $sort['lat']['lat2'] . ']%20AND%20INTRO.SAMPLING_POINT.LATITUDE:[' . $sort['lat']['lat1'] . '%20TO%20' . $sort['lat']['lat1'] . ']';
    }

    $config = self::ConfigFile();
    $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . "/_search?q=" . $lithology . $mesure . $date . $geo . "type=" . $config['COLLECTION_NAME'] ."&size=10000";

    $postcontent = '{ "_source": { 
        "includes": [ "DATA","INTRO.MEASUREMENT.ABBREVIATION" ] 
    }}';
    $curlopt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_PORT => $config['ESPORT'],
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => "Content-type: application/json",
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postcontent
    );
    $response = self::Curlrequest($url, $curlopt);
    $response = json_decode($response, true);
    $response = $response['hits']['hits'];
    $responses = array();
    $finalcsv = array();
    $finalcsvuniq = array();
    echo '<link rel="stylesheet" type="text/css" href="/css/semantic/dist/semantic.min.css">';
    echo '    <link rel="stylesheet" type="text/css" href="/css/style.css">  

    ';

    echo "<div class='' ui grid container'  style='overflow-x:auto'><table style='width:700px; height:500px;' class='ui compact unstackable table'></div>";
    foreach ($response as $key => $value)
    {
         if ($value['_source']['INTRO']['ACCESS_RIGHT']!='Awaiting' && !is_null($value['_source']['DATA']['FILES'])) {
            if (strtoupper($sort['mesure']) == strtoupper($value['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION']))
            {

                $file = $value['_source']['DATA']['FILES'][0]['ORIGINAL_DATA_URL'];
                $folder = explode('_', strtoupper($value['_source']['DATA']['FILES'][0]['DATA_URL']));
                $name = $value['_source']['DATA']['FILES'][0]['DATA_URL'];
                $file_parts = pathinfo($file);
                if ($file_parts['extension'] == "xlsx")
                {
                    $CSV_FOLDER = $config["CSV_FOLDER"];
                    $file = $CSV_FOLDER . $folder[0] . '_' . $folder[1] . "/" . $name;
                }
                $csv = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $finalcsv[] = $csv;
            }
        }
    }
    foreach ($finalcsv as $key => $value)
    {
        foreach ($value as $key => $value)
        {
            $finalcsvuniq[] = $value;
        }
    }
    $finalcsvuniq = array_unique($finalcsvuniq);
    foreach ($finalcsvuniq as $key => $value)
    {
        $value = str_getcsv($value);
        echo "<tr>";
        foreach ($value as $cell)
        {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>\n";

    }
    echo "\n</table></body></html>";

}

function Download_data_with_sort($sort)
{
    $lithology = '';
    $mesure = '';
    $sort = json_decode($sort, true);
   /* if ($sort['lithology'])
    {
       $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
   }*/
    if ($sort['lithology'])  // HOST LITOHLOGY SCANDIUM
    {
            $host_litho = 'INTRO.SUPPLEMENTARY_FIELDS.HOST_LITHOLOGY_OR_PROTOLITH:"' . urlencode($sort['lithology']) . '"%20AND%20';
    }
   if ($sort['lithology3'])
    {
        $lithology3 = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY_3:"' . urlencode($sort['lithology3']) . '"%20AND%20';
    }
    if ($sort['host_litho'])
    {
        $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.HOST_LITHOLOGY_OR_PROTOLITH:"' . urlencode($sort['host_litho']) . '"%20AND%20';
    }
   if ($sort['mindate'] and $sort['maxdate'])
   {
    $date = 'INTRO.SAMPLING_DATE:[' . $sort['mindate'] . '%20TO%20' . $sort['maxdate'] . ']%20AND%20';
}
if ($sort['mesure'])
{
    $mesure = 'INTRO.MEASUREMENT.ABBREVIATION:"' . urlencode($sort['mesure']) . '"%20AND%20';
}
$config = self::ConfigFile();
$url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . "/_search?q=" . $lithology . $mesure . $date . "type=" . $config['COLLECTION_NAME'] ."&size=10000";

$postcontent = '{ "_source": { 
    "includes": [ "DATA","INTRO.MEASUREMENT.ABBREVIATION" ] 
}}';
$curlopt = array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_PORT => $config['ESPORT'],
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_HTTPHEADER     => "Content-type: application/json",
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $postcontent
);
$response = self::Curlrequest($url, $curlopt);
$response = json_decode($response, true);
$response = $response['hits']['hits'];
$responses = array();
$finalcsv = array();
$finalcsvuniq = array();

foreach ($response as $key => $value)
{
     if ($value['_source']['INTRO']['ACCESS_RIGHT']!='Awaiting' && !is_null($value['_source']['DATA'])) {
        if (strtoupper($sort['mesure']) == strtoupper($value['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION']) or $sort['mesure'] == null)
        {

            $file = $value['_source']['DATA']['FILES'][0]['ORIGINAL_DATA_URL'];
            $folder = explode('_', strtoupper($value['_source']['DATA']['FILES'][0]['DATA_URL']));
            $name = $value['_source']['DATA']['FILES'][0]['DATA_URL'];
            $file_parts = pathinfo($file);
            if ($file_parts['extension'] == "xlsx")
            {
                $CSV_FOLDER = $config["CSV_FOLDER"];
                $file = $CSV_FOLDER . $folder[0] . '_' . $folder[1] . "/" . $name;
            }
            $csv = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $finalcsv[] = $csv;
        }
    }
}
foreach ($finalcsv as $key => $value)
{
    foreach ($value as $key => $value)
    {
        $finalcsvuniq[] = $value;
    }
}
$finalcsvuniq = array_unique($finalcsvuniq);
$generatedfile = "";
foreach ($finalcsvuniq as $key => $value)
{
    $generatedfile .= $value . "\n";
}
echo $generatedfile;

}

    /**
     *  Methode de requetes vers elasticsearch
     *
     *
     */
    function Request_poi_with_sort($sort)
    {
        $lithology = '';
        $lithology3 = '';
        $mesure = '';
        $sort = json_decode($sort, true);
       /* if ($sort['lithology'])
        {
            $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
        }*/
        if ($sort['lithology3'])
        {
            $lithology3 = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY_3:"' . urlencode($sort['lithology3']) . '"%20AND%20';
        }
        if ($sort['lithology'])  // HOST LITOHLOGY SCANDIUM
        {
            $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.HOST_LITHOLOGY_OR_PROTOLITH:"' . urlencode($sort['lithology']) . '"%20AND%20';
        }
        if ($sort['mindate'] and $sort['maxdate'])
        {
            $date = 'INTRO.SAMPLING_DATE:[' . $sort['mindate'] . '%20TO%20' . $sort['maxdate'] . ']%20AND%20';
        }
        if ($sort['mesure'])
        {
            $mesure = 'INTRO.MEASUREMENT.ABBREVIATION:"' . urlencode($sort['mesure']) . '"%20AND%20';
        }
        /*if ($sort['lat'] and $sort['lon']) {
        	$geo='INTRO.SAMPLING_POINT.LONGITUDE:['.abs($sort['lat']['lat1']).'%20TO%20'.abs($sort['lat']['lat2']).']%20AND%20INTRO.SAMPLING_POINT.LATITUDE:['.abs($sort['lon']['lon1']).'%20TO%20'.abs($sort['lon']['lon2']).']';
        }*/

        $config = self::ConfigFile();
        $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . "/_search?q=" . $lithology .$lithology3 . $mesure . $date . "type=" . $config['COLLECTION_NAME'] ."&size=10000";

        $postcontent = '{ "_source": { 
            "includes": [ "INTRO.SAMPLING_DATE","INTRO.TITLE","INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY","INTRO.DATA_DESCRIPTION","INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME","INTRO.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE","INTRO.SUPPLEMENTARY_FIELDS.NAME_REFERENT","INTRO.SUPPLEMENTARY_FIELDS.FIRST_NAME_REFERENT",
            "INTRO.SAMPLING_DATE","INTRO.SAMPLING_POINT","INTRO.MEASUREMENT","DATA.FILES" ] 
        }}';
        $curlopt = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_PORT => $config['ESPORT'],
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => "Content-type: application/json",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postcontent
        );
        $response = self::Curlrequest($url, $curlopt);
        $response = json_decode($response, true);
        $response = $response['hits']['hits'];
        $responses = array();
        $return = array();
        foreach ($response as $key => $value)
        {
            if ($value['_source']['INTRO']['ACCESS_RIGHT']!='Awaiting') {
            $longitude = (float)$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];
            $latitude = (float)$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
            if ((strtoupper($sort['mesure']) == strtoupper($value['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION']) or $sort['mesure'] == null) and (($latitude >= $sort['lat']['lat1']) && $latitude < $sort['lat']['lat2']) && ($longitude >= $sort['lon']['lon2'] && $longitude < $sort['lon']['lon1']) or $sort['lat'] == null or $sort['lon'] == null)
            {
                if (!$return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']])
                {
                    $current = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_DATE'] = $value['_source']['INTRO']['SAMPLING_DATE'];
           if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $value['_source']['INTRO']['SUPPLEMENTARY_FIELDS'];
                }else{
                    $supplementary['CORE_DETAILS']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'];
                    $supplementary['BLOCK']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['BLOCK'];
                    $supplementary['SAMPLE_NAME']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'];
                    //$supplementary['DESCRIPTION']=$value['_source']['INTRO']['DATA_DESCRIPTION'];
                    $supplementary['REFERENT']=$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['REFERENT'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $supplementary;
                }
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['TITLE'] = $value['_source']['INTRO']['TITLE'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['DATA_DESCRIPTION'] = $value['_source']['INTRO']['DATA_DESCRIPTION'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['INSTITUTION'] = $value['_source']['INTRO']['INSTITUTION'];

                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_POINT'] = $value['_source']['INTRO']['SAMPLING_POINT'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LAT'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LONG'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                    foreach ($value['_source']['DATA']['FILES'] as $key => $file)
                    {
                        if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                            if (exif_imagetype($file['ORIGINAL_DATA_URL']))
                            {
                                $return[$current][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['PICTURES'][$key] = $file;
                            }
                        }
                    }

                }
                //if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                 $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['MEASUREMENT'][] = $value['_source']['INTRO']['MEASUREMENT'];
            // }
             }
             $responses = $return;
         }
     }
     $responses = json_encode($responses);
     return $responses;
 }


 function Request_poi_data_awaiting($id, $name)
{
    $explode = explode('_', $id, 2);
    $config = self::ConfigFile();
    $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . '/_search?q=(INTRO.MEASUREMENT.ABBREVIATION:"' . $explode[1] . '"%20AND%20INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME:"' . $explode[0] . '")&type=' . $config['COLLECTION_NAME'].'_sandbox' ;
    $curlopt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_PORT => $config['ESPORT'],
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => "Content-type: application/json",
        CURLOPT_CUSTOMREQUEST => "GET"
    );
    $response = self::Curlrequest($url, $curlopt);
    $response = json_decode($response, true);

    foreach ($response['hits']['hits'][0]['_source']['DATA']['FILES'] as $key => $value)
    {

        if ($value['DATA_URL'] == $name)
        {
            $img = $value['ORIGINAL_DATA_URL'];
        }

    }
    return $img;
}



 function Request_poi_data($id)
 {
    $explode = explode('_', $id, 3);
    $id=$explode[0].'_'.$explode[1];
    $config = self::ConfigFile();
    $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . '/_search?q=(INTRO.MEASUREMENT.ABBREVIATION:"' . $explode[1] . '"%20AND%20INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME:"' . $explode[0] . '")&type=' . $config['COLLECTION_NAME'] ;
    $curlopt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_PORT => $config['ESPORT'],
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => "Content-type: application/json",
        CURLOPT_CUSTOMREQUEST => "GET"
    );
    $response = self::Curlrequest($url, $curlopt);
    $response = json_decode($response, true);
    //if (($_SESSION['mail'] && in_array($response['hits']['hits'][0]['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
        $identifier = $response['hits']['hits'][0]['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'] . '_' . $response['hits']['hits'][0]['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION'];

        if ($identifier == $id)
        {
            
            $response = json_encode($response['hits']['hits'][0]['_source']['DATA']);

            return $response;
        }
    }
//}

function Request_poi_raw_data($id)
{
    $config = self::ConfigFile();
    $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . '/_search?q=INTRO.MEASUREMENT.ABBREVIATION:"' . $id .'"&type=' . $config['COLLECTION_NAME'] ;
    $curlopt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_PORT => $config['ESPORT'],
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => "Content-type: application/json",
        CURLOPT_CUSTOMREQUEST => "GET"
    );
    $response = self::Curlrequest($url, $curlopt);
    $response = json_decode($response, true);

    if (count($response['hits']['hits'])==1) {
        $response = json_encode($response['hits']['hits'][0]['_source']['DATA']);
        return $response;
    }

}


function Request_poi_img($id, $picturename)
{
    $explode = explode('_', $id, 2);
    $config = self::ConfigFile();
    $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . '/_search?q=(INTRO.MEASUREMENT.ABBREVIATION:"' . $explode[1] . '"%20AND%20INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME:"' . $explode[0] . '")&type=' . $config['COLLECTION_NAME'] ;
    $curlopt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_PORT => $config['ESPORT'],
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => "Content-type: application/json",
        CURLOPT_CUSTOMREQUEST => "GET"
    );
    $response = self::Curlrequest($url, $curlopt);
    $response = json_decode($response, true);

    foreach ($response['hits']['hits'] as $key => $value)
    {
        foreach ($value['_source']['DATA']['FILES'] as $key => $value) {
        if ($value['DATA_URL'] == $picturename)
        {
            $img = $value['ORIGINAL_DATA_URL'];
        }
            
        }


    }
   return $img;
}

    /**
     * Download a file
     * @return true if ok else false
     */
    function download($filepath)
    {
        if (file_exists($filepath))
        {
            if(@!is_array(getimagesize($filepath))){
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=" . basename($filepath));
            $readfile = file_get_contents($filepath);
            print $readfile;
            }
            else{
                return false;
            }
        }
        if ($readfile == false)
        {
            return false;
        }
        else
        {
            return true;
        }
        exit;
    }

    function preview_img($path)
    {
        $mime = pathinfo($path);
        $mime = $mime['extension'];
        $mime = strtolower($mime);
        if ($mime == 'png')
        {
            $readfile = readfile($path);
            $mime = "image/png";
            header('Content-Type:  ' . $mime);
        }
        elseif ($mime == 'jpg')
        {
            $readfile = readfile($path);
            $mime = "image/jpg";
            header('Content-Type:  ' . $mime);
        }
        elseif ($mime == 'gif')
        {
            $readfile = readfile($path);
            $mime = "image/gif";
            header('Content-Type:  ' . $mime);
        }
        else
        {
            echo "<h1>Cannot preview file</h1> <p>Sorry, we are unfortunately not able to preview this file.<p>";
            $readfile = false;
            header('Content-Type:  text/html');
        }
        if ($readfile == false)
        {
            return false;
        }
        else
        {
            return $mime;
        }
    }

    /**
     * Preview a file
     * @param doi of dataset, filename,data of dataset
     * @return true if ok else false
     */
    function preview($file, $folder, $name)
    {
        $config = self::ConfigFile();

        $file_parts = pathinfo($file);
        if ($file_parts['extension'] == "xlsx")
        {
            $CSV_FOLDER = $config["CSV_FOLDER"];
            $file = $CSV_FOLDER . $folder . "/" . $name;
        }
        if (file_exists($file))
        {
           if(@!is_array(getimagesize($file))){
               

            $readfile = false;
            $file = fopen($file, "r");
            $firstTimeHeader = true;
            $firstTimeBody = true;
            echo '<link rel="stylesheet" type="text/css" href="/css/semantic/dist/semantic.min.css">';
            echo '    <link rel="stylesheet" type="text/css" href="/css/style.css">';
            echo "<div class='' ui grid container'  style='overflow-x:auto'><table style='width:700px; height:500px;' class='ui compact unstackable table'></div>";
            while (!feof($file))
            {
                $data = fgetcsv($file);

                if ($firstTimeHeader)
                {
                    echo "<thead>";
                }
                else
                {
                    if ($firstTimeBody)
                    {
                        echo "</thead>";
                        echo "<tbody>";
                        $firstTimeBody = false;
                    }
                }
                echo "<tr>";

                foreach ($data as $value)
                {
                    if ($firstTimeHeader)
                    {
                        echo "<th>" . $value . "</th>";
                    }
                    else
                    {
                        echo "<td>" . $value . "</td>";
                    }
                }

                echo "</tr>";
                if ($firstTimeHeader)
                {
                    $firstTimeHeader = false;
                }
            }
            echo "</table>";

            }
            else
        {
            echo "<h1>Cannot preview file</h1> <p>Sorry, we are unfortunately not able to preview this file.<p>";
            $readfile = false;
            header('Content-Type:  text/html');
        }
        }

        else
        {
            echo "<h1>Cannot preview file</h1> <p>Sorry, we are unfortunately not able to preview this file.<p>";
            $readfile = false;
            header('Content-Type:  text/html');
        }

        if ($readfile == false)
        {
            return false;
        }
        else
        {
            return $mime;
        }
        exit;

    }
     


    function Post_Processing($POST,$route)
    {

        $config = self::ConfigFile();
        $rawdata=array();
        $data=array();
        $pictures=array();
        $data_samples  = array();
        $_POST['sample_name']=strtoupper($_POST['sample_name']);

if ($_POST['original_sample_name']) {
                                    if ($_POST['sample_name'].'_'.strtoupper($_POST['measurements'][1])!=$_POST['original_sample_name']) {
                                        //$sample_name=$_POST['sample_name'];
                                        $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1];
                                       /* if (strpos($_POST['original_sample_name'], '_RAW') !== false) {
                                           $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW';
                                       }*/
                                   }
                                   else{
                                        //$sample_name=$_POST['sample_name'];
                                    $original_sample_name=$_POST['original_sample_name'];

                                }
                            }
        $UPLOAD_FOLDER    = $config["CSV_FOLDER"];
        if ($_FILES['data']['error'][0] != '0') {

        }
        else{
          for ($i = 0; $i < count($_FILES['data']['name']); $i++) {
                        // on parcourt les fichiers uploader
            $size = $_FILES["data"]["size"][$i];
            $repertoireDestination         = $UPLOAD_FOLDER;
            $nomDestination                = str_replace(' ', '_', $_FILES["data"]["name"][$i]);
            $data["FILES"][$i]["DATA_URL"] = $nomDestination;

            if (file_exists($repertoireDestination . $_FILES["data"]["name"][$i])) {
                $returnarray[] = "false";
                $returnarray[] = $array['dataform'];
                return $returnarray;
            } else {
                if (is_uploaded_file($_FILES["data"]["tmp_name"][$i])) {
                    if (is_dir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]) == false) {
                        mkdir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]);
                    }
                    if (!file_exists($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1])) {
                        mkdir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]);
                    }
                    if (copy($_FILES["data"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]. "/" . $nomDestination)) {
                        chmod($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]. "/" . $nomDestination, 0640);
                        $extension = new \SplFileInfo($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1] ."/" . $nomDestination);
                        $filetypes = $extension->getExtension();
                        $file=$repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1] ."/" . $nomDestination;
                        if ($filetypes == 'csv' or $filetypes == 'xlsx') {
                            if ($filetypes == 'csv') {

                             $type = \PHPExcel_IOFactory::identify($file);
                             $objReader = \PHPExcel_IOFactory::createReader($type);

                             $objPHPExcel = $objReader->load($file);
                             $sheet         = $objPHPExcel->getActiveSheet();
                             $highestColumn = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

                             $rowIterator   = $sheet->getRowIterator();
                             $units         = array();
                             $keys          = array();
                             $obj           = array();


                             $startFields = 2;

                             foreach ($rowIterator as $ligne => $row) {
                                $cellIterator = $row->getCellIterator();
                                $cellIterator->setIterateOnlyExistingCells(false);
                                foreach ($cellIterator as $cell) {
                                    $indice = \PHPExcel_Cell::columnIndexFromString($cell->getColumn());
                                    if ($ligne == 1) {
                                        $key = trim($cell->getValue());
                                        if (strpos($key, ".") !== false) {
                                            $msg = "\t Caractere '.' detecte dans la clef [$key] : suppression";
                                            echo PHP_EOL . $msg . PHP_EOL;
                                            $this->logger->warning($msg);
                                            $key = preg_replace('/./', '', $key);
                                        }
                                        $units[$key] = $sheet->getCellByColumnAndRow($indice - 1, $ligne + 1)->getValue();

                                        if ($indice == $highestColumn) {
                                            $keys = array_keys($units);
                                        }
                                    } else if ($ligne > $startFields) {
                                        $value = $cell->getValue();
                                        if (!empty($keys[$indice - 1])) {

                                            $obj[$keys[$indice - 1]] = $value;
                                        }

                                        if ($indice == $highestColumn) {

                                                               // $arrKey["SAMPLES"][] = $obj;
                                            $data_samples["SAMPLES"][]=$obj;
                                        }
                                    }
                                }

                            }

                        }
                        if ($filetypes == 'xlsx') {



                         $type = \PHPExcel_IOFactory::identify($file);
                         $objReader = \PHPExcel_IOFactory::createReader($type);
                         $objPHPExcel = $objReader->load($file);
                         $sheet         = $objPHPExcel->getActiveSheet();
                         $highestColumn = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());


                         $rowIterator   = $sheet->getRowIterator();
                         $units         = array();
                         $keys          = array();
                         $obj           = array();
                         $data_samples  = array();


                         $startFields = 2;
                         foreach ($rowIterator as $ligne => $row) {
                            $cellIterator = $row->getCellIterator();
                            $cellIterator->setIterateOnlyExistingCells(false);
                            foreach ($cellIterator as $cell) {
                                $indice = \PHPExcel_Cell::columnIndexFromString($cell->getColumn());
                                if ($ligne == 1) {
                                    $key = trim($cell->getValue());
                                    if (strpos($key, ".") !== false) {
                                        $msg = "\t Caractere '.' detecte dans la clef [$key] : suppression";
                                        echo PHP_EOL . $msg . PHP_EOL;
                                        $this->logger->warning($msg);
                                        $key = preg_replace('/./', '', $key);
                                    }
                                    $units[$key] = $sheet->getCellByColumnAndRow($indice - 1, $ligne + 1)->getValue();

                                    if ($indice == $highestColumn) {
                                        $keys = array_keys($units);
                                    }
                                } else if ($ligne > $startFields) {
                                    $value = $cell->getValue();
                                    if (!empty($keys[$indice - 1])) {

                                        $obj[$keys[$indice - 1]] = $value;
                                    }

                                    if ($indice == $highestColumn) {

                                                               // $arrKey["SAMPLES"][] = $obj;
                                        $data_samples["SAMPLES"][]=$obj;
                                    }
                                }
                            }

                        }





                    }
                    //var_dump($data_samples);






                }
                else{
                    $error='Bad extension';
                }
                $data["FILES"][$i]["FILETYPE"] = $filetypes;
                $data["FILES"][$i]["TYPE_DATA"] = 'Data';
                $data["FILES"][$i]["ORIGINAL_DATA_URL"] = $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1] ."/" . $nomDestination;
                                        //var_dump($data);
                                        //$collection                    = "Manual_Depot";
                                       // $collectionObject              = $this->db->selectCollection($config["authSource"], $collection);
            } else {
                $returnarray[] = "false";
                $returnarray[] = $array['dataform'];
                return $returnarray;
            }
        }
    }

}
}
if ($_FILES['pictures']['error'][0] != '0') {
}
else{
   for ($i = 0; $i < count($_FILES['pictures']['name']); $i++) {
                        // on parcourt les fichiers uploader
    $size = $_FILES["pictures"]["size"][$i];
    $repertoireDestination         = $UPLOAD_FOLDER;
    $nomDestination                = str_replace(' ', '_', $_FILES["pictures"]["name"][$i]);
    $pictures["FILES"][$i]["DATA_URL"] = $nomDestination;

    if (file_exists($repertoireDestination . $_FILES["pictures"]["name"][$i])) {
        $returnarray[] = "false";
        $returnarray[] = $array['dataform'];
        return $returnarray;
    } else {
        if (is_uploaded_file($_FILES["pictures"]["tmp_name"][$i])) {
            if (is_dir($repertoireDestination  ."/". $_POST['sample_name']."_META") == false) {
                mkdir($repertoireDestination  ."/". $_POST['sample_name']."_META");
            }
            if (!file_exists($repertoireDestination  ."/". $_POST['sample_name']."_META")) {
                mkdir($repertoireDestination  ."/". $_POST['sample_name']."_META");
            }
            if (copy($_FILES["pictures"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'] . "_META/" . $nomDestination)) {
                chmod($repertoireDestination ."/". $_POST['sample_name'] . "_META/" . $nomDestination, 0640);
                $extension = new \SplFileInfo($repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $nomDestination);
                $filetypes = $extension->getExtension();
                if (strlen($filetypes) == 0 or strlen($filetypes) > 4) {
                    $filetypes = 'unknow';
                }
                $pictures["FILES"][$i]["FILETYPE"] = $filetypes;
                $pictures["FILES"][$i]["TYPE_DATA"] = 'Pictures';
                $pictures["FILES"][$i]["ORIGINAL_DATA_URL"] = $repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $nomDestination;

                                        //$collection                    = "Manual_Depot";
                                       // $collectionObject              = $this->db->selectCollection($config["authSource"], $collection);
            } else {
                $returnarray[] = "false";
                $returnarray[] = $array['dataform'];
                return $returnarray;
            }
        }
    }
}

}
if ($_FILES['rawdata']['error'][0] != '0') {

}
else{
  for ($i = 0; $i < count($_FILES['rawdata']['name']); $i++) {
                        // on parcourt les fichiers uploader
    $size = $_FILES["rawdata"]["size"][$i];
    $repertoireDestination         = $UPLOAD_FOLDER;
    $nomDestination                = str_replace(' ', '_', $_FILES["rawdata"]["name"][$i]);
    $rawdata["FILES"][$i]["DATA_URL"] = $nomDestination;

    if (file_exists($repertoireDestination . $_FILES["rawdata"]["name"][$i])) {
        $returnarray[] = "false";
        $returnarray[] = $array['dataform'];
        return $returnarray;
    } else {
        if (is_uploaded_file($_FILES["rawdata"]["tmp_name"][$i])) {
            if (is_dir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW') == false) {
                mkdir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW');
            }
            if (!file_exists($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW')) {
                mkdir($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW');
            }
            if (copy($_FILES["rawdata"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW'. "/" . $nomDestination)) {
                chmod($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW'. "/" . $nomDestination, 0640);

                $extension = new \SplFileInfo($repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW' ."/" . $nomDestination);
                $filetypes = $extension->getExtension();
                $file=$repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW' ."/" . $nomDestination;
                                       /* if ($filetypes == 'csv' or $filetypes == 'xlsx') {
                                        var_dump($file);
                                         
                                            $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
                                            $excelObj = $excelReader->load($file);
                                            $worksheet = $excelObj->getSheet(0);
                                            $lastRow = $worksheet->getHighestRow();
                                            var_dump($excelReader);

                                    exit();

                                        }
                                        else{
                                            $error='Bad extension';
                                        }*/
                                        $rawdata["FILES"][$i]["FILETYPE"] = $filetypes;
                                        $rawdata["FILES"][$i]["TYPE_DATA"] = 'Rawdata';
                                        $rawdata["FILES"][$i]["ORIGINAL_DATA_URL"] = $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW'."/" . $nomDestination;

                                        //$collection                    = "Manual_Depot";
                                       // $collectionObject              = $this->db->selectCollection($config["authSource"], $collection);
                                    } else {
                                        $returnarray[] = "false";
                                        $returnarray[] = $array['dataform'];
                                        return $returnarray;
                                    }
                                }
                            }

                        }
                    }

                    $SUPPLEMENTARY_FIELDS=array('HOST_LITHOLOGY_OR_PROTOLITH','LITHOLOGY1','LITHOLOGY2','LITHOLOGY3','ORETYPE1','ORETYPE2','ORETYPE3','TEXTURE1','TEXTURE2','TEXTURE3','SUBSTANCE','STORAGE_DETAILS','HOST_AGE','MAIN_EVENT_AGE','OTHER_EVENT_AGE','ALTERATION_DEGREE','SAMPLE_NAME','BLOCK','PULP','SAFETY_CONSTRAINTS','SAMPLE_LOCATION_FACILITY');
                    $error=null;

                    $required = array(
                        'title',
                        'language',
                        'sample_name',
                        'keywords',
                        'institution',
                        'scientific_fields',
                        'sampling_points',

                    );

                    foreach ($required as $field) {
            //Verif des champs à traiter
                        if (empty($_POST[$field]) or empty($_POST[$field][0]) or empty($_POST[$field][0][0])) {
                            $fields[] = $field;
                        }
                    }
                    if (count($fields) != 0) {
        //Affichage des champs manquants
                        $txt = null;
                        foreach ($fields as $key => $value) {
                            $txt .= "  " . $value;
                        }
                        $error = "Warning there are empty fields: " . $txt;
                    }








                    foreach ($POST as $key => $value) {

                       switch ($key) {

                        case "description":
                            $arrKey[strtoupper('DATA_DESCRIPTION')] = htmlspecialchars($value, ENT_QUOTES);
                            $arrcsv[strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);
                        
                        break;
                        case "keywords":
                        foreach ($value as $key2 => $value2) {
                            $arrKey[strtoupper($key)][]['NAME'] = htmlspecialchars($value2, ENT_QUOTES);
                            $arrcsv[strtoupper($key)][] = htmlspecialchars($value2, ENT_QUOTES);
                        }
                        break;
                        case "core":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][]['CORE'] = $value;
                        $csv['CORE_label'][]= '';
                        $csv['CORE_label'][]= '';
                        $csv['CORE'][] = 'CORE';
                        $csv['CORE'][] = htmlspecialchars($value, ENT_QUOTES);

                        break;
                        case "core_depth":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['DEPTH'] = htmlspecialchars($value, ENT_QUOTES);
                        $csv['CORE_label'][]= 'DEPTH';
                        $csv['CORE'][]= htmlspecialchars($value, ENT_QUOTES);


                        break;
                        case "core_azimut":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['AZIMUT'] = htmlspecialchars($value, ENT_QUOTES);
                        $csv['CORE_label'][]= 'AZIMUT';
                        $csv['CORE'][]= htmlspecialchars($value, ENT_QUOTES);


                        break;
                        case "core_dip":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['DIP'] = htmlspecialchars($value, ENT_QUOTES);
                        $csv['CORE_label'][]= 'DIP';
                        $csv['CORE'][]= htmlspecialchars($value, ENT_QUOTES);


                        break;
                        case "sampling_date":
                        $arrKey[strtoupper($key)][] = htmlspecialchars($value, ENT_QUOTES);
                        $arrcsv[strtoupper($key)][] = htmlspecialchars($value, ENT_QUOTES);

                        break;

                        case "measurements":
                        $csv['Measurement_label'][]= '';
                        $csv['MEASUREMENT'][]= 'MEASUREMENT';
                        foreach ($value as $key2 => $value2) {
                          if ($key2==0) {
                             $name='NATURE';
                             $csv['Measurement_label'][]= 'NATURE_OF_MEASUREMENT';
                         }elseif($key2==1){
                             $name='ABBREVIATION';
                            $csv['Measurement_label'][]= 'ABBREVIATION';
                         }
                         elseif($key2==2){
                            $name='UNIT';
                            $csv['Measurement_label'][]= 'UNITS';
                        }

                        if ($value2=='Select abbreviation') {
                               $error = 'Measurements must be completed';
                            
                        }
                        $arrKey['MEASUREMENT'][0][$name] =htmlspecialchars($value2, ENT_QUOTES); 

                        $csv['MEASUREMENT'][]= htmlspecialchars($value2, ENT_QUOTES);

                                    /*$arrKey['MEASUREMENT'][$key2]['NATURE'] =$value2[0] ;
                                    $arrKey['MEASUREMENT'][$key2]['ABBREVIATION'] =$value2[1] ;
                                    $arrKey['MEASUREMENT'][$key2]['UNIT'] =$value2[2] ;*/
                                }
                                break;

                                case "methodology":
                                foreach ($value as $key2 => $value2) {
                                    if ($key2==0) {
                                     $name='SAMPLING_METHOD';
                                 }elseif($key2==1){
                                     $name='CONDITIONNING';
                                 }
                                 elseif($key2==2){
                                    $name='SAMPLE_STORAGE';
                                }

                                $arrKey['METHODOLOGY'][$key2]['NAME'] =$name ;
                                $arrKey['METHODOLOGY'][$key2]['DESCRIPTION'] =htmlspecialchars($value2, ENT_QUOTES); 
                                $csv[$name][]= 'METHODOLOGY';
                                $csv[$name][]= htmlspecialchars($name, ENT_QUOTES);
                                $csv[$name][]= htmlspecialchars($value2, ENT_QUOTES);

                            }

                            break;

                            case "methodology3":
                            foreach ($value as $key2 => $value2) {
                                $arrKey['METHODOLOGY'][$key2+3]['NAME'] ="Additional_comments" ;
                                $arrKey['METHODOLOGY'][$key2+3]['DESCRIPTION'] =htmlspecialchars($value2, ENT_QUOTES); 
                                $csv[$key2+3][]= 'METHODOLOGY';
                                $csv[$key2+3][]= htmlspecialchars($_POST['methodology2'][$key2], ENT_QUOTES);
                                $csv[$key2+3][]= htmlspecialchars($value2, ENT_QUOTES);
                            }
                            break;
                            case 'methodology2':
                            break;

                            case "institution":
                            foreach ($value as $key2 => $value2) {
                                $arrKey[strtoupper($key)][]['NAME'] = htmlspecialchars($value2, ENT_QUOTES);
                                $arrcsv[strtoupper($key)][] = htmlspecialchars($value2, ENT_QUOTES);

                            }


                            break;
                            case "scientific_fields":
                            foreach ($value as $key2 => $value2) {
                                $sc=htmlspecialchars($value2, ENT_QUOTES);;

                                $arrKey[strtoupper('SCIENTIFIC_FIELD')][]['NAME'] = $sc;
                                $arrcsv[strtoupper($key)][] = $sc;

                            }
                            break;


                            break;
                            case "sampling_points":
                            //var_dump($value);
                            $csv['sampling_point_label'][]= '';
                            $csv['sampling_point_label'][]= 'SAMPLING_POINTS';
                            $csv['sampling_point_label'][]= 'COORDINATE_SYSTEM';
                            $csv['sampling_point_label'][]= 'ABBREVIATION';
                            $csv['sampling_point_label'][]= 'LONGITUDE';
                            $csv['sampling_point_label'][]= 'LATITUDE';
                            $csv['sampling_point_label'][]= 'ELEVATION_M';
                            $csv['sampling_point_label'][]= 'SAMPLING';
                            $csv['sampling_point_label'][]= 'DESCRIPTION';

                            $csv['SAMPLING_POINT'][]= 'SAMPLING_POINT';


                            $arrKey['SAMPLING_POINT'][0]['NAME'] =$value[0] ;
                            $csv['SAMPLING_POINT'][]= htmlspecialchars($value[0], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['COORDINATE_SYSTEM'] =$value[1] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[1], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['ABBREVIATION'] =$value[2] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[2], ENT_QUOTES);                                    
                            $arrKey['SAMPLING_POINT'][0]['LONGITUDE'] =$value[3] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[3], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['LATITUDE'] =$value[4] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[4], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['ELEVATION'] =$value[5] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[5], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['SAMPLING'] =$value[6] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[6], ENT_QUOTES);
                            $arrKey['SAMPLING_POINT'][0]['DESCRIPTION'] =$value[7] ;
                              $csv['SAMPLING_POINT'][]= htmlspecialchars($value[7], ENT_QUOTES);
                              /*  foreach ($value as $key2 => $value2) {


                                   // $arrKey['SAMPLING_POINT'][strtoupper($key2)] = array_change_key_case ($value2 ,  CASE_UPPER  );
                              }*/


                              break;

                              case "sample_name":
                              $arrKey['SUPPLEMENTARY_FIELDS'][strtoupper($key)] =strtoupper(htmlspecialchars($value, ENT_QUOTES));
                              $sample_name=htmlspecialchars($value, ENT_QUOTES);
                              $arrcsv[strtoupper($key)] = htmlspecialchars($sample_name, ENT_QUOTES);
                              $sample_name_old=$sample_name;
                              break;
                              case "original_sample_name":
                              break;
                              case "csrf_value":
                              break;
                              case "csrf_name":
                              break;
                              case "file_already_uploaded":
                              break;

                              default:
                              if (in_array(strtoupper($key), $SUPPLEMENTARY_FIELDS)) {  
                                $key=strtoupper($key);
                                if ($key=='LITHOLOGY1') {
                                                $key='LITHOLOGY';
                                            }
                                            elseif ($key=='LITHOLOGY2') {
                                                $key='LITHOLOGY_2';
                                            } 
                                            elseif ($key=='LITHOLOGY3') {
                                                $key='LITHOLOGY_3';
                                            }     
                                            elseif ($key=='ORETYPE1') {
                                                $key='ORE_TYPE_1';
                                            }   
                                            elseif ($key=='ORETYPE2') {
                                                $key='ORE_TYPE_2';
                                            }   
                                            elseif ($key=='ORETYPE3') {
                                                $key='ORE_TYPE_3';
                                            }   
                                            elseif ($key=='TEXTURE1') {
                                                $key='TEXTURE_STRUCTURE_1';
                                            }   
                                            elseif ($key=='TEXTURE2') {
                                                $key='TEXTURE_STRUCTURE_2';
                                            }   
                                            elseif ($key=='TEXTURE3') {
                                                $key='TEXTURE_STRUCTURE_3';
                                            } 
                                $arrKey['SUPPLEMENTARY_FIELDS'][strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);
                                $arrcsv[strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);

                            }else{

                                $arrKey[strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);
                                $arrcsv[strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);

                            }




                        }



                    }
                        $arrcsv[]=$csv;


                    $user= New User();
                    $referents=$user->getProjectReferent($config['COLLECTION_NAME']);

                    foreach ($referents as $key => $value) {

                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['NAME_REFERENT'] = $value->name;
                       $arrcsv['NAME_REFERENT'][] = htmlspecialchars($value->name, ENT_QUOTES);

                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['FIRST_NAME_REFERENT'] = $value->firstname;
                      $arrcsv['FIRST_NAME_REFERENT'][] = htmlspecialchars($value->firstname, ENT_QUOTES);

                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['MAIL_REFERENT'] = $value->mail;
                        $arrcsv['MAIL_REFERENT'][] = htmlspecialchars($value->mail, ENT_QUOTES);


                       $arrKey['FILE_CREATOR'][$key]['NAME'] = $value->name;
                       $arrKey['FILE_CREATOR'][$key]['FIRST_NAME'] = $value->firstname;
                       $arrKey['FILE_CREATOR'][$key]['MAIL'] = $value->mail;
                       $arrKey['FILE_CREATOR'][$key]['DISPLAY_NAME'] = $value->name." ".$value->firstname;
                   }


                   $item=count($referents);     
                   $item++;

                   if ($route!='modify') {

                      

                   
                 

                       $filecreator[0]['NAME'] = $_SESSION['name'];

                       $filecreator[0]['FIRST_NAME'] = $_SESSION['firstname'];

                       $filecreator[0]['MAIL'] = $_SESSION['mail'];

                       $filecreator[0]['DISPLAY_NAME'] = $_SESSION['name']." ".$_SESSION['firstname'];

                       $arrKey['FILE_CREATOR']=array_merge($filecreator,$arrKey['FILE_CREATOR']);

                   }



       // foreach ($POST['measurements'] as $key => $value) {
                   $sample_name=$sample_name_old;
                   $arrKey["ACCESS_RIGHT"] = "Awaiting";
                   $arrKey["UPLOAD_DATE"]  = date('Y-m-d');
                   $arrKey["CREATION_DATE"]  = date('Y-m-d');
                   $arrcsv["CREATION_DATE"]  = date('Y-m-d');
                   $arrKey["METADATA_DATE"]  = date('Y-m-d');
                   //$arrKey["STATUS"]  = "Awaiting";
            // $sample_name=$sample_name.'_'.$value[1];
                   $sample_name = $sample_name.'_'.$_POST['measurements'][1];

        //echo   json_encode($arrKey);
                   if (!$error == null) {
         //si on rencontre une erreur on retourne le tableau et on l'affiche
                    $array['dataform'] = $arrKey;
                    $array['error']    = $error;
                    return $array;
                }else{

                   try {
                    if(empty($config['authSource']) && empty($config['username']) && empty($config['password'])) {
                        $this->db = new MongoDB\Driver\Manager("mongodb://" . $config['MDBHOST'] . ':' . $config['MDBPORT'], array('journal' => false));
                    } else {
                        $this->db= new MongoDB\Driver\Manager("mongodb://" . $config['MDBHOST'] . ':' . $config['MDBPORT'], array('journal' => false, 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $this->logger->error($e->getMessage());
                }
                if ($route=='modify') {
                                  if ($_POST['original_sample_name']) {
                                    //var_dump($_POST['original_sample_name']);
                                    //var_dump($_POST['sample_name'].'_'.strtoupper($_POST['measurements'][1]));
                                    if ($_POST['sample_name'].'_'.strtoupper($_POST['measurements'][1])!=$_POST['original_sample_name']) {
                                        //$sample_name=$_POST['sample_name'];
                                        $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1];
                                           $sample_search=$_POST['original_sample_name'];
                                       /* if (strpos($_POST['original_sample_name'], '_RAW') !== false) {
                                           $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW';
                                       }*/
                                   }
                                   else{
                                        //$sample_name=$_POST['sample_name'];
                                    $original_sample_name=$_POST['original_sample_name'];
                                    $sample_search=$_POST['sample_name'].'_'.$_POST['measurements'][1];

                                }
                            }

                            //var_dump($POST['file_already_uploaded']);
                               //$data=$arrKey['DATA'];
                            $filter = ['_id' => strtoupper($sample_search)];
                               // var_dump($filter);
                            $query = new MongoDB\Driver\Query($filter);
                            $cursor = $this->db->executeQuery($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $query);
                            foreach ($cursor as $document) {
                                //var_dump(($document->INTRO->FILE_CREATOR[0]->DISPLAY_NAME));
                               $filecreator[0]['NAME'] = $document->INTRO->FILE_CREATOR[0]->NAME;
                               $arrcsv['NAME'][] = htmlspecialchars($document->INTRO->FILE_CREATOR[0]->NAME, ENT_QUOTES);
                               $filecreator[0]['FIRST_NAME'] = $document->INTRO->FILE_CREATOR[0]->FIRST_NAME;
                               $arrcsv['FIRST_NAME'][] = htmlspecialchars($document->INTRO->FILE_CREATOR[0]->FIRST_NAME, ENT_QUOTES);
                               $filecreator[0]['MAIL'] = $document->INTRO->FILE_CREATOR[0]->MAIL;
                               $arrcsv['MAIL'][] = htmlspecialchars($document->INTRO->FILE_CREATOR[0]->MAIL, ENT_QUOTES);
                               $filecreator[0]['DISPLAY_NAME'] = $document->INTRO->FILE_CREATOR[0]->DISPLAY_NAME;
                               $arrcsv['FILE_CREATOR'][] = htmlspecialchars($document->INTRO->FILE_CREATOR[0]->DISPLAY_NAME, ENT_QUOTES);
                               //var_dump($filecreator);
                               $arrKey['FILE_CREATOR']=array_merge($filecreator,$arrKey['FILE_CREATOR']);


                                //($document);
                                $tmp_array=$document->DATA;
                              // var_dump($data);
                            }
                            foreach ($POST['file_already_uploaded'] as $key => $value) {
                                $file_already_uploaded[$key]['DATA_URL'] = $value;
                            }

                            $intersect = array();
                            $intersect_raw = array();

                           

                            

                            

                                        if (isset($tmp_array)) {
                                            foreach ($tmp_array->FILES as $key => $value) {

                                                foreach ($file_already_uploaded as $key => $value2) {
                                              // var_dump($value->DATA_URL);
                                                    if($value->DATA_URL==$value2['DATA_URL']){
                                                        /*if ($value->TYPE_DATA=='Rawdata') {
                                                            $intersect_raw['FILES'][]=(array)$value;
                                                        }
                                                        else{*/

                                                        $intersect['FILES'][]=(array)$value;
                                                       // }
                                                    //unset($data[$key]);
                                                    }


                                                }
                                            }
                                        }
                                        //echo "string";
                                       // var_dump($intersect);
                                        //var_dump($tmp_array);
                                           // print_r($data_samples);
                                       // var_dump($intersect);

                                               // var_dump($pictures);
                                      // var_dump($pictures);

                                        if (count($intersect) != 0 and ( (count($data) != 0) or (count($pictures) != 0) or (count($rawdata) != 0 or count($rawdata)==0))) { //si il y a eu des suppressions et des ajouts
                                        //print_r($intersect);
                                                       // var_dump($data);
                                            if (count($data) != 0) {
                                            foreach ($intersect['FILES'] as $key => $value) {
                                                    if ($value['TYPE_DATA']=='Data') {
                                                        unlink($value->ORIGINAL_DATA_URL);
                                                        unset($intersect['FILES'][$key]);
                                                        //$data_samples['SAMPLES']=$data['SAMPLES'];
                                                    }
                                                }
                                                //var_dump($rawdata);
                                                $intersect = array_merge_recursive($data, $intersect); // on merge les tableaux
                                                }
                                                if (count($pictures) != 0) {
                                                $intersect = array_merge_recursive($intersect, $pictures); // on merge les tableaux
                                                }
                                                if (count($rawdata) != 0 && $route=='upload') {
                                                $intersect = array_merge_recursive($intersect, $rawdata); // on merge les tableaux
                                                }else if((count($rawdata) == 0) || (count($rawdata)!=0) and $route=='modify'){
                                                    $is_raw_data=false;
                                                    $intersect_raw=$intersect;
                                                      foreach ($intersect_raw['FILES'] as $key => $value) {
                                                        if ($value['TYPE_DATA']=='Rawdata') {
                                                        unlink($value->ORIGINAL_DATA_URL);
                                                        unset($intersect['FILES'][$key]);
                                                        //$data_samples['SAMPLES']=$data['SAMPLES'];
                                                    }
                                                        if ($value['TYPE_DATA']=='Data') {
                                                            unset($intersect_raw['FILES'][$key]);
                                                            //$data_samples['SAMPLES']=$data['SAMPLES'];
                                                        }
                                                        if ($value['TYPE_DATA']=='Rawdata' || ((count($rawdata)!=0) and $route=='modify')) {
                                                            $is_raw_data=true;
                                                        }
                                                }
                                                if ($is_raw_data) {
                                                     $rawdata = array_merge_recursive($intersect_raw, $rawdata); // on merge les tableaux
                                                        $i=1;
                                                     foreach ($rawdata['FILES'] as $key => $value) {
                                                        if ($value['TYPE_DATA']=='Rawdata') {
                                                            $rawdata2['FILES'][0]=$value;
                                                        }else{

                                                         $rawdata2['FILES'][$i]=$value;
                                                        $i++;
                                                        }
                                                    }

                                                     
                                                     $rawdata=$rawdata2;
                                                

                                                }
                                                if (count($data)==0) {
                                                    $data_samples['SAMPLES']=$tmp_array->SAMPLES;
                                                }
                                               // var_dump($merge);
                                            }

                                            } 

                                            else if (count($intersect) != 0) {
                                                //echo "TEREE";

                                            // si il y a eu seulement des suppressions
                                                $data_samples['SAMPLES']=$tmp_array->SAMPLES;
                                                
                                            }else {
                                                //si il y a eu seuelement des ajouts
                                                $data_samples['SAMPLES']=$tmp_array->SAMPLES;
                                                $intersect = $data;
                                            }

                                           /* if (count($pictures)!=0) {
                                                $merge =array_merge_recursive($merge,$pictures);
                                               // var_dump($merge);
                                            }else{
                                                $pictures=array();
                                                //$merge =array_merge_recursive($merge,$pictures);
                                            }*/
                                          
                                           /* if (count($rawdata)!=0) {
                                                $merge_raw=$merge;
                                                 foreach ($merge['FILES'] as $key => $value) {
                                                    if ($value['TYPE_DATA']=='Rawdata') {
                                                      unlink($value['ORIGINAL_DATA_URL']);
                                                      unset($intersect_raw['FILES'][$key]);
                                                    }
                                                      if ($value['TYPE_DATA']=='Data') {
                                                      unlink($value['ORIGINAL_DATA_URL']);
                                                      unset($merge_raw['FILES'][$key]);
                                                    }

                                                
                                                }
                                                    $rawdata =array_merge_recursive($intersect_raw,$rawdata,$merge_raw);

                                            //var_dump($pictures);
                                           // var_dump($rawdata);
                                            //var_dump($intersect_raw);
                                                //$rawdata =array_merge_recursive($pictures,$intersect_raw);
                                            //var_dump($rawdata);
                                            }*/

                                           /* if ($intersect_raw){
                                                $merge_raw=$merge;
                                                foreach ($merge['FILES'] as $key => $value) {
                                                if ($value['TYPE_DATA']=='Data') {
                                                      unlink($value['ORIGINAL_DATA_URL']);
                                                      unset($merge_raw['FILES'][$key]);
                                                    }
                                                }
                                        $rawdata =array_merge_recursive($pictures,$merge_raw,$intersect_raw);

                                                
                                            }*/

                                        foreach ($intersect['FILES'] as $key => $value) {

                                            if ($value['TYPE_DATA']=='Rawdata') {
                                                unset($intersect['FILES'][$key]);
                                            }
                                        }

                                         foreach ($intersect['FILES'] as $key => $value) {

                                           $intersect2['FILES'][]=$value;
                                        }
                                        $intersect=$intersect2;
                                            if ($data_samples) {
                                                
                                            $intersect=array_merge($intersect,$data_samples);
                                            }
                                foreach ($tmp_array->FILES as $key => $value) {
                                    $array[]=(array)$value;
                                }

                                //var_dump($array);
                                //var_dump($intersect['FILES']);
                                //var_dump($rawdata);
                                $intersect3=array_merge($intersect['FILES'],$rawdata['FILES']);
                             


                                    $diff = array_map('unserialize',
                                                        array_diff(array_map('serialize', $array), array_map('serialize', $intersect3)));

                                 foreach ($diff as $key => $value) {
                                    unlink($value["ORIGINAL_DATA_URL"]);
                                 }

                                 foreach ($intersect['FILES'] as $key => $value) {
                                    //var_dump($value);
                                    $sample=explode('_', $sample_search);
                                    $sample2=explode('_', $original_sample_name);

                                    $newurl=preg_replace('/'.$sample[0].'/', $sample2[0], $value['ORIGINAL_DATA_URL'],1);
                                   // var_dump($value['DATA_URL']);
                                    //$dir=preg_replace('/'.$value['DATA_URL'].'/', '', $value['ORIGINAL_DATA_URL']);
                                    $dir=dirname($newurl);
                                    mkdir($dir);
                                    copy($value['ORIGINAL_DATA_URL'],$newurl);
                                    chmod($newurl, 0640);
                                    rmdir(dirname($value['ORIGINAL_DATA_URL']));
                                    $intersect['FILES'][$key]['ORIGINAL_DATA_URL']=$newurl;

                                 }
                                 foreach ($rawdata['FILES'] as $key => $value) {
                                    //var_dump($value);
                                    $sample=explode('_', $sample_search);
                                    $sample2=explode('_', $original_sample_name);

                                    $newurl=preg_replace('/'.$sample[0].'/', $sample2[0], $value['ORIGINAL_DATA_URL'],1);
                                    //var_dump($value['DATA_URL']);
                                    //$dir=preg_replace('/'.$value['DATA_URL'].'/', '', $value['ORIGINAL_DATA_URL']);
                                    $dir=dirname($newurl);
                                    //var_dump($dir);
                                    mkdir($dir);
                                    copy($value['ORIGINAL_DATA_URL'],$newurl);
                                    chmod($newurl, 0640);
                                    rmdir(dirname($value['ORIGINAL_DATA_URL']));
                                    $rawdata['FILES'][$key]['ORIGINAL_DATA_URL']=$newurl;

                                 }
                                

                            //var_dump($intersect);
                                $bulk = new MongoDB\Driver\BulkWrite;
                                try{
                                   //unset($arrKey["STATUS"]);
                                   
                                        $arrKey["ACCESS_RIGHT"] = "Unpublished";
                                   
                                   $insert=array('_id' => strtoupper($original_sample_name),"INTRO"=>$arrKey,'DATA'=>$intersect);
                                   $bulk->insert($insert);
                                    if (count($rawdata)!=0) {
                                       

                                          //unset($arrKey["STATUS"]);
                                         // var_dump($rawdata);
                                          $arrKey["MEASUREMENT"][0]["ABBREVIATION"]=$arrKey["MEASUREMENT"][0]["ABBREVIATION"].'_RAW';
                                           $insert=array('_id' => strtoupper($original_sample_name).'_RAW',"INTRO"=>$arrKey,'DATA'=>$rawdata);
                                           $bulk->insert($insert);

                                     }
                                   $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'], $bulk);
                                   $bulk = new MongoDB\Driver\BulkWrite;
                                   $bulk->delete(['_id' => strtoupper($_POST['original_sample_name'])]);
                                   $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);
                                    $repertoireDestination         = $UPLOAD_FOLDER;
                                    mkdir($repertoireDestination  ."/". $_POST['sample_name']."_META");
                                    $fp = fopen($repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $_POST['sample_name'].'_META.csv', 'w');
                                    //var_dump($intersect['FILES']);
                                   $picture_csv=[];
                                    $connection = \ssh2_connect($config['SSH_HOST'], 22);
                                    \ssh2_auth_password($connection, $config['SSH_UNIXUSER'], $config['SSH_UNIXPASSWD']);
                                    $measurement_csv= str_replace('_RAW', '', $arrKey["MEASUREMENT"][0]["ABBREVIATION"]);
                                   foreach ($intersect['FILES'] as $key => $value) {
                                    if ($value['TYPE_DATA']=='Data') {
                                         $stream = \ssh2_exec($connection, 'sudo -u ' . $config["DATAFILE_UNIXUSER"] . ' cp ' . $value['ORIGINAL_DATA_URL'].' '.$config['OWNCLOUD_FOLDER'].'/data/'.$measurement_csv.'/'.$value['DATA_URL'], false);
                                        stream_set_timeout($stream, 3);
                                        stream_set_blocking($stream, true);
                                        // read the output into a variable
                                        $data = '';
                                        while ($buffer = fread($stream, 4096)) {
                                            $data .= $buffer;
                                        }
                                        // close the stream
                                        fclose($stream);

                                        //$sftp = \ssh2_sftp($connection);
                                        //\ssh2_sftp_mkdir($sftp, $config['OWNCLOUD_FOLDER'].'/data/'.$measurement_csv);
                                       // \ssh2_scp_send($connection, $value['ORIGINAL_DATA_URL'], $config['OWNCLOUD_FOLDER'].'/data/'.$measurement_csv.'/'.$value['DATA_URL'], 0644);
                                           
                                       }
                                       if ($value['TYPE_DATA']=='Pictures') {
                                        $picture_csv[]='/Metadata/Pictures/'.$value['DATA_URL'];
                                        //\ssh2_scp_send($connection, $value['ORIGINAL_DATA_URL'], $config['OWNCLOUD_FOLDER'].'/Metadata/Pictures/'.$value['DATA_URL'], 0644);
                                        $stream = \ssh2_exec($connection, 'sudo -u ' . $config["DATAFILE_UNIXUSER"] . ' cp ' . $value['ORIGINAL_DATA_URL'].' '.$config['OWNCLOUD_FOLDER'].'/Metadata/Pictures/'.$value['DATA_URL'], false);
                                        stream_set_timeout($stream, 3);
                                        stream_set_blocking($stream, true);
                                        // read the output into a variable
                                        $data = '';
                                        while ($buffer = fread($stream, 4096)) {
                                            $data .= $buffer;
                                        }
                                        // close the stream
                                        fclose($stream);

                                           
                                       }
                                   }
                                    foreach ($rawdata['FILES'] as $key => $value) {
                                        if ($value['TYPE_DATA']=='Rawdata') {
                                        //$sftp = \ssh2_sftp($connection);
                                        //\ssh2_sftp_mkdir($sftp, $config['OWNCLOUD_FOLDER'].'/Raw-data-measurement/'.$measurement_csv);
                                        //\ssh2_scp_send($connection, $value['ORIGINAL_DATA_URL'], $config['OWNCLOUD_FOLDER'].'/Raw-data-measurement/'.$measurement_csv.'/'.$value['DATA_URL'], 0644);
                                        $stream = \ssh2_exec($connection, 'sudo -u ' . $config["DATAFILE_UNIXUSER"] . ' cp ' . $value['ORIGINAL_DATA_URL'].' '.$config['OWNCLOUD_FOLDER'].'/Raw-data-measurement/'.$measurement_csv.'/'.$value['DATA_URL'], false);
                                        stream_set_timeout($stream, 3);
                                        stream_set_blocking($stream, true);
                                        // read the output into a variable
                                        $data = '';
                                        while ($buffer = fread($stream, 4096)) {
                                            $data .= $buffer;
                                        }
                                        // close the stream
                                        fclose($stream);

                                           
                                       }
                                   }
                                    $arrcsv['PICTURE']=$picture_csv;

                                          foreach ($arrcsv as $key=>$line) {
                                            $csv=[];
                                           
                                            if (is_array($line)) {

                                               foreach ($line as $key2 => $value) {
                                                if (is_array($value)) {
                                                    $csv2=[];
                                                    foreach ($value as $key3 => $value2) {
                                                        $csv2[$key3]=$value2;
                                                    }
                                                    $csv[]=$csv2;
                                                     fputcsv($fp, $csv2);
                                                }else{

                                                   if ($key=='SCIENTIFIC_FIELDS') {
                                                       $key='SCIENTIFIC_FIELD';
                                                   }
                                                   elseif ($key=='KEYWORDS') {
                                                      $key='KEYWORD';
                                                   }

                                                $csv=array($key,$value);
                                                    
                                                fputcsv($fp, $csv);
                                                }
                                               

                                                

                                               }
                                            }else{

                                            if ($key=='LITHOLOGY1') {
                                                $key='LITHOLOGY';
                                            }
                                            elseif ($key=='LITHOLOGY2') {
                                                $key='LITHOLOGY_2';
                                            } 
                                            elseif ($key=='LITHOLOGY3') {
                                                $key='LITHOLOGY_3';
                                            }     
                                            elseif ($key=='ORETYPE1') {
                                                $key='ORE_TYPE_1';
                                            }   
                                            elseif ($key=='ORETYPE2') {
                                                $key='ORE_TYPE_2';
                                            }   
                                            elseif ($key=='ORETYPE3') {
                                                $key='ORE_TYPE_3';
                                            }   
                                            elseif ($key=='TEXTURE1') {
                                                $key='TEXTURE_STRUCTURE_1';
                                            }   
                                            elseif ($key=='TEXTURE2') {
                                                $key='TEXTURE_STRUCTURE_2';
                                            }   
                                            elseif ($key=='TEXTURE3') {
                                                $key='TEXTURE_STRUCTURE_3';
                                            }   
                                            $csv=array($key,$line);
                                            fputcsv($fp, $csv);
                                            }
                                          
                                            }


                                        fclose($fp);
                                        chmod($repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $_POST['sample_name'].'_META.csv', 0640);
                                        
                                         //\ssh2_scp_send($connection, $repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $_POST['sample_name'].'_META.csv', $config['OWNCLOUD_FOLDER'].'/Metadata/'.$_POST['sample_name'].'_META.csv', 0644);
                                         $stream = \ssh2_exec($connection, 'sudo -u ' . $config["DATAFILE_UNIXUSER"] . ' cp ' . $repertoireDestination  ."/". $_POST['sample_name'] . "_META/" . $_POST['sample_name'].'_META.csv'.' '.$config['OWNCLOUD_FOLDER'].'/Metadata/'.$_POST['sample_name'].'_META.csv', false);
                                        stream_set_timeout($stream, 3);
                                        stream_set_blocking($stream, true);
                                        // read the output into a variable
                                        $data = '';
                                        while ($buffer = fread($stream, 4096)) {
                                            $data .= $buffer;
                                        }
                                        // close the stream
                                        fclose($stream);

                                        $mail = new Mailer();
                                        $user = new User();
                                        $referents=$user->getProjectReferent($config['COLLECTION_NAME']);
                                        foreach ($referents as $key => $value) {
                                                $mail->Send_mail_data_validate($sample_name,$config['COLLECTION_NAME'],$value->mail);
                                        }

                                   return true;
                               }
                               catch (MongoDB\Driver\Exception\BulkWriteException  $e) {
                                   $array['dataform'] = $arrKey;
                                   $array['error']    = 'Sample name already in database';
                                   return $array;
                               }
                           
            }
           elseif($route=='upload') {

             try{
                $bulk = new MongoDB\Driver\BulkWrite;
                $filter=array();
                $filter = ['_id' => strtoupper($sample_name)];


                $query = new MongoDB\Driver\Query($filter);
                $cursor = $this->db->executeQuery($config['dbname'].'.'.$config['COLLECTION_NAME'], $query);



                foreach ($cursor as $document) {
                    if($document->_id== strtoupper($sample_name)){
                        $array['dataform'] = $arrKey;
                        $array['error']    = 'Sample name already in database';
                        return $array;
                    }
                }
                $data =array_merge_recursive($data,$pictures,$rawdata,$data_samples);
                $insert=array('_id' => strtoupper($sample_name),"INTRO"=>$arrKey,'DATA'=>$data);
                $bulk->insert($insert);
                $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);
                $mail = new Mailer();
                $referents=$user->getProjectReferent($config['COLLECTION_NAME']);
                foreach ($referents as $key => $value) {
                    $mail->Send_mail_new_data_to_approve($value->mail,$config['COLLECTION_NAME'],$_SESSION['mail']);
                }
                return true;
            }
            catch (MongoDB\Driver\Exception\BulkWriteException  $e) {
               $array['dataform'] = $arrKey;
               $array['error']    = 'Sample name already in database';
               return $array;
           }

          /* if ($rawdata) {
            try{
               $bulk = new MongoDB\Driver\BulkWrite;

               $filter=array();
               $filter = ['_id' => strtoupper($sample_name).'_RAW'];


               $query = new MongoDB\Driver\Query($filter);
               $cursor = $this->db->executeQuery($config['dbname'].'.'.$config['COLLECTION_NAME'], $query);

               foreach ($cursor as $document) {
                if($document->_id== strtoupper($sample_name)){
                    $array['dataform'] = $arrKey;
                    $array['error']    = 'Sample name already in database';
                    return $array;
                }
            }

            $rawdata =array_merge_recursive($rawdata,$pictures);


            $insert=array('_id' => strtoupper($sample_name).'_RAW',"INTRO"=>$arrKey,'DATA'=>$rawdata);
            
            $bulk->insert($insert);
            $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);
            exit();
            return true;
        }
        catch (MongoDB\Driver\Exception\BulkWriteException  $e) {
           $array['dataform'] = $arrKey;
           $array['error']    = 'Sample name already in database';
           return $array;
       }
   }else{
    return true;
}*/
}
}



   // }
}



function delete_data($id)
{
    $config = self::ConfigFile();

    try {
        if(empty($config['authSource']) && empty($config['username']) && empty($config['password'])) {
            $this->db = new MongoDB\Driver\Manager("mongodb://" . $config['MDBHOST'] . ':' . $config['MDBPORT'], array('journal' => false));
        } else {
            $this->db= new MongoDB\Driver\Manager("mongodb://" . $config['MDBHOST'] . ':' . $config['MDBPORT'], array('journal' => false, 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        $this->logger->error($e->getMessage());
    }

    $data=self::Request_data_awaiting($id);
    foreach ($data['_source']['DATA']['FILES'] as $key => $value) {
        unlink($value['ORIGINAL_DATA_URL']);
        rmdir(dirname($value['ORIGINAL_DATA_URL']));

    }
    
    $mail = new Mailer();
    $user = new User();
    $referents=$user->getProjectReferent($config['COLLECTION_NAME']);
    foreach ($referents as $key => $value) {
            $mail->Send_mail_data_refused($id,$config['COLLECTION_NAME'],$value->mail);
    }
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete(['_id' => strtoupper($id)]);

    $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);



}





}

?>
