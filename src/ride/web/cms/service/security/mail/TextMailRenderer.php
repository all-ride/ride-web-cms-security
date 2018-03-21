<?php

namespace ride\web\cms\service\security\mail;

use \ride\web\base\service\security\mail\TextMailRenderer as BaseTextMailRenderer;

/**
 * Text mail renderer for the CMS
 */
class TextMailRenderer extends BaseTextMailRenderer {

    /**
     * Value for the URL variable
     * @var string
     */
    protected $url;

    /**
     * Sets the URL variable
     * @param string $url
     * @return null
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * Hook to process the variables before rendering the template
     * @param array $variables Variables to be passed to the template
     * @return array Process variables to be passed to the template
     */
    public function processVariables(array $variables) {
        $variables = parent::processVariables($variables);

        if ($this->url) {
            $url = $this->url;

            if (isset($variables['encryptedUsername'])) {
                $url = str_replace('%25user%25', $variables['encryptedUsername'], $url);
            }
            if (isset($variables['encryptedEmail'])) {
                $url = str_replace('%25email%25', $variables['encryptedEmail'], $url);
            }
            if (isset($variables['encryptedTime'])) {
                $url = str_replace('%25time%25', $variables['encryptedTime'], $url);
            }

            $variables['url'] = $url;
        }

        if (isset($variables['user'])) {
            $variables['username'] = $variables['user']->getUserName();
            $variables['name'] = $variables['user']->getDisplayName();
            $variables['email'] = $variables['user']->getEmail();
        }

        return $variables;
    }

}
