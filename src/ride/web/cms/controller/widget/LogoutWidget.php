<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\node\NodeModel;

/**
 * Widget to logout the current user
 */
class LogoutWidget extends AbstractWidget {

	/**
	 * Name of the widget
	 * @var string
	 */
    const NAME = 'logout';

    /**
     * Path to the icon of the widget
     * @var string
     */
    const ICON = 'img/cms/widget/logout.png';

	/**
	 * Name of the redirect node property
	 * @var string
	 */
	const PROPERTY_NODE = 'node';

    /**
     * Action to logout the current user and redirect to the home page
     * @return null
     */
    public function indexAction(NodeModel $nodeModel) {
        $securityManager = $this->getSecurityManager();
        $securityManager->logout();

        $node = $this->properties->getNode();

        $redirectNodeId = $this->getRedirectNode();
        if ($redirectNodeId) {
            $redirectNode = $nodeModel->getNode($node->getRootNodeId(), $node->getRevision(), $redirectNodeId);

            $redirectUrl = $redirectNode->getUrl($this->locale, $this->request->getBaseScript());
        } else {
            $redirectUrl = $node->getRootNode()->getUrl($this->locale, $this->request->getBaseScript());
        }

        $this->response->setRedirect($redirectUrl);
    }

    /**
     * Gets a preview of the current properties
     * @return string
     */
    public function getPropertiesPreview() {
    	$translator = $this->getTranslator();
        $redirectNode = $this->getRedirectNode('---');

        $preview = '';
        if ($redirectNode) {
            $preview = $translator->translate('label.login.redirect.node') . ': ' . $this->getRedirectNode('---');
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

        $data = array(
            self::PROPERTY_NODE => $this->getRedirectNode(),
        );

        $form = $this->createFormBuilder($data);
        $form->addRow(self::PROPERTY_NODE, 'select', array(
            'label' => $translator->translate('label.login.redirect.node'),
            'options' => $this->getNodeList($nodeModel),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                return false;
            }

            try {
                $form->validate();

                $data = $form->getData();

                $this->properties->setWidgetProperty(self::PROPERTY_NODE, $data[self::PROPERTY_NODE]);

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

    /**
     * Gets the value for the redirect property
     * @return string
     */
    protected function getRedirectNode($default = null) {
        return $this->properties->getWidgetProperty(self::PROPERTY_NODE, $default);
    }

}
