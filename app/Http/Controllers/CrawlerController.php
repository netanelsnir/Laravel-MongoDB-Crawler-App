<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;

class CrawlerController extends Controller
{
    /**
     * Display the results of the crawler by url and depth parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'depth' => 'required|integer|min:0',
        ]);

        $url = $request->input('url');
        $depth = $request->input('depth');

        $existingUrl = Website::where('normalizedUrl', $this->normalizeUrl($url))->first();

        // If the URL was found in DB fetch URLs from DB otherwise run the crawler.
        if ($existingUrl && $existingUrl->pages) {
            $response = $this->fetchFromDB($existingUrl, $depth);
        } else {
            $response = $this->runCrawler($url, $depth);
        }

        return response()->json($response);
    }

    /**
     * Refresh the results of the crawler by url and depth parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'depth' => 'required|integer|min:0',
        ]);

        $url = $request->input('url');
        $depth = $request->input('depth');

        $existingUrl = Website::where('normalizedUrl', $this->normalizeUrl($url))->first();

        // if the URL was found in the DB with its associated pages 
        // Remove pages and refresh results by running crawler again
        // otherwise return error.
        if ($existingUrl && $existingUrl->pages) {

            $pages = $existingUrl->pages;
            $existingUrl->unset('pages');

            while (count($pages) > 0 && $depth > 0) {
                $websites = Website::whereIn('_id', $pages)->get();

                foreach ($websites as $website) {

                    if ($website->pages && count($website->pages) > 0) {
                        $pages = array_merge($pages, $website->pages);
                        $website->unset('pages');
                    }
                }
                $depth--;
            }

            $depth = $request->input('depth');
            $response = $this->runCrawler($url, $depth);
            return response()->json($response);
        }

        return response()->json([
            "errors" => ["url" => ["URL was not found."]],
            "message" => "URL was not found."
        ], 422);
    }

    /**
     * Fetch the results from DB.
     *
     * @param  App\Models\Website  $website
     * @param  integer  $depth
     * @return array[ url => string, depth => integer]
     */
    private function fetchFromDB($website, $depth)
    {
        $URLs = [];
        $pages = $website->pages ?? [];
        $level = 1;

        while (count($pages) > 0 && $depth > 0) {
            $websites = Website::whereIn('_id', $pages)->get();

            $pages = [];
            foreach ($websites as $website) {
                array_push($URLs, ['url' => $website->url, 'depth' => $level]);
                if ($website->pages && count($website->pages) > 0) {

                    $pages = array_merge($pages, $website->pages);
                }
            }
            $level++;
            $depth--;
        }

        return $URLs;
    }

    /**
     * Run Crawler.
     *
     * @param  string  $url
     * @param  integer  $depth
     * @return array[ [url => string, depth => integer]]
     */
    private function runCrawler($url, $depth)
    {
        $this->crawler($url, $depth);
        $website = Website::where('normalizedUrl', $this->normalizeUrl($url))->first();
        return $this->fetchFromDB($website, $depth);
    }

    /**
     * Recursive Crawler.
     *
     * @param  string  $url
     * @param  integer  $depth
     * @param  App\Models\Website | null  $parent
     * @param  integer  $currentDepth
     * @param  array[ parentID:string => array[urls:string]]  $relationsMap
     * @return void
     */
    private function crawler($url, $depth, $parent = null, $currentDepth = 0, $relationsMap = [])
    {
        if ($currentDepth > $depth) {

            foreach ($relationsMap as $key => $data) {
                Website::find($key)->push('pages', $data, true);
            }

            return;
        }

        $existingUrl = Website::where('normalizedUrl', $this->normalizeUrl($url))->first();

        if (!$existingUrl || ($existingUrl && !$existingUrl->pages)) {

            try {
                $response = Http::timeout(env('HTTP_TIMEOUT_SECONDS', 2))->get($url);
                if ($response->successful()) {

                    $website = $existingUrl ? $existingUrl : Website::create([
                        'url' => $url,
                        'normalizedUrl' => $this->normalizeUrl($url),
                        'depth' => $depth - $currentDepth,
                    ]);

                    if ($parent) {
                        if (!isset($relationsMap[$parent->_id])) {
                            $relationsMap[$parent->_id] = array();
                        }
                        array_push($relationsMap[$parent->_id], $website->_id);
                    }

                    $body = $response->body();
                    $urls = $this->extractUrls($body);

                    foreach ($urls as $newUrl) {
                        $this->crawler($newUrl, $depth, $website, $currentDepth + 1, $relationsMap);
                    }
                }
            } catch (ConnectionException | RequestException $ex) {
                //dd($ex);
            }
        }
    }

    /**
     * Extract Urls From HTML.
     *
     * @param  string  $html
     * @return array[string]
     */
    private function extractUrls($html)
    {
        $urls = [];

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            // Remove all illegal characters from a url
            $url = filter_var($anchor->getAttribute('href'), FILTER_SANITIZE_URL);
            // Skip invalid URLs
            if (filter_var($url, FILTER_VALIDATE_URL) === false || str_starts_with($url, 'mailto')) {
                continue;
            }
            // Add URL to the array
            $urls[] = $url;
        }

        // Remove duplicates urls and then renumbering keys
        return array_values(array_unique($urls));
    }

    /**
     * Url Normalization.
     *
     * @param  string  $url
     * @return string
     */
    private function normalizeUrl($url)
    {
        $url = rtrim($url, '/');
        $disallowed = array('https://www.', 'http://www.', 'https://', 'http://', 'www.');
        foreach ($disallowed as $d) {
            if (strpos($url, $d) === 0) {
                return str_replace($d, '', $url);
            }
        }

        return $url;
    }
}
