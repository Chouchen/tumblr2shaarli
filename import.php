<?php

function mylog($message) {
    echo $message.'<br>'."\n";
    file_put_contents(sprintf('%s/import.log', dirname(__FILE__)), $message."\n", FILE_APPEND);
}

mylog('start');

// Pulling config from ini file
$ini_file = sprintf('%s/tumblr.ini', dirname(__FILE__));
if (!is_readable($ini_file)) {
    die('You must have a tumblr.ini file');
}
$config = parse_ini_file($ini_file);
$tumblr_blog = $config['tumblr'];
$api_key = $config['api_key'];
$private = $config['private'];
$shaarli_dir = sprintf('%s/%s', dirname(__FILE__), $config['shaarli_dir']);

// Loading every needed class
require 'vendor/autoload.php';
// Shaarli library
require_once sprintf('%sapplication/ApplicationUtils.php', $shaarli_dir);
require_once sprintf('%sapplication/Cache.php', $shaarli_dir);
require_once sprintf('%sapplication/CachedPage.php', $shaarli_dir);
require_once sprintf('%sapplication/config/ConfigManager.php', $shaarli_dir);
require_once sprintf('%sapplication/config/ConfigPlugin.php', $shaarli_dir);
require_once sprintf('%sapplication/FeedBuilder.php', $shaarli_dir);
require_once sprintf('%sapplication/FileUtils.php', $shaarli_dir);
require_once sprintf('%sapplication/HttpUtils.php', $shaarli_dir);
require_once sprintf('%sapplication/Languages.php', $shaarli_dir);
require_once sprintf('%sapplication/LinkDB.php', $shaarli_dir);
require_once sprintf('%sapplication/LinkFilter.php', $shaarli_dir);
require_once sprintf('%sapplication/LinkUtils.php', $shaarli_dir);
require_once sprintf('%sapplication/NetscapeBookmarkUtils.php', $shaarli_dir);
require_once sprintf('%sapplication/PageBuilder.php', $shaarli_dir);
require_once sprintf('%sapplication/TimeZone.php', $shaarli_dir);
require_once sprintf('%sapplication/Url.php', $shaarli_dir);
require_once sprintf('%sapplication/Utils.php', $shaarli_dir);
require_once sprintf('%sapplication/PluginManager.php', $shaarli_dir);
require_once sprintf('%sapplication/Router.php', $shaarli_dir);
require_once sprintf('%sapplication/Updater.php', $shaarli_dir);

// Initialize variables 
// DO NOT TOUCH
$offset = 0;
$per_page = 20;
$API_post_url = sprintf('https://api.tumblr.com/v2/blog/%s/posts?api_key=%s&limit=1&offset=%u', $tumblr_blog, $api_key, 0);
$converter = new \League\HTMLToMarkdown\HtmlConverter();
$importCount = 0;
$alreadyCount = 0;
$conf = new ConfigManager();
$pagecache = sprintf('%s/pagecache', $shaarli_dir);
$linkDb = new LinkDB(
    sprintf('%s/data/datastore.php', $shaarli_dir),
    true,
    false,
    '',
    true
);
mylog('Backup '.$tumblr_blog);

// First request to determine the number of tumblr post to save in Shaarli
$json_response = file_get_contents($API_post_url);
$json_response = json_decode($json_response, true);
mylog('First request result : '.var_export($json_response, true));

$total_count = $json_response['response']['total_posts'];
mylog('Found '.$total_count.' entries on Tumblr');
$loop = ceil($total_count / $per_page);
mylog('We are going to do '.$loop.' loops');

