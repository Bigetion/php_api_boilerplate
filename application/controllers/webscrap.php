<?php

class WebScrap extends Controller
{
    public function googleTrends()
    {
        $this->crawl->set_document_type('xml')->set_url("https://trends.google.com/trends/trendingsearches/daily/rss?geo=ID");

        $data['items'] = $this->crawl->get_data([], [[
            "condition" => ["tag", "=", "item"],
        ]]);
        $result['data'] = array();
        foreach ($data['items'] as $key => $row) {
            $link = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "link"],
            ]]);
            $title = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "title"],
            ]]);
            $thumbnail = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "ht:picture"],
            ]]);
            $date = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "pubDate"],
            ]]);
            $traffic = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "ht:approx_traffic"],
            ]]);
            $news_item = $this->jsonq->search($row["children"], [], [[
                "condition" => ["tag", "=", "ht:news_item"],
            ]]);
            $news_item_description = $this->jsonq->search($news_item[0]["children"], [], [[
                "condition" => ["tag", "=", "ht:news_item_snippet"],
            ]]);
            $news_item_url = $this->jsonq->search($news_item[0]["children"], [], [[
                "condition" => ["tag", "=", "ht:news_item_url"],
            ]]);
            $tmp_date = explode(' ', $date[0]['value']);
            $result['data'][] = array(
                "link" => $link[0]['value'],
                "title" => $title[0]['value'],
                "thumbnail" => $thumbnail[0]['value'],
                "date" => $tmp_date[1]." ".$tmp_date[2]." ".$tmp_date[3],
                "traffic" => $traffic[0]['value'],
                "description" => strip_tags($news_item_description[0]["html"]),
                "source_link" => $news_item_url[0]["html"]
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

    public function liputan6()
    {
        $category_list = array("bisnis", "bola", "showbiz", "health", "lifestyle", "tekno", "otomotif", "tv");

        $url = "http://www.liputan6.com/feed/rss";

        if (isset($_GET['category'])) {
            $category = $_GET['category'];
            if (in_array($_GET['category'], $category_list)) {
                $url = "https://www.liputan6.com/$category/feed/rss";
            }
        }
        $this->crawl->set_document_type('xml')->set_url($url);

        $data['items'] = $this->crawl->get_data([], [[
            "condition" => ["tag", "=", "item"],
        ]]);
        $result['data'] = array();
        foreach ($data['items'] as $key => $row) {
            $link = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "link"],
            ]]);
            $title = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "title"],
            ]]);
            $thumbnail = $this->jsonq->search($row["children"], ["url as value"], [[
                "condition" => ["tag", "=", "enclosure"],
            ]]);
            $date = $this->jsonq->search($row["children"], ["innerHTML as value"], [[
                "condition" => ["tag", "=", "pubDate"],
            ]]);
            $tmp_date = explode(' ', $date[0]['value']);
            $result['data'][] = array(
                "link" => explode('?', $link[0]['value'])[0],
                "title" => $title[0]['value'],
                "thumbnail" => $thumbnail[0]['value'],
                "date" => $tmp_date[1]." ".$tmp_date[2]." ".$tmp_date[3],
            );
        }
        $this->render->json($result);
    }
}
