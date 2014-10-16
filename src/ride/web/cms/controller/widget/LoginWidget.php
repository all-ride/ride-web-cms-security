<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\node\NodeModel;
use ride\library\http\Response;
use ride\library\i18n\translator\Translator;
use ride\library\security\exception\AuthenticationException;
use ride\library\security\exception\EmailAuthenticationException;
use ride\library\security\exception\SecurityModelNotSetException;
use ride\library\security\SecurityManager;
use ride\library\validation\constraint\ConditionalConstraint;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\validator\RequiredValidator;
use ride\library\validation\ValidationError;

/**
 * Widget to show a login form which handles authentication
 */
class LoginWidget extends AbstractWidget {

    /**
     * Machine name of this widget
     * @var string
     */
    const NAME = 'login';

    /**
	 * Path to the icon of this widget
	 * @var string
	 */
	const ICON = 'img/cms/widget/login.png';

    /**
     * Path to the resource of the template
     * @var string
     */
    const TEMPLATE = 'cms/widget/security/login';

    /**
     * Name of the authenticated property
     * @var string
     */
    const PROPERTY_AUTHENTICATED = 'authenticated';

	/**
	 * Name of the redirect node property
	 * @var string
	 */
	const PROPERTY_NODE = 'node';

	/**
	 * Do nothing
	 * @var string
	 */
	const AUTHENTICATED_NOTHING = 'nothing';

	/**
	 * Redirect to a node
	 * @var string
	 */
	const AUTHENTICATED_NODE = 'node';

    /**
     * Redirect to the referer
     * @var string
     */
    const AUTHENTICATED_REFERER = 'referer';

	/**
	 * Render the widget
	 * @var string
	 */
	const AUTHENTICATED_RENDER = 'render';

