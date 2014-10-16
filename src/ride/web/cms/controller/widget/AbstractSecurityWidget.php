<?php

namespace ride\web\cms\controller\widget;

use ride\library\mail\transport\Transport;
use ride\library\security\model\User;

/**
 * Abstract widget of the security module
 */
abstract class AbstractSecurityWidget extends AbstractWidget {

    /**
     * Name of the subject property
     * @var string
     */
    const PROPERTY_SUBJECT = 'subject';

    /**
     * Name of the message property
     * @var string
     */
    const PROPERTY_MESSAGE = 'message';

    /**
     * Sends a mail to the provided user
     * @param ride\library\mail\transport\Transport $transport
     * @param ride\library\security\model\User $user The user
     * @param string $url The URL to be parsed in the message
     * @return null
     */
    protected function sendMail(Transport $transport, User $user, $url = null) {
    	$subject = $this->getSubject();
    	$email = $user->getEmail();
        $message = $this->getMessage();

        $message = str_replace('[[username]]', $user->getUserName(), $message);
    	$message = str_replace('[[name]]', $user->getDisplayName(), $message);
    	$message = str_replace('[[email]]', $email, $message);

    	if ($url) {
    	   $message = str_replace('[[url]]', $url, $message);
    	}

    	$mail = $transport->createMessage();
    	$mail->setTo($email);
    	$mail->setSubject($subject);
    	$mail->setMessage($message);

    	$transport->send($mail);
    }

    /**
     * Generates a secure key for the provided user
     * @param zibo\library\security\model\User $user The user to get the key of
     * @return string The secure key for the provided user
     */
    protected function getUserKey(User $user) {
    	$key = $user->getUserId();
    	$key .= '-' . $user->getUserName();
    	$key .= '-' . $user->getUserEmail();

    	return md5($key);
    }

    /**
     * Redirects the response to the login page
     * @param string $default Fall back URL if there is no node with the login widget
     * @return null
     */
    protected function setRedirectToLogin($default = null) {
    	if (!$default) {
    		$default = $this->request->getBasePath();
    	}

    	$nodeModel = $this->zibo->getDependency('joppa\\model\\node\\NodeModel');

        $loginNode = $nodeModel->getNodesForWidget('login', 1);
        if ($loginNode) {
            $this->response->setRedirect($this->request->getBaseScript() . '/' . $loginNode->getRoute($this->locale));
        } else {
            $this->response->setRedirect($default);
        }
    }

    /**
     * Gets the subject of the mail from the properties
     * @return string The subject of the mail
     */
    protected function getSubject() {
    	return $this->properties->getWidgetProperty(self::PROPERTY_SUBJECT . '.' . $this->locale);
    }

    /**
     * Sets the subject of the mail to the properties
     * @param string $subject The subject of the mail
     * @return null
     */
    protected function setSubject($subject) {
    	$this->properties->setWidgetProperty(self::PROPERTY_SUBJECT. '.' . $this->locale, $subject);
    }

    /**
     * Gets the message of the mail from the properties
     * @return string Message of the mail
     */
    protected function getMessage() {
    	return $this->properties->getWidgetProperty(self::PROPERTY_MESSAGE. '.' . $this->locale);
    }

    /**
     * Sets the message of the mail to the properties
     * @param string $message Message of the mail
     * @return null
     */
    protected function setMessage($message) {
    	$this->properties->setWidgetProperty(self::PROPERTY_MESSAGE. '.' . $this->locale, $message);
    }

}
