<?php declare(strict_types=1);

namespace EditingExtensions\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Form;
use EditingExtensions\Module;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            ->add([
                'name' => Module::SETTING_RECENTLY_EDITED_SORT,
                'type' => Checkbox::class,
                'options' => [
                    'label' => 'Enable recently edited sorting', // @translate
                    'info' => 'Add “Recently edited” to the item browse and advanced search sort selectors.', // @translate
                    'use_hidden_element' => true,
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
                'attributes' => [
                    'id' => Module::SETTING_RECENTLY_EDITED_SORT,
                ],
            ])
            ->add([
                'name' => Module::SETTING_USED_TERMS_SEARCH,
                'type' => Checkbox::class,
                'options' => [
                    'label' => 'Limit advanced search to used terms', // @translate
                    'info' => 'Show only properties and resource classes used by existing resources on the admin item advanced search page.', // @translate
                    'use_hidden_element' => true,
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
                'attributes' => [
                    'id' => Module::SETTING_USED_TERMS_SEARCH,
                ],
            ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => Module::SETTING_RECENTLY_EDITED_SORT,
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => Module::SETTING_USED_TERMS_SEARCH,
            'required' => false,
        ]);
    }
}
