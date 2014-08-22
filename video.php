<?php

/**
 * Get Vimeo Thumbnail
 *
 * @param  string $id video id
 *
 * @return string     url of thumbnail
 */
function getVimeoThumb($id) {

    try {

        $trying = get_headers('http://vimeo.com/api/v2/video/' . (int) $id . '.xml');

        if (substr($trying[0], 9, 1) !== '4') {

            $vimeo = simplexml_load_file('http://vimeo.com/api/v2/video/' . (int) $id . '.xml');

            $vimeo = $vimeo->video->thumbnail_large;

        } else {
            $vimeo = '';
        }

    } catch (Exception $e) {

        $vimeo = '';

    }

    return nvl((string) $vimeo, '');

}

/**
 * Get YouTube Thumbnail (Attempt for highest quality)
 *
 * @param  string $id video id
 *
 * @return string     url of thumbnail
 */
function getYouTubeThumb($id) {

    try {

        $trying = get_headers("http://gdata.youtube.com/feeds/api/videos/".$id."?v=2&alt=json");

        if (substr($trying[0], 9, 1) !== '4') {

            $images = json_decode(file_get_contents("http://gdata.youtube.com/feeds/api/videos/".$id."?v=2&alt=json"), true);
            $images = $images['entry']['media$group']['media$thumbnail'];
            $image  = $images[count($images)-4]['url'];

            $maxurl = "http://i.ytimg.com/vi/".$id."/maxresdefault.jpg";
            $max    = get_headers($maxurl);

            if (substr($max[0], 9, 3) !== '404') {
                $image = $maxurl;
            }

        } else {
            $image = '';
        }

    } catch (Exception $e) {

        $image = '';

    }

    return $image;

}

/**
 * Gets thumbnail uri of video
 *
 * @param  string $url URL of video
 *
 * @return string      uri of image
 */
function getVideoThumb($url) {

    $type = '';

    if (strpos($url, 'vimeo') !== FALSE) {
        $type = 'vimeo';
    }

    if (strpos($url, 'youtube') !== FALSE) {
        $type = 'youtube';
    }

    $id = getVideoId($url);

    switch ($type) {
        case 'vimeo':
            return getVimeoThumb(getVideoId($url));
            break;

        case 'youtube':
            return getYouTubeThumb(getVideoId($url));
            break;

        default:
            return '';
            break;
    }

}

/**
 * Returns title of a vimeo or youtube video
 *
 * @param  string $type youtube or vimeo
 * @param  string $id   video id
 *
 * @return string       video title
 */
function getVideoTitle($url) {

    $type = '';

    if (strpos($url, 'vimeo') !== FALSE) {
        $type = 'vimeo';
    }

    if (strpos($url, 'youtube') !== FALSE) {
        $type = 'youtube';
    }

    if (!empty($type)) {

        $id = getVideoId($url);

        switch ($type) {
            case 'vimeo':
                $vimeo = simplexml_load_file('http://vimeo.com/api/v2/video/' . $id . '.xml');
                return (string) $vimeo->video->title;
                break;

            case 'youtube':
                $youtube = simplexml_load_file('http://gdata.youtube.com/feeds/api/videos/' . $id . '?v=2');
                return (string) $youtube->title;
                break;

            default:
                return '';
                break;
        }

    }

    return '';

}

/**
 * Get the ID of a video from the URL
 *
 * @param  string $url URL of video
 *
 * @return string      ID of video
 */
function getVideoId($url) {

    $type = '';

    if (strpos($url, 'vimeo') !== FALSE) {
        $type = 'vimeo';
    }

    if (strpos($url, 'youtube') !== FALSE) {
        $type = 'youtube';
    }

    switch ($type) {
        case 'vimeo':
            return (int) substr(parse_url($url, PHP_URL_PATH), 1);
            break;

        case 'youtube':
            return parse_yturl($url);
            break;

        default:
            # code...
            break;
    }

}

/**
 *  Check if input string is a valid YouTube URL
 *  and try to extract the YouTube Video ID from it.
 *  @author  Stephan Schmitz <eyecatchup@gmail.com>
 *  @param   $url   string   The string that shall be checked.
 *  @return  mixed           Returns YouTube Video ID, or (boolean) false.
 */
function parse_yturl($url)
{
    $pattern = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
    preg_match($pattern, $url, $matches);
    return (isset($matches[1])) ? $matches[1] : false;
}
