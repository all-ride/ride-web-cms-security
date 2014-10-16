<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\node\NodeModel;
use ride\library\http\Response;
use ride\library\router\Route;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use ride\web\base\form\PasswordRequestComponent;
use ride\web\base\form\PasswordResetComponent;
use ride\web\base\service\security\PasswordResetService;


/**
 * Widget to reset a users password
 */
class PasswordWidget extends AbstractSecurityWidget {

    /**
     * Machine name of the widget
     * @var string
     */
    const NAME = 'password';

	/**
	 * Path to the icon of this widget
	 * @var string
	 */
	const ICON = 'img/cms/widget/password.png';

    /**
     * Path to the template to ask for a password reset
     * @var string
     */
    const TEMPLATE_RESET = 'cms/widget/security/password.reset';

    /**
     * Path to the template to ask for a new password
     * @var string
     */
    const TEMPLATE_NEW = 'cms/widget/security/password.new';

    /**
     * Instance of the password reset service
     * @var \ride\web\base\service\PasswordResetService
     */
    protected $service;

    /**
     * Constructs a new instance of the password widget
     * @param \ride\web\base\service\PasswordResetService $service
     * @return null
     */
    public function __construct(PasswordResetService $service) {
        $this->service = $service;
    }

    /**
     * Gets the templates used by this widget
     * @return array Array with the resource names of the templates
     */
    public function getTemplates() {
        return array(
            self::TEMPLATE_RESET,
            self::TEMPLATE_NEW,
        );
    }

    /**
     * Gets the additional sub routes for this widget
     * @return array|null Array with a route path as key and the action method
     * as value
     */
    public function getRoutes() {
        return array(
            new Route('/reset/%user%/%time%', array($this, 'resetAction'), 'password.reset', array('head', 'get', 'post')),
        );
    }

    /**
     * Action to ask for the user and send a mail with the password reset URL
     * @return null
     */
    public function indexAction(PasswordResetService $service) {
        $translator = $this->getTranslator();

        $data = array();

        $user = $this->getUser();
        if ($user) {
            $data['user'] = array(
                'username' => $user->getUserName(),
            );
        }

        $form = $this->createFormBuilder($data);
        $form->addRow('user', 'component', array(
            'component' => new PasswordRequestComponent(),
            'embed' => true,
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $user = $service->lookupUser($data['user']['username'], $data['user']['email']);
                if ($user) {
                    $service->requestPasswordReset($user);
                }

                $this->addSuccess('success.user.password.mail');

                $referer = $this->request->getQueryParameter('referer');
                if (!$referer) {
                    $referer = $this->request->getBaseUrl();
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form, null);
            }
        }

        $this->setTemplateView(self::TEMPLATE_RESET, array(
            'form' => $form->getView(),
        ));
    }

    /**
     * Action to reset the password
     * @param string $user Encrypted username
     * @param string $time Encrypted time
     * @return null
     */
    public function resetAction(PasswordResetService $service, NodeModel $nodeModel, $user, $time) {
        $user = $service->getUser($user, $time);
        if (!$user) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $form = $this->createFormBuilder();
        $form->setId('form-password-reset');
        $form->addRow('user', 'component', array(
            'component' => new PasswordResetComponent(),
            'embed' => true,
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $service->setUserPassword($user, $data['user']['password']);

                $this->addSuccess('success.user.password.reset');

                // redirect to the redirect node of the login widget with the root as fallback
                $url = null;

                $nodes = $nodeModel->getNodesForWidget('login');
                if ($nodes) {
                    $node = reset($nodes);
                    $widgetProperties = $node->getWidgetProperties($node->getWidgetId());

                    if ($widgetProperties->getWidgetProperty(LoginWidget::PROPERTY_REDIRECT) == LoginWidget::REDIRECT_NODE) {
                        $node = $nodeModel->getNode($widgetProperties->getWidgetProperty(LoginWidget::PROPERTY_NODE));
                        $url = $node->getUrl($this->locale, $this->request->getBaseScript());
                    }
                }

                if (!$url) {
                    $url = $this->request->getBaseUrl();
                }

                $this->response->setRedirect($url);

	            return;
        	} catch (ValidationException $exception) {
                $this->setValidationException($exception, $form, null);
        	}
        }

        $this->setTemplateView(self::TEMPLATE_NEW, array(
            'form' => $form->getView(),
        ));
    }

    /**
     * Gets a preview of the current properties
     * @return string
     */
    public function getPropertiesPreview() {
    	$subject = $this->getSubject();

    	if (!$subject) {
    		return '---';
    	}

    	$translator = $this->getTranslator();

    	return $translator->translate('label.subject') . ': ' . $subject;
    }

    /**
     * Action to edit the properties of this widget
     * @return null
     */
    public function propertiesAction() {
        $translator = $this->getTranslator();

        $data = array(
            'subject' => $this->getSubject(),
        );

        $form = $this->createFormBuilder($data);
        $form->addRow('subject', 'string', array(
            'label' => $translator->translate('label.subject'),
            'description' => $translator->translate('label.subject.password.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form = $form->build();
    	if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                return false;
            }

    		try {
	    		$form->validate();

                $data = $form->getData();

	    		$this->setSubject($data['subject']);

	    		$this->addInformation('success.preferences.saved');

    			return true;
    		} catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
    		}
    	}

        $this->setTemplateView('cms/widget/security/properties', array(
            'form' => $form->getView(),
        ));

    	return false;
    }

}
