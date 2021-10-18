<?php

namespace CidiLabs\PhpAlly\Video;

use GuzzleHttp\Client;

class Youtube
{
	const YOUTUBE_FAIL = 0;
	const YOUTUBE_NO_VIDEO = 1;
	const YOUTUBE_SUCCESS = 2;

	private $regex = array(
		'@youtube\.com/embed/([^"\&\? ]+)@i',
		'@youtube\.com/v/([^"\&\? ]+)@i',
		'@youtube\.com/watch\?v=([^"\&\? ]+)@i',
		'@youtube\.com/\?v=([^"\&\? ]+)@i',
		'@youtu\.be/([^"\&\? ]+)@i',
		'@youtu\.be/v/([^"\&\? ]+)@i',
		'@youtu\.be/watch\?v=([^"\&\? ]+)@i',
		'@youtu\.be/\?v=([^"\&\? ]+)@i',
		'@youtube-nocookie\.com/embed/([^"\&\? ]+)@i',
	);

	private $search_url = 'https://www.googleapis.com/youtube/v3/captions?part=snippet&fields=items(snippet(trackKind,language))&videoId=';
	private $client;
	private $language;

	public function __construct(Client $client, $language = 'en', $api_key)
	{
		$this->client = $client;
		$this->language = $language;
		$this->api_key = $api_key;
	}

	/**
	 *	Checks to see if a video is missing caption information in YouTube
	 *	@param string $link_url The URL to the video or video resource
	 *	@return int 0 if captions are missing, 1 if video is private, 2 if captions exist or not a video
	 */
	public function captionsMissing($captionData)
	{
		if ($captionData->getStatusCode() >= 400) {
			return self::YOUTUBE_NO_VIDEO;
		}

		$items = json_decode($captionData->getBody())->items;

		return !(empty($items)) ? self::YOUTUBE_SUCCESS : self::YOUTUBE_FAIL;
	}

	public function captionsAutoGenerated($captionData)
	{
		if ($captionData->getStatusCode() >= 400) {
			return self::YOUTUBE_NO_VIDEO;
		}

		$items = json_decode($captionData->getBody())->items;

		// Looks through the captions and checks if any were not auto-generated
		foreach ($items as $track) {
			if (strtolower($track->snippet->trackKind) != 'asr') {
				return self::YOUTUBE_SUCCESS;
			}
		}

		return empty($items) ? self::YOUTUBE_SUCCESS : self::YOUTUBE_FAIL;
	}

	/**
	 *	Checks to see if a video is missing caption information in YouTube
	 *	@param string $link_url The URL to the video or video resource
	 *	@return int 0 if captions are manual and wrong language, 1 if video is private, 2 if captions are auto-generated or manually generated and correct language
	 */
	public function captionsLanguage($captionData)
	{
		$foundManual = false;
		// If for whatever reason course_locale is blank, set it to English
		$course_locale = $this->language;
		if ($course_locale === '' || is_null($course_locale)) {
			$course_locale = 'en';
		}

		// If the video was pulled due to copyright violations, is unlisted, or is unavailable, the reponse header will be 404
		if ($captionData->getStatusCode() >= 400) {
			return self::YOUTUBE_NO_VIDEO;
		}

		$items = json_decode($captionData->getBody())->items;

		// Looks through the captions and checks if they are of the correct language
		foreach ($items as $track) {
			$trackKind = strtolower($track->snippet->trackKind);

			//If the track was manually generated, set the flag to true
			if ($trackKind != 'asr') {
				$foundManual = true;
			}

			if (substr($track->snippet->language, 0, 2) == $course_locale && $trackKind != 'asr') {
				return self::YOUTUBE_SUCCESS;
			}
		}

		//If we found any manual captions and have not returned, then none are the correct language
		if ($foundManual === true) {
			return self::YOUTUBE_FAIL;
		}

		return self::YOUTUBE_SUCCESS;
	}

	/**
	 *	Checks to see if the provided link URL is a YouTube video. If so, it returns
	 *	the video code, if not, it returns null.
	 *	@param string $link_url The URL to the video or video resource
	 *	@return mixed FALSE if it's not a YouTube video, or a string video ID if it is
	 */
	function isYouTubeVideo($link_url)
	{
		$matches = null;
		foreach ($this->regex as $pattern) {
			if (preg_match($pattern, trim($link_url), $matches)) {
				return $matches[1];
			}
		}
		return false;
	}

	function getVideoData($link_url)
	{
		$key_trimmed = trim($this->api_key);

		if ($youtube_id = $this->isYouTubeVideo($link_url) && !empty($key_trimmed)) {
			$url = $this->search_url . $youtube_id . '&key=' . $this->api_key;
			$response = $this->client->request('GET', $url);

			return $response;
		}

		return null;
	}
}
