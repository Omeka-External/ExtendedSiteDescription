<?php
namespace ExtendedSiteDescription;

use Omeka\Form\Element\Asset;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Form\Form;
use Zend\Mvc\Controller\AbstractController;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        $settings->delete('extended_site_description_categories');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addToSiteSettingsForm']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\SiteRepresentation',
            'rep.resource.json',
            [$this, 'addSettingsToApi']
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $categories = implode("\n", $settings->get('extended_site_description_categories', []));
        $form = new Form;
        $form->add([
            'type' => 'textarea',
            'name' => 'extended_site_description_categories',
            'options' => [
                'label' => 'Categories', // @translate
                'info' => 'Categories available to select, one per line', // @translate
            ],
            'attributes' => [
                'id' => 'extended_site_description_categories',
                'value' => $categories,
                'rows' => 10,
            ],
        ]);
        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $rawCategories = $controller->params()->fromPost('extended_site_description_categories', '');
        $categories = array_unique(array_filter(array_map('trim', explode("\n", $rawCategories)), 'strlen'));
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('extended_site_description_categories', $categories);
    }

    public function addToSiteSettingsForm(Event $event)
    {
        $form = $event->getTarget();
        $siteSettings = $form->getSiteSettings();
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $categories = $settings->get('extended_site_description_categories', []);
        $form->add([
            'type' => 'fieldset',
            'name' => 'extended_site_description',
            'options' => [
                'label' => 'Extended Site Description', // @translate
            ],
        ]);
        $fieldset = $form->get('extended_site_description');
        $fieldset->add([
            'type' => Asset::class,
            'name' => 'extended_site_description_image',
            'options' => [
                'label' => 'Image', // @translate
            ],
            'attributes' => [
                'id' => 'extended_site_description_image',
                'value' => $siteSettings->get('extended_site_description_image'),
            ],
        ]);
        $fieldset->add([
            'type' => 'checkbox',
            'name' => 'extended_site_description_linear',
            'options' => [
                'label' => 'Linear', // @translate
            ],
            'attributes' => [
                'id' => 'extended_site_description_linear',
                'value' => $siteSettings->get('extended_site_description_linear'),
            ],
        ]);
        $fieldset->add([
            'type' => 'select',
            'name' => 'extended_site_description_categories',
            'options' => [
                'label' => 'Categories', // @translate
                'value_options' => array_combine($categories, $categories),
            ],
            'attributes' => [
                'id' => 'extended_site_description_categories',
                'value' => $siteSettings->get('extended_site_description_categories'),
                'class' => 'chosen-select',
                'multiple' => true,
                'data-placeholder' => 'Select categories', // @translate
            ],
        ]);
    }

    public function addSettingsToApi(Event $event)
    {
        $site = $event->getTarget();
        $siteId = $site->id();

        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $siteSettings = $services->build('Omeka\Settings\Site');
        $siteSettings->setTargetId($siteId);

        $jsonLd = $event->getParam('jsonLd');
        $jsonLd['extended_site_description_linear'] = (bool) $siteSettings->get('extended_site_description_linear');
        $jsonLd['extended_site_description_categories'] = $siteSettings->get('extended_site_description_categories', []);

        $image = null;
        $imageId = $siteSettings->get('extended_site_description_image');
        if ($imageId) {
            try {
                $response = $api->read('assets', $imageId);
                $image = $response->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {}
        }
        $jsonLd['extended_site_description_image'] = $image;
        $event->setParam('jsonLd', $jsonLd);
    }
}

