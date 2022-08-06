<?php
/*********************************************************************\
| (c)2014-2021 Synoboos                   httpa://www.synoboost.com   |
|---------------------------------------------------------------------|
| This program is free software; you can redistribute it and/or       |
| modify it under the terms of the GNU General Public License         |
| as published by the Free Software Foundation; either version 2      |
| of the License, or (at your option) any later version.              |
|                                                                     |
| This program is distributed in the hope that it will be useful,     |
| but WITHOUT ANY WARRANTY; without even the implied warranty of      |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       |
| GNU General Public License for more details.                        |
|                                                                     |
| You should have received a copy of the GNU General Public License   |
| along with this program; If not, see <http://www.gnu.org/licenses/> |
\*********************************************************************/
?>
<?php

class SynoDLMSearchYTS{
        private $qurl  = 'http://service.dtn-tech.com/dlm/proxy/https://yts.mx/api/v2/list_movies.json?query_term=%s&sort_by=rating&limit=50';

        public function __construct() {

        }

        public function prepare($curl, $query) {
                $url = sprintf($this->qurl, urlencode($query));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($curl, CURLOPT_TIMEOUT, 8);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_URL, $url);                      
        }

        public function parse($plugin, $response) {
                $json = json_decode($response,true);
                if ($json["status"]!="ok") {                 
                    return 0;
                }
                if ($json["data"]["movie_count"]=="0") {                  
                    return 0;
                }
                $res=0;                
                foreach($json["data"]["movies"] as $movie){
                    foreach($movie["torrents"] as $torrent){
                        $title=$movie["title_long"].sprintf(" [%s / %s]",ucwords($torrent["type"]),$torrent["quality"]);
                        $download=$torrent["url"];
                        $size=$torrent["size_bytes"];
                        $datetime=$torrent["date_uploaded"];
                        $page=$movie["url"];
                        $hash=$torrent["hash"];
                        $seeds=$torrent["seeds"];
                        $leechs=$torrent["peers"];
                        $category="电影";
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
    
$keyword='Strange';
$ch = curl_init();
$dlm=new SynoDLMSearch();
$plugin=new DLMPlugin();

$dlm->prepare($ch,$keyword);
$response=curl_exec($ch);
$dlm->parse($plugin,$response);
*/

?>