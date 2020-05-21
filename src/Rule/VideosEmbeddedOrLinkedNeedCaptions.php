<?php

namespace CidiLabs\PhpAlly\Rule;

use DOMElement;
use CidiLabs\PhpAlly;

/**
*	Links to YouTube videos must have a caption
*/
class VideoEmbeddedOrLinkedNeedCaptions extends BaseRule
{
    public static $severity = self::SEVERITY_ERROR;
    
    public function id()
    {
        return self::class;
    }

    public function check()
    {
        $search_youtube = '/(youtube|youtu.be)/';
		$search_vimeo = '/(vimeo)/';

		foreach ($this->getAllElements(array('a', 'embed', 'iframe')) as $video) {
			$attr = ($video->tagName == 'a') ? 'href' : 'src';
			if ($video->hasAttribute($attr)) {
				$attr_val = $video->getAttribute($attr);
				if ( preg_match($search_youtube, $attr_val) ) {
					$service = new Youtube();
				}
				elseif ( preg_match($search_vimeo, $attr_val) ) {
					$service = new Vimeo();
				}
				if (isset($service)) {
                    $captionState = $service->captionsMissing($attr_val, $this->course_locale);
					if($captionState != 2) {
						$this->setIssue($video);
					}
				}
			}
		}
        
        return count($this->issues);
    }

    // public function getPreviewElement(DOMElement $a = null)
    // {
    //     return $a->parentNode;
    // }
}