<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Api extends Controller
{
    public function list($param, $name='') {
        if($param=="rank" && $name!='')
            $param = "country";
        $url = "http://scoresaber.com/global?$param=$name";
        if($fp = fopen($url, 'r')) {
            $content = '';
            while($line = fread($fp, 1024)) $content .= $line;

            $tbody = explode('</tbody>', explode('<tbody>', $content)[1])[0];

            preg_match_all('/picture">(.|\n)*?src="(.*?)"/', $tbody, $res);
            $avatar = $res[2];
            preg_match_all('/rank">\n\s+#([0-9,]+)/', $tbody, $res);
            $rank = $res[1];
            preg_match_all('/flags\/(.*?)\.png/', $tbody, $res);
            $country = $res[1];
            preg_match_all('/700">(.+)<\/span/', $tbody, $res);
            $name = $res[1];
            preg_match_all('/ppValue">([0-9,.]+)/', $tbody, $res);
            $pp = $res[1];
            preg_match_all('/diff">\n\s+(<.*?>\+?)?(-?[0-9,]+|0|\s)\s*<\//', $tbody, $res);
            $weekly_change = $res[2];
            preg_match_all('/href="\/u\/([0-9]+)/', $tbody, $res);
            $url = $res[1];
            $list = [];

            for($i=0; $i<count($name); $i++) {
                array_push($list, [
                    'name' => $name[$i],
                    'avatar' => (preg_match('/^https?/', $avatar[$i]) ? '' : "http://scoresaber.com").$avatar[$i],
                    'rank' => (int)str_replace(',', '', $rank[$i]),
                    'country' => $country[$i],
                    'pp' => (float)str_replace(',', '', $pp[$i]),
                    'weekly_change' => (int)preg_replace('/[,\n]/', '', $weekly_change[$i]),
                    'url' => $url[$i],
                ]);
            }
            return json_encode($list, JSON_UNESCAPED_UNICODE);
        }
    }

    public function profile($id) {
        if($fp = fopen("http://scoresaber.com/u/$id", 'r')) {
            $content = '';
            while($line = fread($fp, 1024)) $content .= $line;

            preg_match('/title>(.+)\'/', $content, $name);
            if(is_null($name)) return '';
            preg_match('/flags\/([a-z]{2}).png/', $content, $country);
            preg_match('/avatar">\n\s+<img.*?src="(.*?)"/', $content, $avatar);
            preg_match('/global">.*#([0-9,]+).*#([0-9,]+)<\/a>/', $content, $rank);
            preg_match('/([0-9,.]+)pp/', $content, $pp);
            preg_match('/Play Count.*\s([0-9,]+)/', $content, $playcount);
            preg_match('/Total Score.*\s([0-9,]+)/', $content, $totalscore);
            preg_match('/Replays.*\s([0-9,]+)/', $content, $replay);

            $data = [
                'name' => $name[1],
                'country' => $country[1],
                'avatar' => (preg_match('/^https?/', $avatar[1]) ? '' : "http://scoresaber.com").$avatar[1],
                'rank_global' => (int)str_replace(',', '', $rank[1]),
                'rank_country' => (int)str_replace(',', '', $rank[2]),
                'pp' => (float)str_replace(',', '', $pp[1]),
                'playcount' => (int)str_replace(',', '', $playcount[1]),
                'totalscore' => (int)str_replace(',', '', $totalscore[1]),
                'replays' => (int)str_replace(',', '', $replay[1]),
            ];
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    public function score($param, $id, $page = 1) {
        $sort = $param=='topscore' ? 1 : 2;
        $url = "http://scoresaber.com/u/$id?sort=$sort&page=$page";
        if($fp = fopen($url, 'r')) {
            $content = '';
            while($line = fread($fp, 1024)) $content .= $line;
            
            $tbody = explode('</tbody>', explode('<tbody>', $content)[1])[0];
        
            preg_match_all('/rank">\n\s+#([0-9,]+)/', $tbody, $res);
            $rank = $res[1];
            if(is_null($rank)) return '';
            preg_match_all('/<div style="display: flex; align-items: center; justify-content: center;">\s*<img src="(.*?)"/', $tbody, $res);
            $thumb = $res[1];
            preg_match_all('/pp">(.*?)\s?<span/', $tbody, $res);
            $name = $res[1];
            preg_match_all('/">(Expert\+|Expert|Hard|Normal|Easy)<\/span/', $tbody, $res);
            $difficult = $res[1];
            preg_match_all('/mapper">(.*?)<\/span/', $tbody, $res);
            $mapper = $res[1];
            preg_match_all('/time">(.*?)<\/span/', $tbody, $res);
            $time = $res[1];
            preg_match_all('/ppValue">([0-9,.]+)<\/span/', $tbody, $res);
            $pp = $res[1];
            preg_match_all('/ppWeightedValue">\(([0-9,.]+)<span/', $tbody, $res);
            $pp_weight = $res[1];
            preg_match_all('/(accuracy|score): ([0-9,.]+%?(\s\([A-Z,]*\))?)<\/span/', $tbody, $res);
            $accuracy = $res[2];
            
            preg_match('/([0-9]+)<\/a>\n\s+<\/li>\n\s+<\/ul>/', $content, $res);
            $total_page = $res[1];

            $list = [$total_page];
            for($j=0; $j<count($name); $j++) {
                if(strpos($accuracy[$j], '%')) $tmp = 'accuracy';
                else $tmp = 'score';
                array_push($list, [
                    'thumb' => 'http://scoresaber.com'.$thumb[$j],
                    'name' => $name[$j],
                    'difficult' => $difficult[$j],
                    'mapper' => $mapper[$j],
                    'rank' => (int)str_replace(',', '', $rank[$j]),
                    'pp' => (float)str_replace(',', '', $pp[$j]),
                    'pp_weight' => (float)str_replace(',', '', $pp_weight[$j]),
                    $tmp => $accuracy[$j],
                    'time' => $time[$j],
                ]);
            }
            return str_replace('&quot;', '\\"', json_encode($list, JSON_UNESCAPED_UNICODE));
        }
    }
}
