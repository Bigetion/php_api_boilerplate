<?php

class WebScrap extends Main
{
    public function fromAPI()
    {
        $post_data = $this->render->json_post();
        $result = array();

        if (isset($post_data['url'])) {
            $url = $post_data['url'];
            $this->crawl->set_url($url);
            $p = $this->crawl->get_data([], [[
                "condition" => ["tag", "=", "p"],
            ]]);
            $result = json_decode($p[0]['innerHTML']);
        }
        $this->render->json($result);
    }

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
                "date" => $tmp_date[1] . " " . $tmp_date[2] . " " . $tmp_date[3],
                "traffic" => $traffic[0]['value'],
                "description" => strip_tags($news_item_description[0]["html"]),
                "source_link" => $news_item_url[0]["html"],
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
                "date" => $tmp_date[1] . " " . $tmp_date[2] . " " . $tmp_date[3],
            );
        }
        $this->render->json($result);
    }

    public function googleTranslate()
    {
        $post_data = $this->render->json_post();
        $result = array();

        if (isset($post_data['q'])) {
            $q = rawurlencode(str_replace(array("%"), "", $post_data['q']));

            $from = 'id';
            $to = 'en';

            if (isset($post_data['from']) && isset($post_data['to'])) {
                $from = $post_data['from'];
                $to = $post_data['to'];
            }

            $this->crawl->set_url("https://translate.google.com/#view=home&op=translate&sl=$from&tl=$to&text=$q", true);
            $data = $this->crawl->get_data([], [[
                "condition" => ["class", "=", "tlid-translation translation"],
            ]]);
            if (count($data) > 0) {
                $result['value'] = $data[0]['innerHTML'];
            }
        }

        $this->render->json($result);
    }

    public function googleImages()
    {
        $result = array('data' => array());

        if (isset($_GET['q'])) {
            $q = $_GET['q'];
            $size = '';
            if (isset($_GET['size'])) {
                $size = '&tbs=isz:' . $_GET['size'];
            }
            $slug = strtolower(trim(preg_replace('/[\s-]+/', '+', preg_replace('/[^A-Za-z0-9-]+/', '+', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $q))))), '+'));
            $this->crawl->set_url("https://www.google.com/search?q=$slug&tbm=isch$size&oq=$slug", true);
            $items = $this->crawl->get_data([], [[
                "condition" => ["href", "~", "/imgres"],
            ]]);

            $result = array('data' => array());
            foreach ($items as $row) {
                $result['data'][] = array(
                    'src' => $row['children'][1]['src'] ? $row['children'][1]['src'] : $row['children'][1]['data-src'],
                    'link' => 'https://www.google.com' . $row['href'],
                );
            }
        }
        $this->render->json($result);
    }

    public function googleSearch()
    {
        $result = array('data' => array());

        if (isset($_GET['q'])) {
            $q = $_GET['q'];

            $start = '';
            if (isset($_GET['start'])) {
                $start = '&start=' . $_GET['start'];
            }

            $slug = strtolower(trim(preg_replace('/[\s-]+/', '+', preg_replace('/[^A-Za-z0-9-]+/', '+', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $q))))), '+'));
            $this->crawl->set_url("https://www.google.com/search?q=$slug&safe=strict$start&oq=$slug&num=100", true);

            $data['items'] = $this->crawl->get_data([], [[
                "condition" => ["class", "=", "r"],
            ]]);

            $result['data'] = array();
            foreach ($data['items'] as $key => $row) {
                if ($row['children'][0]['href'] && $row['children'][0]['children'][0]['html'] && substr($row['children'][0]['href'], 0, 8) !== '/search?') {
                    $result['data'][] = array(
                        'title' => $row['children'][0]['children'][0]['html'],
                        'link' => $row['children'][0]['href'],
                    );
                }
            }
        }
        $this->render->json($result);
    }

    public function youtube()
    {
        $result = array('data' => array(), 'q' => '');
        $url = 'https://ytinstant.com';
        if (isset($_GET['q'])) {
            $q = rawurlencode($_GET['q']);
            $url = "https://ytinstant.com/#$q";
        }
        $this->crawl->set_url($url, true);

        $data['q'] = $this->crawl->get_data([], [[
            "condition" => ["id", "=", "searchTermKeyword"],
        ]]);

        $result['q'] = $data['q'][0]['html'];

        $data['items'] = $this->crawl->get_data([], [[
            "condition" => ["id", "=", "playlist"],
        ]]);

        foreach ($data['items'][0]['children'] as $key => $row) {
            $result['data'][] = array(
                'title' => $row['children'][1]['html'],
                'thumbnail' => $row['children'][0]['src'],
                'id' => explode("/", str_replace("https://i.ytimg.com/vi/", "", $row['children'][0]['src']))[0],
                'link' => "https://www.youtube.com/v/" . explode("/", str_replace("https://i.ytimg.com/vi/", "", $row['children'][0]['src']))[0] . "?fs=1&hl=en_US",
            );
        }

        $this->render->json($result);
    }

    public function youtubeMP3()
    {
        $result = array('file' => '');

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $this->crawl->set_url("http://michaelbelgium.me/ytconverter/convert.php?youtubelink=https://www.youtube.com/watch?v=$id");
            $p = $this->crawl->get_data([], [[
                "condition" => ["tag", "=", "p"],
            ]]);
            $result = json_decode($p[0]['innerHTML']);
        }
        $this->render->json($result);
    }

    public function bukalapak()
    {
        $result['data'] = array();
        $result['total_pages'] = 0;

        if (isset($_GET['q'])) {
            $q = $_GET['q'];

            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }

            $slug = strtolower(trim(preg_replace('/[\s-]+/', '+', preg_replace('/[^A-Za-z0-9-]+/', '+', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $q))))), '+'));
            $this->crawl->set_url("https://www.bukalapak.com/products/s?from=omnisearch&page=$page&search%5Bhashtag%5D=&search%5Bkeywords%5D=$slug&search_source=omnisearch_organic&source=navbar&utf8=%E2%9C%93");

            $level = $this->crawl->get_data([], [[
                "condition" => ["class", "=", "basic-products basic-products--grid"],
            ]]);

            $level = $level[0]['level'];

            $products = $this->crawl->get_data(["data-name", "data-url"], [[
                "condition" => ["level", "startswith", $level . "_1_"],
            ], [
                "condition" => ["level", "endswith", "_1_1"],
            ], [
                "condition" => ["tag", "=", "article"],
            ]]);

            $images = $this->crawl->get_data([], [[
                "condition" => ["level", "startswith", $level . "_"],
            ], [
                "condition" => ["tag", "=", "img"],
            ]]);

            $prices = $this->crawl->get_data([], [[
                "condition" => ["level", "startswith", $level . "_"],
            ], [
                "condition" => ["class", "=", "product-price"],
            ]]);

            $total_pages = $this->crawl->get_data([], [[
                "condition" => ["class", "=", "last-page"],
            ]]);

            foreach ($products as $key => $row) {
                $result['data'][] = array(
                    "title" => $row['data-name'],
                    "url" => "https://bukalapak.com" . $row['data-url'],
                    "image" => $images[$key]['data-src'],
                    "price" => $prices[$key]['data-reduced-price'],
                );
            }
            $result['total_pages'] = (int) $total_pages[0]['html'];
        }

        $this->render->json($result);
    }

    public function shopee()
    {
        $result['data'] = array();
        $result['total_pages'] = 100;

        if (isset($_GET['q'])) {
            $q = $_GET['q'];

            $page = 1;
            $newest = 0;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }

            $page = $page - 1;

            $slug = strtolower(trim(preg_replace('/[\s-]+/', '+', preg_replace('/[^A-Za-z0-9-]+/', '+', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $q))))), '+'));
            $this->crawl->set_url("https://shopee.co.id/search?keyword=$q&page=$page&sortBy=relevancy", true);
            $body = $this->crawl->get_data([], [[
                "condition" => ["type", "=", "application/ld+json"],
            ], [
                "condition" => ["level", "!=", "0_0_10"],
            ]]);
            foreach ($body as $row) {
                $innerJSON = json_decode($row['innerHTML'], true);
                $price = 0;
                if (isset($innerJSON['offers']['price'])) {
                    $price = $innerJSON['offers']['price'];
                } else if (isset($innerJSON['offers']['lowPrice'])) {
                    $price = $innerJSON['offers']['lowPrice'];
                }
                $result['data'][] = array(
                    "title" => $innerJSON['name'],
                    "url" => $innerJSON['url'],
                    "image" => $innerJSON['image'],
                    "price" => (int) $price,
                );
            }

        }
        $this->render->json($result);
    }

    public function tokopedia()
    {
        $result['data'] = array();
        $result['total_pages'] = 0;

        if (isset($_GET['q'])) {
            $q = $_GET['q'];

            $offset = 50;
            $page = 1;
            $start = 0;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            $start = ($page - 1) * $offset;

            $slug = strtolower(trim(preg_replace('/[\s-]+/', '+', preg_replace('/[^A-Za-z0-9-]+/', '+', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $q))))), '+'));
            $this->crawl->set_url("https://ace.tokopedia.com/search/product/v3?scheme=https&device=desktop&related=true&catalog_rows=5&source=search&ob=23&st=product&rows=$offset&start=$start&q=$slug&safe_search=false");
            $body = $this->crawl->get_data([], [[
                "condition" => ["tag", "=", "p"],
            ]]);
            $response = json_decode($body[0]['innerHTML'], true);
            $products = $response['data']['products'];
            $result['data'] = array();
            foreach ($products as $row) {
                $result['data'][] = array(
                    "title" => $row['name'],
                    "url" => $row['url'],
                    "image" => $row['image_url'],
                    "price" => $row['price_int'],
                );
            }
            $result['total_pages'] = ceil($response['header']['total_data'] / $offset);
        }

        $this->render->json($result);
    }
}
