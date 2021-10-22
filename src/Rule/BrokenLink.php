<?php

namespace CidiLabs\PhpAlly\Rule;

use DOMElement;

/**
*  Links that are broken need to be removed or manually updated.
*  Based on UDOIT 2.8.X https://github.com/ucfopen/UDOIT/blob/classic/lib/Udoit.php
*  contributions by Emily Sachs
*/
class BrokenLink extends BaseRule
{

	public function id()
	{
		return self::class;
	}

	private function linkCheck($links) {
		$curls = array();
		$mcurl = curl_multi_init();
		foreach (array_keys($links) as $i => $link) {
			$curls[$i] = curl_init();
			curl_setopt($curls[$i], CURLOPT_URL, $link);
			curl_setopt($curls[$i], CURLOPT_HEADER, true);
			curl_setopt($curls[$i], CURLOPT_NOBODY, true);
			curl_setopt($curls[$i], CURLOPT_REFERER, true);
			curl_setopt($curls[$i], CURLOPT_TIMEOUT, 2);
			curl_setopt($curls[$i], CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($mcurl, $curls[$i]);
		}
		$running = null;
		do {
			curl_multi_exec($mcurl, $running);
		} while ($running > 0);
		foreach (array_keys($links) as $i => $link) {
			$status = curl_getinfo($curls[$i], CURLINFO_RESPONSE_CODE);
			// If the status is greater than or equal to 400 the link is broken.
			if (400 <= $status) {
				$this->setIssue($links[$link]);
			}
			curl_multi_remove_handle($mcurl, $curls[$i]);
		}
		curl_multi_close($mcurl);
	}

	public function check()
	{
		$links = array();
		foreach ($this->getAllElements('a') as $a) {
			$href = $a->getAttribute('href');
			if ($href) {
				$links[$href] = $a;
			}
		}
		$this->linkCheck($links);

		return count($this->issues);
	}
}
