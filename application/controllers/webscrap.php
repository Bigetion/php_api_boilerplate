<?php

class WebScrap extends Controller
{
    public function googleTrends()
    {
        $this->crawl->set_url("https://trends.google.com/trends/trendingsearches/daily?geo=ID", true);
        $google_trends = $this->crawl->get_data(["title", "href as link"], [[
            "condition" => ["level", "endswith", "_0_2_0_1_1_3_1_1_2_1"],
        ], [
            "condition" => ["tag", "=", "a"],
        ]]);
        
        $description = $this->crawl->get_data(["title as description", "href as link"], [[
            "condition" => ["level", "endswith", "_0_1_3"],
        ], [
            "condition" => ["tag", "=", "a"],
        ]]);

        $result['data'] = array();
        foreach($google_trends as $key => $row) {
            $result['data'][] = array(
                "title" => str_replace('Explore ', '', $row['title']),
                "link" => "https://trends.google.com".$row['link'],
                "description" => $description[$key]['description'],
                "source_link" => $description[$key]['link']
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

        foreach ($description as $key => $desc) {
            $result['data'][$key]['description'] = $desc['innerHTML'];
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
