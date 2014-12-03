<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\node\NodeModel;
use ride\library\http\Response;
use ride\library\router\Route;
use ride\library\validation\exception\ValidationException;

use ride\web\base\form\PasswordResetComponent;
use ride\web\base\service\security\PasswordResetService;
use ride\web\cms\service\security\mail\TextMailRenderer;

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
     * Namespace for the templates of this widget
     * @var string
     */
    const TEMPLATE_NAMESPACE = 'cms/widget/security';

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
     * Action to request a password reset
     * @param \ride\web\base\service\PasswordResetService $service
     * @return null
     */
    public function indexAction(PasswordResetService $service) {
        $form = $this->createFormBuilder();
        $form->setId('form-password-request');
        $form->addRow('email', 'email', array(
            'label' => $this->getTranslator()->translate('label.email'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $user = $service->lookupUser($data['email']);
                if ($user) {
                    $message = $this->getMessage();
                    if ($message) {
                        $mailRenderer = new TextMailRenderer();
                        $mailRenderer->setText(nl2br($message));
                        $mailRenderer->setUrl($this->getUrl('password.reset', array(
                            'user' => '%user%',
                            'time' => '%time%',
                        )));

                        $service->setMailRenderer($mailRenderer);
                    }

                    $service->requestPasswordReset($user, $this->getSubject());

                    $this->addSuccess('success.user.password.mail');
                }

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

        $view = $this->setTemplateView($this->getTemplate(static::TEMPLATE_NAMESPACE . '/password.request', 'request'), array(
            'form' => $form->getView(),
            'referer' => $this->request->getQueryParameter('referer'),
        ));

        $form->processView($view);
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

                $nodes = $nodeModel->getNodesForWidget('login', $this->properties->getNode()->getRootNodeId());
                if ($nodes) {
                    $node = reset($nodes);
                    $widgetProperties = $node->getWidgetProperties($node->getWidgetId());

                    if ($widgetProperties->getWidgetProperty(LoginWidget::PROPERTY_AUTHENTICATED) == LoginWidget::REDIRECT_NODE) {
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

        $view = $this->setTemplateView($this->getTemplate(static::TEMPLATE_NAMESPACE . '/password.reset', 'reset'), array(
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
            'message' => $this->getMessage(),
            'template_index' => $this->getTemplate(static::TEMPLATE_NAMESPACE . '/password.request', 'request'),
            'template_reset' => $this->getTemplate(static::TEMPLATE_NAMESPACE . '/password.reset', 'reset'),
        );

        $form = $this->createFormBuilder($data);
        $form->addRow('subject', 'string', array(
            'label' => $translator->translate('label.subject'),
            'description' => $translator->translate('label.subject.security.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('message', 'text', array(
            'label' => $translator->translate('label.message'),
            'description' => $translator->translate('label.message.security.description'),
            'attributes' => array(
                'rows' => 10,
            ),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->addRow(self::PROPERTY_TEMPLATE . '_request', 'select', array(
            'label' => $translator->translate('label.template.password.request'),
            'description' => $translator->translate('label.template.widget.description'),
            'options' => $this->getAvailableTemplates(static::TEMPLATE_NAMESPACE, self::NAME, 'request'),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow(self::PROPERTY_TEMPLATE . '_reset', 'select', array(
            'label' => $translator->translate('label.template.password.reset'),
            'description' => $translator->translate('label.template.widget.description'),
            'options' => $this->getAvailableTemplates(static::TEMPLATE_NAMESPACE, self::NAME, 'reset'),
            'validators' => array(
                'required' => array(),
            )
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
	    		$this->setMessage($data['message']);
	    		$this->setTemplate($data[self::PROPERTY_TEMPLATE . '_request'], 'request');
	    		$this->setTemplate($data[self::PROPERTY_TEMPLATE . '_reset'], 'reset');

	    		$this->addInformation('success.preferences.saved');

    			return true;
    		} catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
    		}
    	}

        $this->setTemplateView('cms/widget/security/properties.password', array(
            'form' => $form->getView(),
        ));

    	return false;
    }

}
