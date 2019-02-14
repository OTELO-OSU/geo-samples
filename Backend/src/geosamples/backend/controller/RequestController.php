<?php
namespace geosamples\backend\controller;
use \geosamples\backend\controller\UserController as User;
use \geosamples\backend\controller\FileController as File;
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
            if ($value['_source']['INTRO']['STATUS']=='Awaiting') {
             
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
            "includes": [ "INTRO.SAMPLING_DATE","INTRO.TITLE","INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY","INTRO.SUPPLEMENTARY_FIELDS.DESCRIPTION","INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME","INTRO.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE","INTRO.SUPPLEMENTARY_FIELDS.NAME_REFERENT","INTRO.SUPPLEMENTARY_FIELDS.FIRST_NAME_REFERENT",
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
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $value['_source']['INTRO']['SUPPLEMENTARY_FIELDS'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['TITLE'] = $value['_source']['INTRO']['TITLE'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_POINT'] = $value['_source']['INTRO']['SAMPLING_POINT'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LAT'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LONG'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                foreach ($value['_source']['DATA']['FILES'] as $key => $file)
                {
                    if (exif_imagetype($file['ORIGINAL_DATA_URL']))
                    {
                        $return[$current][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['PICTURES'][$key] = $file;
                    }
                }
            }
            if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['MEASUREMENT'][] = $value['_source']['INTRO']['MEASUREMENT'];
            }

        }
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
        if ($sort['lithology'])
        {
           $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
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
    if ($sort['lithology'])
    {
       $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
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
        $mesure = '';
        $sort = json_decode($sort, true);
        if ($sort['lithology'])
        {
            $lithology = 'INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY:"' . urlencode($sort['lithology']) . '"%20AND%20';
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
        $url = $config['ESHOST'] . '/' . $config['INDEX_NAME'] . "/_search?q=" . $lithology . $mesure . $date . "type=" . $config['COLLECTION_NAME'] ."&size=10000";
        $postcontent = '{ "_source": { 
            "includes": [ "INTRO.SAMPLING_DATE","INTRO.TITLE","INTRO.SUPPLEMENTARY_FIELDS.LITHOLOGY","INTRO.SUPPLEMENTARY_FIELDS.DESCRIPTION","INTRO.SUPPLEMENTARY_FIELDS.SAMPLE_NAME","INTRO.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE","INTRO.SUPPLEMENTARY_FIELDS.NAME_REFERENT","INTRO.SUPPLEMENTARY_FIELDS.FIRST_NAME_REFERENT",
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
            $longitude = (float)$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];
            $latitude = (float)$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
            if ((strtoupper($sort['mesure']) == strtoupper($value['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION']) or $sort['mesure'] == null) and (($latitude >= $sort['lat']['lat1']) && $latitude < $sort['lat']['lat2']) && ($longitude >= $sort['lon']['lon2'] && $longitude < $sort['lon']['lon1']) or $sort['lat'] == null or $sort['lon'] == null)
            {
                if (!$return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']])
                {
                    $current = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_DATE'] = $value['_source']['INTRO']['SAMPLING_DATE'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SUPPLEMENTARY_FIELDS'] = $value['_source']['INTRO']['SUPPLEMENTARY_FIELDS'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['TITLE'] = $value['_source']['INTRO']['TITLE'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['SAMPLING_POINT'] = $value['_source']['INTRO']['SAMPLING_POINT'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LAT'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'];
                    $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']]['COORDINATES']['LONG'] = $value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE'];

                    foreach ($value['_source']['DATA']['FILES'] as $key => $file)
                    {
                        if (exif_imagetype($file['ORIGINAL_DATA_URL']))
                        {
                            $return[$current][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['PICTURES'][$key] = $file;
                        }
                    }

                }
                if (($_SESSION['mail'] && in_array($value['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
                 $return[$value['_source']['INTRO']['SAMPLING_POINT'][0]['LATITUDE'].'/'.$value['_source']['INTRO']['SAMPLING_POINT'][0]['LONGITUDE']][$value['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME']]['MEASUREMENT'][] = $value['_source']['INTRO']['MEASUREMENT'];
             }

             $responses = $return;
         }
     }
     $responses = json_encode($responses);
     return $responses;
 }

 function Request_poi_data($id)
 {
    $explode = explode('_', $id, 3);
    $id=$explode[0].'_'.$explode[1];
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
    if (($_SESSION['mail'] && in_array($response['hits']['hits'][0]['_type'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin']==1) ) {
        $identifier = $response['hits']['hits'][0]['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'] . '_' . $response['hits']['hits'][0]['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION'];

        if ($identifier == $id)
        {
            $response = json_encode($response['hits']['hits'][0]['_source']['DATA']);
            return $response;
        }
    }
}

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

    foreach ($response['hits']['hits'][0]['_source']['DATA']['FILES'] as $key => $value)
    {

        if ($value['DATA_URL'] == $picturename)
        {
            $img = $value['ORIGINAL_DATA_URL'];
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
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=" . basename($filepath));
            $readfile = file_get_contents($filepath);
            print $readfile;
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
        $rawdata=null;
        $data=null;
        $pictures=null;

        
        
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
                    if (rename($_FILES["data"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1]. "/" . $nomDestination)) {
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

                            $data["SAMPLES"]=$data_samples["SAMPLES"];
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

                        $data["SAMPLES"]=$data_samples["SAMPLES"];
                        



                    }

                    
                    

                    

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
            if (rename($_FILES["pictures"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'] . "_META/" . $nomDestination)) {
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
            if (rename($_FILES["rawdata"]["tmp_name"][$i], $repertoireDestination ."/". $_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW'. "/" . $nomDestination)) {
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

                    $SUPPLEMENTARY_FIELDS=array('HOST_LITHOLOGY_OR_PROTOLITH','LITHOLOGY1','LITHOLOGY2','LITHOLOGY3','ORETYPE1','ORETYPE2','ORETYPE3','TEXTURE1','TEXTURE2','TEXTURE3','SUBSTANCE','STORAGE_DETAILS','HOST_AGE','MAIN_EVENT_AGE','OTHER_EVENT_AGE','ALTERATION_DEGREE','SAMPLE_NAME','BLOCK','PULP','SAFETY_CONSTRAINTS','SAMPLE_LOCATION_FACILITY','DESCRIPTION');
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
                        
                        
                        case "keywords":
                        foreach ($value as $key2 => $value2) {
                            $arrKey[strtoupper($key)][]['NAME'] = htmlspecialchars($value2, ENT_QUOTES);
                        }
                        break;
                        case "core":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][]['CORE'] = $value;
                        
                        break;
                        case "core_depth":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['DEPTH'] = htmlspecialchars($value, ENT_QUOTES);;
                        
                        break;
                        case "core_azimut":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['AZIMUT'] = htmlspecialchars($value, ENT_QUOTES);;
                        
                        break;
                        case "core_dip":
                        $arrKey['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['DIP'] = htmlspecialchars($value, ENT_QUOTES);;
                        
                        break;
                        case "sampling_date":
                        $arrKey[strtoupper($key)][] = htmlspecialchars($value, ENT_QUOTES);;
                        break;

                        case "measurements":
                        foreach ($value as $key2 => $value2) {
                          if ($key2==0) {
                             $name='NATURE';
                         }elseif($key2==1){
                             $name='ABBREVIATION';
                         }
                         elseif($key2==2){
                            $name='UNIT';
                        }

                        
                        $arrKey['MEASUREMENT'][0][$name] =htmlspecialchars($value2, ENT_QUOTES); ;

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
                                $arrKey['METHODOLOGY'][$key2]['DESCRIPTION'] =htmlspecialchars($value2, ENT_QUOTES); ;
                            }

                            break;

                            case "methodology3":
                            foreach ($value as $key2 => $value2) {
                                $arrKey['METHODOLOGY'][$key2+3]['NAME'] =$_POST['methodology2'][$key2] ;
                                $arrKey['METHODOLOGY'][$key2+3]['DESCRIPTION'] =htmlspecialchars($value2, ENT_QUOTES); ;
                            }
                            break;
                            case 'methodology2':
                            break;

                            case "institution":
                            foreach ($value as $key2 => $value2) {
                                $arrKey[strtoupper($key)][]['NAME'] = htmlspecialchars($value2, ENT_QUOTES);;
                            }
                            
                            
                            break;
                            case "scientific_fields":
                            foreach ($value as $key2 => $value2) {
                                $sc=htmlspecialchars($value2, ENT_QUOTES);;

                                $arrKey[strtoupper($key)][]['NAME'] = $sc;
                            }
                            break;
                            
                            
                            break;
                            case "sampling_points":
                            $arrKey['SAMPLING_POINT'][$key2]['NAME'] =$value[0] ;
                            $arrKey['SAMPLING_POINT'][$key2]['COORDINATE_SYSTEM'] =$value[1] ;
                            $arrKey['SAMPLING_POINT'][$key2]['ABBREVIATION'] =$value[2] ;                                    
                            $arrKey['SAMPLING_POINT'][$key2]['LONGITUDE'] =$value[3] ;
                            $arrKey['SAMPLING_POINT'][$key2]['LATITUDE'] =$value[4] ;
                            $arrKey['SAMPLING_POINT'][$key2]['ELEVATION'] =$value[5] ;
                            $arrKey['SAMPLING_POINT'][$key2]['SAMPLING'] =$value[6] ;
                            $arrKey['SAMPLING_POINT'][$key2]['DESCRIPTION'] =$value[7] ;
                              /*  foreach ($value as $key2 => $value2) {


                                   // $arrKey['SAMPLING_POINT'][strtoupper($key2)] = array_change_key_case ($value2 ,  CASE_UPPER  );
                              }*/
                              
                              
                              break;

                              case "sample_name":
                              $arrKey['SUPPLEMENTARY_FIELDS'][strtoupper($key)] =strtoupper(htmlspecialchars($value, ENT_QUOTES));
                              $sample_name=htmlspecialchars($value, ENT_QUOTES);;
                              $sample_name_old=$sample_name;
                              break;
                              case "original_sample_name":
                              break;

                              default:
                              if (in_array(strtoupper($key), $SUPPLEMENTARY_FIELDS)) {
                                $arrKey['SUPPLEMENTARY_FIELDS'][strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);
                            }else{

                                $arrKey[strtoupper($key)] = htmlspecialchars($value, ENT_QUOTES);
                            }




                        }



                    }
                    $user= New User();
                    $referents=$user->getProjectReferent($config['COLLECTION_NAME']);
                    
                    foreach ($referents as $key => $value) {
                      
                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['NAME_REFERENT'] = $value->name;
                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['FIRST_NAME_REFERENT'] = $value->firstname;
                       $arrKey['SUPPLEMENTARY_FIELDS']['REFERENT'][$key]['MAIL'] = $value->mail;

                       $arrKey['FILE_CREATOR'][$key]['NAME'] = $value->name;
                       $arrKey['FILE_CREATOR'][$key]['FIRST_NAME'] = $value->firstname;
                       $arrKey['FILE_CREATOR'][$key]['MAIL'] = $value->mail;
                       $arrKey['FILE_CREATOR'][$key]['DISPLAY_NAME'] = $value->name." ".$value->firstname;
                   }

                   
                   $item=count($referents);     
                   $item++;
                   $arrKey['FILE_CREATOR'][$item]['NAME'] = $_SESSION['name'];
                   $arrKey['FILE_CREATOR'][$item]['FIRST_NAME'] = $_SESSION['firstname'];
                   $arrKey['FILE_CREATOR'][$item]['MAIL'] = $_SESSION['mail'];
                   $arrKey['FILE_CREATOR'][$item]['DISPLAY_NAME'] = $_SESSION['name']." ".$_SESSION['firstname'];

                   


       // foreach ($POST['measurements'] as $key => $value) {
                   $sample_name=$sample_name_old;
                   $arrKey["ACCESS_RIGHT"] = "Draft";
                   $arrKey["UPLOAD_DATE"]  = date('Y-m-d');
                   $arrKey["METADATA_DATE"]  = date('Y-m-d');
                   $arrKey["STATUS"]  = "Awaiting";
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
                        $this->db = new MongoDB\Driver\Manager("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => false));
                    } else {
                        $this->db= new MongoDB\Driver\Manager("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => false, 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $this->logger->error($e->getMessage());
                }

                if ($route=='modify') {
                  if ($_POST['original_sample_name']) {
                    if ($_POST['sample_name'].'_'.strtoupper($_POST['measurements'][1])!=$_POST['original_sample_name']) {
                        //$sample_name=$_POST['sample_name'];
                        $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1];
                        if (strpos($_POST['original_sample_name'], '_RAW') !== false) {
                           $original_sample_name=$_POST['sample_name'].'_'.$_POST['measurements'][1].'_RAW';
                       }
                   }
                   else{
                        //$sample_name=$_POST['sample_name'];
                    $original_sample_name=$_POST['original_sample_name'];
                    
                }
            }

            var_dump($POST['file_already_uploaded']);
              // $data=$arrKey['DATA'];
            $filter = ['_id' => strtoupper($original_sample_name)];
               // var_dump($filter);
            $query = new MongoDB\Driver\Query($filter);
            $cursor = $this->db->executeQuery($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $query);
            foreach ($cursor as $document) {
                //($document);
                $tmp_array=$document->DATA;
              // var_dump($data);
            }
            foreach ($POST['file_already_uploaded'] as $key => $value) {
                $file_already_uploaded[$key]['DATA_URL'] = $value;
            }

            $intersect = array();
            if (isset($tmp_array)) {
                foreach ($tmp_array->FILES as $key => $value) {
                    
                    foreach ($file_already_uploaded as $key => $value2) {
                  // var_dump($value->DATA_URL);
                        if($value->DATA_URL==$value2['DATA_URL']){
                            $intersect['FILES'][]=$value;
                        //unset($data[$key]);
                        }
                    }
                }
            }
            var_dump($intersect);
            var_dump($data);
            $data_samples=$data['SAMPLES'];

            if (count($intersect) != 0 and $data != 0) { //si il y a eu des suppressions et des ajouts
                    $merge = array_merge($intersect, $data['FILES']); // on merge les tableaux
                    $merge=array_merge($merge,$data_samples);
                } else if (count($intersect) != 0) {
// si il y a eu seulement des suppressions
                    $merge = $intersect;
                } else {
                    //si il y a eu seuelement des ajouts
                    $merge = $data;
                    $merge =array_merge_recursive($merge,$pictures);
                }

            //print_r($merge);
                $bulk = new MongoDB\Driver\BulkWrite;
                try{
                   unset($arrKey["STATUS"]);
                   $insert=array('_id' => strtoupper($original_sample_name),"INTRO"=>$arrKey,'DATA'=>$merge);
                   $bulk->insert($insert);
                   $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'], $bulk);
                   $bulk = new MongoDB\Driver\BulkWrite;
                   $bulk->delete(['_id' => strtoupper($_POST['original_sample_name'])]);
                   $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);

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
                $data =array_merge_recursive($data,$pictures);
                $insert=array('_id' => strtoupper($sample_name),"INTRO"=>$arrKey,'DATA'=>$data);
                $bulk->insert($insert);
                $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);
            }
            catch (MongoDB\Driver\Exception\BulkWriteException  $e) {
               $array['dataform'] = $arrKey;
               $array['error']    = 'Sample name already in database';
               return $array;
           }

           if ($rawdata) {
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
}
}
}



   // }
}



function delete_data($id)
{
    $config = self::ConfigFile();

    try {
        if(empty($config['authSource']) && empty($config['username']) && empty($config['password'])) {
            $this->db = new MongoDB\Driver\Manager("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => false));
        } else {
            $this->db= new MongoDB\Driver\Manager("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => false, 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        $this->logger->error($e->getMessage());
    }

    $data=self::Request_data_awaiting($id);
    foreach ($data['_source']['DATA']['FILES'] as $key => $value) {
        unlink($value['ORIGINAL_DATA_URL']);
    }
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete(['_id' => strtoupper($id)]);

    $this->db->executeBulkWrite($config['dbname'].'.'.$config['COLLECTION_NAME'].'_sandbox', $bulk);
    

}





}

?>
