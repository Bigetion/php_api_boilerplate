<?php

class WebScrap extends Controller
{
    public function googleTrends()
    {
        $this->crawl->set_url("https://trends.google.com/trends/trendingsearches/daily?geo=ID", true);
        $result['data'] = $this->crawl->get_data(["title", "href as link"], [[
            "condition" => ["level", "endswith", "_0_1_3"],
        ], [
            "condition" => ["tag", "=", "a"],
        ]]);

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
}