// Tumblr API can fetch 20 posts each time, so we need to paginate.
for( $i = 0; $i < $loop; $i++) {

	mylog('--- LOOP '.($i+1).' ---');
    $API_post_url = sprintf('https://api.tumblr.com/v2/blog/%s/posts?api_key=%s&offset=%u', $tumblr_blog, $api_key, ($per_page*$i));
    $json_response = file_get_contents($API_post_url);
    $json_response = json_decode($json_response, true);

    if (!isset($json_response['response'])) {
        die(sprintf('wrong reponse %s', var_export($json_response,true)));
    }
    foreach ($json_response['response']['posts'] as $post) {
        $newLink = [];
        switch($post['type']) {
            case 'text':
                $newLink = [
                    'title'         => $post['title'],
                    'url'           => $post['post_url'],
                    'description'   => $post['body'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_text'])
                ];
                break;
            case 'photo':
                $big_pic = $post['photos'][0]['alt_sizes'][0];
                $newLink = [
                    'title'         => $post['summary'],
                    'url'           => $post['post_url'],
                    'description'   => sprintf('<img src="%s" alt="tumblr" /><p>%s</p>',$big_pic['url'], $post['caption']),
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_photo'])
                ];
                break;
            case 'quote':
            	$url = empty($post['source_url']) ? $post['post_url'] : $post['source_url'];
                $newLink = [
                    'title'         => $post['text'],
                    'url'           => $url,
                    'description'   => $post['source'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_quote'])
                ];
                break;
            case 'link':
                $newLink = [
                    'title'         => $post['title'],
                    'url'           => $post['url'],
                    'description'   => $post['description'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_link'])
                ];
                break;
            case 'chat':
                $newLink = [
                    'title'         => $post['title'],
                    'url'           => $post['post_url'],
                    'description'   => $post['body'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_chat'])
                ];
                break;
            case 'audio':
            	$url = empty($post['source_url']) ? $post['post_url'] : $post['source_url'];
                $newLink = [
                    'title'         => $post['source_title'],
                    'url'           => $url,
                    'description'   => $post['caption'].$post['player'][0]['embed_code'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_audio'])
                ];
                break;
            case 'video':
	            $url = empty($post['source_url']) ? $post['post_url'] : $post['source_url'];
                $newLink = [
                    'title'         => $post['source_title'],
                    'url'           => $url,
                    'description'   => $post['caption'].$post['player'][0]['embed_code'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_video'])
                ];
                break;
            case 'answer':
                $newLink = [
                    'title'         => $post['question'],
                    'url'           => $post['post_url'],
                    'description'   => $post['answer'],
                    'private'       => $private,
                    'linkdate'      => $post['date'],
                    'tags'          => array_merge($post['tags'], ['tumblr', 'tumblr_answer'])
                ];
                break;
            default:
            	mylog('No URL found for '.var_export($post,true));
        }
        $existingLink = $linkDb->getLinkFromUrl($newLink['url']);

		// If the link already exists, we don't do anything (we don't want to break anything)
        if ($existingLink !== false) {
        	mylog($newLink['url'].' already exists.');
        	$alreadyCount++;
            continue;
        }
        
        // no HTML in title
        $newLink['title'] = strip_tags($newLink['title']);
        // description in markdown
        $newLink['description'] = $converter->convert($newLink['description']);
        // tags are string separated with a space
        $newLink['tags'] = implode(' ', $newLink['tags']);

        // Add a new link
        $newLinkDate = DateTime::createFromFormat('Y-m-d H:i:s T', $newLink['linkdate']);
        while (!empty($linkDb[$newLinkDate->format(LinkDB::LINK_DATE_FORMAT)])) {
            // Ensure the date/time is not already used
            // - this hack is necessary as the date/time acts as a primary key
            // - apply 1 second increments until an unused index is found
            // See https://github.com/shaarli/Shaarli/issues/351
            $newLinkDate->add(new DateInterval('PT1S'));
        }
        $linkDbDate = $newLinkDate->format(LinkDB::LINK_DATE_FORMAT);
        $newLink['linkdate'] = $linkDbDate;
        $linkDb[$linkDbDate] = $newLink;
        mylog($newLink['url']. ' added!');
        $importCount++;
    }
}

// Finished
mylog('On '.$total_count.', added '.$importCount.', ignored '.$alreadyCount.' and '.($total_count-$importCount-$alreadyCount).' errors.');
// Saving
$linkDb->savedb($pagecache);
