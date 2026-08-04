<?php declare(strict_types=1);

namespace EditingExtensions;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Form\Form;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceClassSelect;
use Omeka\Module\AbstractModule;
use EditingExtensions\Form\ConfigForm;

class Module extends AbstractModule
{
    public const SETTING_RECENTLY_EDITED_SORT = 'EditingExtensions_recently_edited_sort';
    public const SETTING_USED_TERMS_SEARCH = 'EditingExtensions_used_terms_search';

    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        // The browse service owns the event that builds sort selectors. Using
        // it keeps the item browse and advanced search selectors in sync.
        $browse = $this->getServiceLocator()->get('Omeka\Browse');
        $browse->getEventManager()->attach(
            'sort-config',
            [$this, 'addRecentlyEditedSort']
        );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        foreach ([PropertySelect::class, ResourceClassSelect::class] as $elementClass) {
            $sharedEventManager->attach(
                $elementClass,
                'form.vocab_member_select.query',
                [$this, 'restrictAdvancedSearchToUsedTerms']
            );
        }
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $settings = $services->get('Omeka\Settings');
        $settings->set(self::SETTING_RECENTLY_EDITED_SORT, true);
        $settings->set(self::SETTING_USED_TERMS_SEARCH, true);
    }

    public function upgrade(
        $oldVersion,
        $newVersion,
        ServiceLocatorInterface $services
    ): void {
        if (version_compare($oldVersion, '1.1.0', '<')) {
            $settings = $services->get('Omeka\Settings');
            if ($settings->get(self::SETTING_RECENTLY_EDITED_SORT, null) === null) {
                $settings->set(self::SETTING_RECENTLY_EDITED_SORT, true);
            }
            if ($settings->get(self::SETTING_USED_TERMS_SEARCH, null) === null) {
                $settings->set(self::SETTING_USED_TERMS_SEARCH, true);
            }
        }
    }

    public function uninstall(ServiceLocatorInterface $services): void
    {
        $settings = $services->get('Omeka\Settings');
        $settings->delete(self::SETTING_RECENTLY_EDITED_SORT);
        $settings->delete(self::SETTING_USED_TERMS_SEARCH);
    }

    public function getConfigForm(PhpRenderer $renderer): string
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        /** @var ConfigForm $form */
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->setData([
            self::SETTING_RECENTLY_EDITED_SORT => (bool) $settings->get(
                self::SETTING_RECENTLY_EDITED_SORT,
                true
            ),
            self::SETTING_USED_TERMS_SEARCH => (bool) $settings->get(
                self::SETTING_USED_TERMS_SEARCH,
                true
            ),
        ]);
        $form->prepare();

        return $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller): bool
    {
        $services = $this->getServiceLocator();

        /** @var Form $form */
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->setData($controller->getRequest()->getPost());
        if (!$form->isValid()) {
            $controller->messenger()->addFormErrors($form);
            return false;
        }

        $data = $form->getData();
        $settings = $services->get('Omeka\Settings');
        $settings->set(
            self::SETTING_RECENTLY_EDITED_SORT,
            (bool) $data[self::SETTING_RECENTLY_EDITED_SORT]
        );
        $settings->set(
            self::SETTING_USED_TERMS_SEARCH,
            (bool) $data[self::SETTING_USED_TERMS_SEARCH]
        );

        return true;
    }

    public function addRecentlyEditedSort(Event $event): void
    {
        if (!$this->featureIsEnabled(self::SETTING_RECENTLY_EDITED_SORT)
            || $event->getParam('context') !== 'admin'
            || $event->getParam('resourceType') !== 'items'
        ) {
            return;
        }

        $sortConfig = $event->getParam('sortConfig', []);
        $sortConfig['modified'] = 'Recently edited'; // @translate
        $event->setParam('sortConfig', $sortConfig);
    }

    public function restrictAdvancedSearchToUsedTerms(Event $event): void
    {
        if (!$this->featureIsEnabled(self::SETTING_USED_TERMS_SEARCH)
            || !$this->isAdminItemAdvancedSearchRequest()
        ) {
            return;
        }

        $query = $event->getParam('query', []);
        $query['used'] = true;
        $event->setParam('query', $query);
    }

    private function featureIsEnabled(string $setting): bool
    {
        return (bool) $this->getServiceLocator()
            ->get('Omeka\Settings')
            ->get($setting, true);
    }

    private function isAdminItemAdvancedSearchRequest(): bool
    {
        $routeMatch = $this->getServiceLocator()
            ->get('Omeka\Status')
            ->getRouteMatch();
        if (!$routeMatch
            || !$routeMatch->getParam('__ADMIN__')
            || $routeMatch->getParam('action') !== 'search'
        ) {
            return false;
        }

        $controller = $routeMatch->getParam('controller')
            ?: $routeMatch->getParam('__CONTROLLER__');

        return in_array($controller, [
            'item',
            'Omeka\Controller\Admin\Item',
            'Omeka\Controller\Admin\ItemController',
        ], true);
    }
}
