<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataFile = 'data.json';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(['links' => []]));
}

$data = json_decode(file_get_contents($dataFile), true);

function fetchImageUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return '';

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Image: <link rel="preload" as="image" href="[image]">
    $imageNodes = $xpath->query('//link[@rel="preload"][@as="image"]');
    if ($imageNodes->length > 0) {
        return $imageNodes->item(0)->getAttribute('href');
    }
    
    // Fallback to OG Image
    $ogImage = $xpath->query('//meta[@property="og:image"]');
    if ($ogImage->length > 0) {
        return $ogImage->item(0)->getAttribute('content');
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'add_link') {
        $image = fetchImageUrl($input['url']);
        // Fix protocol-relative URLs
        if (strpos($image, '//') === 0) {
            $image = 'https:' . $image;
        }

        $newLink = [
            'id' => uniqid(),
            'url' => $input['url'],
            'added_by' => $input['user_name'],
            'upvotes' => 0,
            'downvotes' => 0,
            'voters' => [],
            'image' => $image,
            'title' => $input['title'] ?? ''
        ];

        // Prepend to show newest first
        array_unshift($data['links'], $newLink);
    } elseif ($action === 'vote') {
        $linkId = $input['link_id'];
        $voteType = $input['vote_type']; // 'up' or 'down'
        $userName = $input['user_name'];

        foreach ($data['links'] as &$link) {
            if ($link['id'] === $linkId) {
                if (!isset($link['voters'])) {
                    $link['voters'] = [];
                }

                $voterIndex = -1;
                foreach ($link['voters'] as $index => $voter) {
                    if ($voter['name'] === $userName) {
                        $voterIndex = $index;
                        break;
                    }
                }

                if ($voterIndex === -1) {
                    // New vote
                    if ($voteType === 'up') {
                        $link['upvotes']++;
                    } elseif ($voteType === 'down') {
                        $link['downvotes']++;
                    }
                    $link['voters'][] = ['name' => $userName, 'type' => $voteType];
                } else {
                    // Existing vote - check if changing
                    $currentType = $link['voters'][$voterIndex]['type'];
                    if ($currentType !== $voteType) {
                        // Remove old vote effect
                        if ($currentType === 'up') {
                            $link['upvotes']--;
                        } elseif ($currentType === 'down') {
                            $link['downvotes']--;
                        }
                        
                        // Apply new vote effect
                        if ($voteType === 'up') {
                            $link['upvotes']++;
                        } elseif ($voteType === 'down') {
                            $link['downvotes']++;
                        }

                        // Update voter record
                        $link['voters'][$voterIndex]['type'] = $voteType;
                    }
                }
                break;
            }
        }
    } elseif ($action === 'update_link') {
         $linkId = $input['link_id'];
         foreach ($data['links'] as &$link) {
            if ($link['id'] === $linkId) {
                if (isset($input['title'])) $link['title'] = $input['title'];
                break;
            }
         }
    }

    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode($data);
    exit;
}
?>
