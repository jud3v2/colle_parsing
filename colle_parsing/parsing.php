<?php
// @workdir
$result = [
    'status' => 'ok',
    'result' => [
        'movie' => [
                // test data
            [
                'title' => 'The Shawshank Redemption',
                'release_date' => '1994-09-23',
                'summary' => 'Two imprisoned',
                'status' => 'Released',
                'duration' => '1h 42m',
                'budget' => '$25,000,000',
                'revenue' => '$28,341,469',
                'original_language' => 'en',
            ]
        ],
    ]
];

$hasParsedReleaseDate = false;

$limitKeyword = 5;

if (!function_exists('parseFile')) {
        function parseFile($file): array
        {
                if (!file_exists($file)) {
                        die("File not found\n");
                }
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                $data = [];
                foreach ($lines as $line) {
                        parseLine($line);
                }

                return $data;
        }
}

if (!function_exists('parseTitle')) {
        function parseTitle($line): string
        {
                return preg_replace_callback('/property="og:title" content="(.*)"/', function ($matches) {
                        global $result;
                        if (!empty($matches[1])) {
                                $result['result']['movie'] = [
                                    [
                                        'title' => htmlspecialchars_decode($matches[1]),
                                    ]
                                ];
                                return $matches[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseReleaseDate')) {
        function parseReleaseDate($line): string
        {
                return preg_replace_callback('/class="release_date">(.*)<\/span/', function ($m) {
                        global $result, $hasParsedReleaseDate;
                        if (!empty($m[1]) && !$hasParsedReleaseDate) {
                                if (!empty($result['result']['movie'][0]['title'])) {
                                        $result['result']['movie'][0]['releaseDate'] = str_replace(')</span', '', str_replace('(', '', $m[1]));
                                        $hasParsedReleaseDate = true;
                                }
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseSummary')) {
        function parseSummary($line): string
        {
                return preg_replace_callback('/name="description" content="(.*)"/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['summary'] = htmlspecialchars_decode($m[1]);
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseStatus')) {
        function parseStatus($line): string
        {
                return preg_replace_callback('/<p><strong><bdi>Status<\/bdi><\/strong>(.*)<\/p>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['status'] = trim(htmlspecialchars_decode($m[1]));
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseDuration')) {
        function parseDuration($line): string
        {
                return preg_replace_callback('/<p><strong><bdi>Runtime<\/bdi><\/strong>(.*)<\/p>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['duration'] = trim(htmlspecialchars_decode($m[1]));
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseBudget')) {
        function parseBudget($line): string
        {
                return preg_replace_callback('/<p><strong><bdi>Budget<\/bdi><\/strong>(.*)<\/p>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['budget'] = trim(htmlspecialchars_decode($m[1]));
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseRevenue')) {
        function parseRevenue($line): string
        {
                return preg_replace_callback('/<p><strong><bdi>Revenue<\/bdi><\/strong>(.*)<\/p>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['revenue'] = trim(htmlspecialchars_decode($m[1]));
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseOriginalLanguage')) {
        function parseOriginalLanguage($line): string
        {
                return preg_replace_callback('/<p><strong><bdi>Original Language<\/bdi><\/strong>(.*)<\/p>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['originalLanguage'] = trim(htmlspecialchars_decode($m[1]));
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, 1);
        }
}

if (!function_exists('parseGenre')) {
        function parseGenre($line): array|string|null
        {
                return preg_replace_callback('/<li><a href="https:\/\/www.themoviedb.org\/genre\/.*">(.*)<\/a><\/li>/', function ($m) {
                        global $result;
                        if (!empty($m[1])) {
                                $result['result']['movie'][0]['genre'] = array_merge($result['result']['movie'][0]['genre'] ?? [], [trim(htmlspecialchars_decode($m[1]))]);
                                return $m[1];
                        } else {
                                return '';
                        }
                }, $line, -1);
        }
}


if (!function_exists('parseKeyword')) {
        function parseKeyword($line): array|string|null
        {
                return preg_replace_callback('/<li><a href="https:\/\/www.themoviedb.org\/keyword\/.*">(.*)<\/a><\/li>/', function ($m) {
                        global $result, $limitKeyword;
                        if (!empty($m[1]) && $limitKeyword > 0) {
                                $result['result']['movie'][0]['keywords'] = array_merge($result['result']['movie'][0]['keywords'] ?? [], [trim(htmlspecialchars_decode($m[1]))]);
                                // decrease limit
                                $limitKeyword--;
                                return $m[1];
                        } else {
                                return '';
                        }
                        // limit à au moins les 5 premiers comme écrit dans le sujet
                }, $line, -1);
        }
}

if (!function_exists('parseCast')) {
        function parseCast($line): void
        {
                /*
                 * I want to do this FAST !!!
                 * "cast": [
{
"name": "Martin Freeman",
"character": "Arthur Dent"
},
{
"name": "Zooey Deschanel",
"character": "Trillian"
},
{
"name": "Sam Rockwell",
"character": "Zaphod Beeblebrox"
},
{
"name": "Yasiin Bey",
"character": "Ford Prefect"
},
{
"name": "John Malkovich",
"character": "Humma Kavula"
}
]
                 * */
                preg_replace_callback('/(<p><a href="https:\/\/www\.themoviedb\.org\/person\/.*">(.*)<\/a><\/p>|<p class="character">(.*)<\/p>)/', function ($m) {
                        global $result;
                        // the next $m have the character name
                        if (!empty($m)) {
                                $data = [
                                    'name' => $m[2] ?? '',
                                    'character' => $m[3] ?? '',
                                ];
                                $result['result']['movie'][0]['cast'] = array_merge($result['result']['movie'][0]['cast'] ?? [], [$data]);
                                return $data;
                        } else {
                                return '';
                        }
                        // limit à au moins les 5 premiers comme écrit dans le sujet
                }, $line);
        }
}


if (!function_exists('parseLine')) {
        function parseLine($line): void
        {
                parseTitle($line);
                parseReleaseDate($line);
                parseSummary($line);
                parseStatus($line);
                parseDuration($line);
                parseBudget($line);
                parseRevenue($line);
                parseOriginalLanguage($line);
                parseGenre($line);
                parseKeyword($line);
                //parseCast($line);
        }
}

if (!empty($argv[1])) {
        parseFile($argv[1]);

        if (file_exists('result.json')) {
                unlink('result.json');
        }

        file_put_contents('result.json', json_encode($result));

} else {
        die("Please provide a html file name\n");
}