    /**
     * Action to login a user with username and password authentication
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @param \ride\library\cms\node\NodeModel $nodeModel Instance of the node
     * model
     * @return null
     */
    public function indexAction(SecurityManager $securityManager, NodeModel $nodeModel) {
        $user = $securityManager->getUser();
        if ($user) {
            // user is already logged in
            $authenticated = $this->getAuthenticated();
            switch ($authenticated) {
                case self::AUTHENTICATED_REFERER:
                case self::AUTHENTICATED_NODE:
                    $redirectUrl = $this->getRedirectUrl($nodeModel, false);
                    if ($redirectUrl) {
                        $this->response->setRedirect($redirectUrl);
                    }

                    return;
                case self::AUTHENTICATED_NOTHING:
                    return;
            }
        }

        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->setId('form-login');
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'attributes' => array(
                'placeholder' => $translator->translate('label.username'),
            ),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow('password', 'password', array(
            'label' => $translator->translate('label.password'),
            'attributes' => array(
                'placeholder' => $translator->translate('label.password'),
            ),
            'validators' => array(
                'required' => array(),
            )
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $securityManager->login($data['username'], $data['password']);

                $this->response->setRedirect($this->getRedirectUrl($nodeModel));

                return;
            } catch (EmailAuthenticationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $username = $this->request->getBodyParameter('username');

                $url = $this->getUrl('profile.email') . '?username=' . urlencode($username) . '&referer=' . urlencode($this->request->getUrl());

                $this->addError('error.authentication.email', array('url' => $url));
            } catch (AuthenticationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $validationError = new ValidationError('error.authentication', 'Could not authenticate, check your credentials');

                $validationException = new ValidationException();
                $validationException->addErrors('username', array($validationError));

                $form->setValidationException($validationException);
            } catch (SecurityModelNotSetException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.security.model.not.set');
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $urls = $this->config->get('system.login.url', array());
        foreach ($urls as $index => $id) {
            $urls[$index] = $this->getUrl($id);
        }

        $this->setTemplateView(self::TEMPLATE, array(
            'form' => $form->getView(),
            'action' => $this->properties->getNode()->getUrl($this->locale, $this->request->getBaseScript()),
            'referer' => $this->getReferer(),
            'urls' => $urls,
        ));
    }

    /**
     * Gets a preview of the current properties
     * @return string
     */
    public function getPropertiesPreview() {
    	$translator = $this->getTranslator();

    	$preview = $translator->translate('label.login.authenticated');

    	$authenticated = $this->getAuthenticated();
        switch ($authenticated) {
            case self::AUTHENTICATED_REFERER:
                $preview .= $translator->translate('label.login.redirect.referer');

                break;
            case self::AUTHENTICATED_NODE:
                $preview .= $translator->translate('label.login.redirect.node');
                $preview .= ' (' . $this->getRedirectNode('---') . ')';

                break;
            case self::AUTHENTICATED_RENDER:
                $preview .= $translator->translate('label.login.render');

                break;
            case self::AUTHENTICATED_NOTHING:
                $preview .= $translator->translate('label.login.nothing');

                break;
            default:
                $preview .= '---';

                break;
        }

    	return $preview;
    }

    /**
     * Action to edit the properties of this widget
     * @param \ride\library\cms\node\NodeModel $nodeModel
     * @return null
     */
    public function propertiesAction(NodeModel $nodeModel) {
        $translator = $this->getTranslator();

        $validationConstraint = new ConditionalConstraint();
        $validationConstraint->addValueCondition(self::PROPERTY_AUTHENTICATED, self::AUTHENTICATED_NODE);
        $validationConstraint->addValidator(new RequiredValidator(), self::PROPERTY_NODE);

        $data = array(
            self::PROPERTY_AUTHENTICATED => $this->getAuthenticated(),
            self::PROPERTY_NODE => $this->getRedirectNode(),
        );

        $form = $this->createFormBuilder($data);
        $form->addRow(self::PROPERTY_AUTHENTICATED, 'option', array(
            'label' => $translator->translate('label.authenticated'),
            'options' => $this->getAuthenticatedOptions($translator),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow(self::PROPERTY_NODE, 'select', array(
            'label' => $translator->translate('label.node'),
            'options' => $this->getNodeList($nodeModel),
        ));
        $form->setValidationConstraint($validationConstraint);

        $form = $form->build();
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                return false;
            }

            try {
                $form->validate();

                $data = $form->getData();

                $this->properties->setWidgetProperty(self::PROPERTY_AUTHENTICATED, $data[self::PROPERTY_AUTHENTICATED]);
                $this->properties->setWidgetProperty(self::PROPERTY_NODE, $data[self::PROPERTY_NODE]);

                return true;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('cms/widget/security/login.properties', array(
            'form' => $form->getView(),
        ));

        return false;
    }

    /**
     * Gets the available options when a user is authenticated
     * @param \ride\library\i18n\translator\Translator $translator
     * @return array
     */
    protected function getAuthenticatedOptions(Translator $translator) {
        $options = array(
            self::AUTHENTICATED_REFERER => $translator->translate('label.login.redirect.referer'),
            self::AUTHENTICATED_NODE => $translator->translate('label.login.redirect.node'),
            self::AUTHENTICATED_RENDER => $translator->translate('label.login.render'),
            self::AUTHENTICATED_NOTHING => $translator->translate('label.login.nothing'),
        );

        return $options;
    }

    /**
     * Gets the redirect URL based on the widget properties
     * @param \ride\library\cms\node\NodeModel $nodeModel
     * @param boolean $force Force a result URL
     * @return string|null
     */
    protected function getRedirectUrl(NodeModel $nodeModel, $force = true) {
        $redirectUrl = null;

        $authenticated = $this->properties->getWidgetProperty(self::PROPERTY_AUTHENTICATED);
        switch ($authenticated) {
            case self::AUTHENTICATED_REFERER:
                $redirectUrl = $this->getReferer();

                break;
            case self::AUTHENTICATED_NODE:
                $nodeId = $this->getRedirectNode();
                if ($nodeId) {
                    $node = $nodeModel->getNode($nodeId);

                    $redirectUrl = $node->getUrl($this->locale, $this->request->getBaseScript());

                    break;
                }
            default:
                if ($force) {
                    $redirectUrl = $this->properties->getNode()->getUrl($this->locale, $this->request->getBaseScript());
                }

                break;
        }

        return $redirectUrl;
    }

    /**
     * Gets the value for the redirect property
     * @return string
     */
    protected function getAuthenticated() {
        return $this->properties->getWidgetProperty(self::PROPERTY_AUTHENTICATED, self::AUTHENTICATED_REFERER);
    }

    /**
     * Gets the value for the redirect property
     * @return string
     */
    protected function getRedirectNode($default = null) {
        return $this->properties->getWidgetProperty(self::PROPERTY_NODE, $default);
    }

    /**
     * Gets the referer of the current request
     * @return string
     */
    protected function getReferer() {
        $referer = $this->request->getQueryParameter('referer');
        if ($referer) {
            return $referer;
        }

        $referer = $this->request->getHeader('Referer');
        if ($referer) {
            return $referer;
        }

        $node = $this->properties->getNode()->getRootNode();
        $referer = $node->getUrl($this->locale, $this->request->getBaseScript());

        return $referer;
    }

}
