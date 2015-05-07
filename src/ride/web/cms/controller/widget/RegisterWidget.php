<?php

namespace ride\web\cms\controller\widget;

class RegisterWidget extends AbstractWidget implements StyleWidget {

    const NAME = 'register';

    const TEMPLATE = 'cms/widget/security/register';

    public function indexAction() {
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->setAction('register');
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('email', 'email', array(
            'label' => $translator->translate('label.email'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('password', 'password', array(
            'label' => $translator->translate('label.password'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('password2', 'password', array(
            'label' => $translator->translate('label.password.confirm'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {

        }

        $this->setTemplateView(self::TEMPLATE, array(
            'form' => $form->getView(),
        ));
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
        );

        $form = $this->createFormBuilder($data);
        $form->addRow('subject', 'string', array(
            'label' => $translator->translate('label.subject'),
            'description' => $translator->translate('label.subject.register.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('message', 'text', array(
            'label' => $translator->translate('label.message'),
            'description' => $translator->translate('label.message.register.description'),
            'attributes' => array(
                'rows' => 15,
            ),
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
                $this->setMessage($data['message']);

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

    /**
     * Gets the options for the styles
     * @return array Array with the name of the option as key and the
     * translation key as value
     */
    public function getWidgetStyleOptions() {
        return array(
            'container' => 'label.style.container',
        );
    }

}
