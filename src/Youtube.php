<?php

namespace CidiLabs\PhpAlly;

use DOMElement;
use GuzzleHttp\Client;

class Youtube {

    private $regex = array(
		'@youtube\.com/embed/([^"\&\? ]+)@i',
	    '@youtube\.com/v/([^"\&\? ]+)@i',
	    '@youtube\.com/watch\?v=([^"\&\? ]+)@i',
	    '@youtube\.com/\?v=([^"\&\? ]+)@i',
	    '@youtu\.be/([^"\&\? ]+)@i',
	    '@youtu\.be/v/([^"\&\? ]+)@i',
	    '@youtu\.be/watch\?v=([^"\&\? ]+)@i',
	    '@youtu\.be/\?v=([^"\&\? ]+)@i',
        );
        
	private $search_url = 'https://www.googleapis.com/youtube/v3/captions?part=snippet&fields=items(snippet(trackKind,language))&videoId=';

    /**
	*	Checks to see if a video is missing caption information in YouTube
	*	@param string $link_url The URL to the video or video resource
	*	@return int 0 if captions are missing, 1 if video is private, 2 if captions exist or not a video
	*/
	public function captionsMissing($link_url)
	{
		$url = $this->search_url;
		$api_key = constant( 'GOOGLE_API_KEY' );
		global $logger;

		// If the API key is blank, flag the video for manual inspection
		$key_trimmed = trim($api_key);
		if( empty($key_trimmed) ){
			return 1;
		}

		if( $youtube_id = $this->isYouTubeVideo($link_url) ) {
			$url = $url.$youtube_id.'&key='.$api_key;
			$response = UdoitUtils::instance()->checkApiCache($url, $link_url);

			// If the video was pulled due to copyright violations, is unlisted, or is unavailable, the reponse header will be 404
			if( $response['code'] === 404 ) {
				return 1;
			}

			// If the daily limit has been exceeded for our API key or there was some other error
			if( $response['code'] === 403 ) {
				global $logger;
				$logger->addError('YouTube API Error: '.$response->body->error->errors[0]->message);
			}

			// Looks through the captions and checks if any were not auto-generated
			foreach ( $response['body']->items as $track ) {
				if ( $track->snippet->trackKind != 'ASR' ) {
					return 2;
				}
			}

			return 0;
		}

		return 2;
    }
    
    /**
	*	Checks to see if the provided link URL is a YouTube video. If so, it returns
	*	the video code, if not, it returns null.
	*	@param string $link_url The URL to the video or video resource
	*	@return mixed FALSE if it's not a YouTube video, or a string video ID if it is
	*/
	private function isYouTubeVideo($link_url)
	{
		$matches = null;
		foreach($this->regex as $pattern) {
			if(preg_match($pattern, trim($link_url), $matches)) {
				return $matches[1];
			}
		}
		return false;
	}
}