<?php

namespace ride\web\cms\controller\widget;

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
     * Action to logout the current user and redirect to the home page
     * @return null
     */
    public function indexAction() {
        $securityManager = $this->getSecurityManager();
        $securityManager->logout();

        $node = $this->properties->getNode();
        $url = $node->getRootNode()->getUrl($this->locale, $this->request->getBaseScript());

        $this->response->setRedirect($url);
    }

}
