<?php

namespace ride\web\cms\controller\widget;

use ride\library\security\exception\UnauthorizedException;
use ride\library\validation\exception\ValidationException;

use ride\web\base\form\ProfileComponent;

/**
 * Widget for handling your profile
 */
class ProfileWidget extends AbstractWidget {

    /**
     * Name of the widget
     * @var string
     */
    const NAME = 'profile';

    /**
     * Path to the icon of the widget
     * @var string
     */
    const ICON = 'img/cms/widget/profile.png';

    /**
     * Path to the resource of the template
     * @var string
     */
    const TEMPLATE = 'cms/widget/security/profile';

    /**
     * Action to show and process the user's profile page
     * @param \ride\web\base\form\ProfileComponent $profileComponent
     * @return null
     */
    public function indexAction(ProfileComponent $profileComponent) {
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedException();
        }

        $profileHooks = $profileComponent->getProfileHooks();

        $form = $this->buildForm($profileComponent);
        if ($form->isSubmitted()) {
            try {
                $profileComponent->processForm($form, $this);

                if (!$this->response->getView() && !$this->response->willRedirect()) {
                    $this->response->setRedirect($this->request->getUrl());
                }

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $arguments = array(
            'form' => $form->getView(),
            'activeHook' => $profileComponent->getActiveProfileHook(),
            'referer' => $this->request->getQueryParameter('referer'),
        );

        $view = $this->setTemplateView(self::TEMPLATE, $arguments, 'profile');
        $view->setProfileHooks($profileHooks);
        $view->addJavascript('js/jquery-ui.js');
        $view->addJavascript('js/form.js');

        $form->processView($view);
    }

}
