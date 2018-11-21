<?php

class WebScrap extends Controller
{
    public function googleTrends()
    {
        $this->crawl->set_url("https://trends.google.com/trends/trendingsearches/daily/rss?geo=ID");
        
        $items = $this->crawl->get_data([], [[
            "condition" => ["tag", "=", "item"],
        ]]);

        $title = $this->crawl->get_data([], [[
            "condition" => ["depth", "=", 6],
        ],[
            "condition" => ["level", "startswith", "0_0_0_0_"],
        ],[
            "condition" => ["level", "endswith", "_0"],
        ]]);

        $picture = $this->crawl->get_data([], [[
            "condition" => ["depth", "=", 6],
        ],[
            "condition" => ["level", "startswith", "0_0_0_0_"],
        ],[
            "condition" => ["level", "endswith", "_6"],
        ]]);

        $description = $this->crawl->get_data([], [[
            "condition" => ["depth", "=", 7],
        ],[
            "condition" => ["level", "startswith", "0_0_0_0_"],
        ],[
            "condition" => ["level", "endswith", "_8_0"],
        ]]);

        $source_link = $this->crawl->get_data([], [[
            "condition" => ["depth", "=", 7],
        ],[
            "condition" => ["level", "startswith", "0_0_0_0_"],
        ],[
            "condition" => ["level", "endswith", "_8_2"],
        ]]);
        
        
        $result['data'] = array();
        foreach($items as $key => $row) {
            $result['data'][] = array(
                "title" => $title[$key]['html'],
                "link" => "https://trends.google.com/trends/explore?q=".$title[$key]['html']."&date=now%207-d&geo=ID",
                "description" => strip_tags($description[$key]['html']),
                "source_link" => $source_link[$key]['html'],
                "picture" => $picture[$key]['html'],
            );
        }

        $this->render->json($result);
    }

    public function youtubeTrends()
    {
        $this->crawl->set_url("https://www.youtube.com/feed/trending?gl=ID&hl=id", true);
        $result['data'] = $this->crawl->get_data(["title", "href as link"], [[
            "condition" => ["class", "=", "yt-simple-endpoint style-scope ytd-video-renderer"],
        ]]);

        $this->render->json($result);
    }

    public function projectId()
    {
        $page = 1;

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }

        $this->crawl->set_url("https://projects.co.id/public/browse_projects/listing/6_website-development?page=$page");
        $result['data'] = $this->crawl->get_data(["html as title", "href as link"], [[
            "condition" => ["level", "endswith", "_3_1_0"],
        ], [
            "condition" => ["tag", "=", "a"],
        ], [
            "condition" => ["href", "contains", "https://projects.co.id/public/browse_projects/view/"],
        ]]);

        $description = $this->crawl->get_data([], [[
            "condition" => ["level", "endswith", "_3_2"],
        ], [
            "condition" => ["tag", "=", "p"],
        ]]);

        $info = $this->crawl->get_data([], [[
            "condition" => ["class", "~", "col-md-12 well img-rounded"],
        ]]);

        foreach ($description as $key => $desc) {
            $result['data'][$key]['description'] = preg_replace('/\xc2\xa0/', ' ', $desc['innerHTML']);
            $result['data'][$key]['info'] = $info[$key]['innerHTML'];
        }

        $pages = $this->crawl->get_data([], [[
            "condition" => ["class", "=", "ajax-url"],
        ]]);

        $result['totalPage'] = 0;

        if (count($pages) > 0) {
            $result['totalPage'] = $pages[count($pages) - 1]['paramval'];
        }

        $this->render->json($result);
    }
}
