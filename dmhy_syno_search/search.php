<?php

class SynoDLMSearch {
    private $qurl = 'https://service.dtn-tech.com/dlm/proxy/https://share.dmhy.org/topics/list?keyword=%s';
    private $site = 'https://share.dmhy.org';

    public function __construct() {
    }

    private function remove_tags($text){
        return preg_replace('/\s*<[^>]+>\s*/si',"",$text);
    }

    public function prepare($curl, $query) {
        curl_setopt($curl, CURLOPT_TIMEOUT, 8);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, sprintf($this->qurl, urlencode($query)));
    }

    public function parse($plugin, $response) {
        $site=$this->site;
        $begin_pos = strrpos($response, '<tbody>');
        $end_pos   = strrpos($response, '</tbody>');
        $response  = substr($response, $begin_pos+7, $end_pos-$begin_pos-7);

        $reg_tr = "<tr class=\"\">(.*?)<\/tr>";
        $reg_td = "<td .*?>(.*?)<\/td>";
        $reg_datetime = "<span[^>]+>(.*?)<\/span>";
        $reg_taga = '<a\s+href="([^"]+)"[^>]+>\s*(.*?)\s*<\/a>';
        $reg_magnet = 'href="(magnet:\?xt=urn:btih:[^"]+)';
        $reg_size = '(\d+\.?\d*)([K|M|G]B)';

        $res=0;
        if(preg_match_all("/$reg_tr/si", $response, $matches_tr, PREG_SET_ORDER)) {
            foreach($matches_tr as $match_tr){
                $title="(Unknow)";
                $download="(Unknow)";
                $size=0;
                $datetime="1900-1-1";
                $page="(Unknow)";
                $hash="(Unknow)";
                $seeds=0; 
                $leechs=0;
                $category="Unknown category";    
                
                if(preg_match_all("/$reg_td/si", $match_tr[1], $matches_td, PREG_SET_ORDER)) {
                    preg_match_all("/$reg_datetime/si", $matches_td[0][1], $matches_datetime, PREG_SET_ORDER);
                    $datetime = $matches_datetime[0][1];
                    
                    $category = $this->remove_tags($matches_td[1][1]);
                    
                    preg_match_all("/$reg_taga/si", $matches_td[2][1], $matches_taga, PREG_SET_ORDER);
                    $link = end($matches_taga);
                    $page = $site . $link[1];
                    $title = $this->remove_tags($link[2]);
                    
                    preg_match("/$reg_magnet/si",$matches_td[3][1], $magnet_link);
                    $download = $magnet_link[1];
                    
                    preg_match("/$reg_size/si",$matches_td[4][1], $size_unit);
                    $size = $size_unit[1];
                    switch ($size_unit[2]){
                        case 'KB':
                             $size = $size * 1024;
                             break;
                        case 'MB':
                             $size = $size * 1024 * 1024;
                             break;
                        case 'GB': 
                             $size = $size * 1024 * 1024 * 1024;
                             break;
                    }
                    $size = floor($size);
                    
                    $hash = md5($download);
                    
                    $seeds=floor(str_replace('-','',$this->remove_tags($matches_td[5][1])));
                    
                    $leechs=floor(str_replace('-','',$this->remove_tags($matches_td[6][1])));
                }
                $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
                $res++;
            }
        }
        return $res;       
    }
}


/*
class DLMPlugin {
    public function __construct() {
    }

    public function addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category){
        echo $title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category;
    }
}

$keyword='OVERLORD';
$ch = curl_init();
$dlm=new SynoDLMSearch();
$plugin=new DLMPlugin();

$dlm->prepare($ch,$keyword);
$response=curl_exec($ch);
$dlm->parse($plugin,$response);
*/
?>