<?php




header("Content-Type: application/json");



function iget($url = null)  {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return $output;
    }
    
    
    
    
    
    
    function getInitalData($html, $nodeIndex = 33)
    {
        // If our regex found the initialData then return 
        if (preg_match('/ytInitialData\s*=\s*({.+?})\s*;/i', $html, $matches)) {
            $json = $matches[1];
            return json_decode($json, true);
        }
        // Else  we will load it in dom and get through index
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $nodes = $doc->getElementsByTagName('script');
        $Var_value = $nodes[$nodeIndex]->nodeValue;
        $res = rtrim(substr($Var_value, 20, strlen($Var_value)), ";");
        $json = json_decode($res, true);
        if($json != null){
          return $json;
        }
    
        foreach ($nodes as $node) {
            // Get the node value
            $nodeValue = $node->nodeValue;
    
            // Check if the node value contains ytInitialData
            if (strpos($nodeValue, 'ytInitialData') !== false) {
             
                $res = rtrim(substr($nodeValue, 20, strlen($nodeValue)), ";");
                $json = json_decode($res, true);
                return $json;
            }
        }
    
        // Return null if ytInitialData is not found
        return null;
    }
    
    function parseSearchResult($json)
    {
        $video_page_response = $json["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"];
        $size = 0;
        if (is_array($video_page_response)) {
            $size = sizeof($video_page_response);
            // The variable is an array, you can proceed with further operations
        }
        $nextToken = $video_page_response[$size - 1]["continuationItemRenderer"]["continuationEndpoint"]["continuationCommand"]["token"];

        $videosJson = $json["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"];
        $videos = [];
        foreach (@$videosJson as $value) {

            if (isset($value["videoRenderer"])) {
                $_video = $value["videoRenderer"];
                $video['id'] = $_video["videoId"];
                $video['url'] = "https://youtube.com/watch?v=".$_video["videoId"];
                $video['title'] = $_video["title"]["runs"][0]["text"];
                $video['author'] = $_video["longBylineText"]["runs"][0]["text"];
                array_push($videos, $video);
            }            
        }
        file_put_contents("session.me", $nextToken);
        return json_encode([ 'result' => $videos ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
       
    }
    function postNext(string $nextToken, $param = 'next')
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://www.youtube.com/youtubei/v1/$param?key=AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8&prettyPrint=false",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n  \"context\": {\r\n    \"client\": {\r\n      \"hl\": \"en-GB\",\r\n      \"gl\": \"PK\",\r\n      \"deviceMake\": \"Google\",\r\n      \"deviceModel\": \"Nexus 5\",\r\n      \"visitorData\": \"CgtmZXN5X0VMZGwwSSiE1qWkBg%3D%3D\",\r\n      \"userAgent\": \"Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Mobile Safari/537.36,gzip(gfe)\",\r\n      \"clientName\": \"MWEB\",\r\n      \"clientVersion\": \"2.20230607.06.00\",\r\n      \"osName\": \"Android\",\r\n      \"osVersion\": \"6.0\",\r\n      \"playerType\": \"UNIPLAYER\",\r\n      \"screenPixelDensity\": 2,\r\n      \"platform\": \"MOBILE\",\r\n      \"clientFormFactor\": \"SMALL_FORM_FACTOR\",\r\n      \"screenDensityFloat\": 2,\r\n      \"browserName\": \"Chrome Mobile\",\r\n      \"browserVersion\": \"109.0.0.0\",\r\n      \"acceptHeader\": \"text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8\",\r\n      \"deviceExperimentId\": \"ChxOekU0T0RnMU9EazBORGt5TlRrMk1qTTFNQT09EITWpaQGGJ_qj54G\",\r\n      \"screenWidthPoints\": 564,\r\n      \"screenHeightPoints\": 962,\r\n      \"utcOffsetMinutes\": 300,\r\n      \"userInterfaceTheme\": \"USER_INTERFACE_THEME_LIGHT\",\r\n      \"memoryTotalKbytes\": \"4000000\",\r\n      \"mainAppWebInfo\": {\r\n        \"webDisplayMode\": \"WEB_DISPLAY_MODE_BROWSER\",\r\n        \"isWebNativeShareAvailable\": false\r\n      }\r\n    }\r\n  },\r\n  \"continuation\": \"$nextToken\"\r\n}",
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "Content-Type: application/json",
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Mobile Safari/537.36"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return json_decode($response);
        }
     
     function getParsedSearchResult($json)
    {
        $root = $json->onResponseReceivedCommands[0]->appendContinuationItemsAction->continuationItems;
        $videos = $root[0]->itemSectionRenderer->contents;
        $VideosSize = sizeof($videos) - 2;
        $videos_parsed = array();
        for ($i = 0; $VideosSize > $i; $i++) {
            $video = $videos[$i]->videoWithContextRenderer ?? null;
            if ($video) {
                array_push(
                $videos_parsed,
                array(
                        'id' => $video->videoId,
                        'url'=> "https://youtube.com/watch?v=".$video->videoId,
                        'title' => $video->headline->runs[0]->text ?? '',
                        'author' => $video->shortBylineText->runs[0]->text
                    ));
            }
        }
        $nextToken = $root[sizeof($root) - 1]->continuationItemRenderer->continuationEndpoint->continuationCommand->token ?? null;
        file_put_contents("session.me", $nextToken);
        return json_encode($videos_parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
  function search($query) {
  	$query = urlencode($query);
        $html = iget("https://www.youtube.com/results?search_query=$query");
        $json = getInitalData($html, 33);
        return parseSearchResult($json);
    }
    function getSearchNext($nextToken)
    {
        $json = postNext($nextToken, 'search');
        return getParsedSearchResult($json);
    }
    
    header("Content-Type: application/json");
    $i1 = search($_GET["query"]);
    $i2 = getSearchNext(file_get_contents("session.me"));
    $i3 = getSearchNext(file_get_contents("session.me"));
    $i4 = getSearchNext(file_get_contents("session.me"));
    $i5 = getSearchNext(file_get_contents("session.me"));
    $i6 = getSearchNext(file_get_contents("session.me"));
    $i7 = getSearchNext(file_get_contents("session.me"));
    $i9 = getSearchNext(file_get_contents("session.me"));
    $i0 = getSearchNext(file_get_contents("session.me"));    
    $str = "]\n}[";
    $str1 = "][";
    $result = str_replace([$str, $str1], ",", $i1.$i2.$i3.$i4.$i5.$i6.$i7.$i8.$i9.$i0)."}";
    echo $result;
#json_decode($result)->result[100]->title;
    
    
    
    
    
    
    
    
    
