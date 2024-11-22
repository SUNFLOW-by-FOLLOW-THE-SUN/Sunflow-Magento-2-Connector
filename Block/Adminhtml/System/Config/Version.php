<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Block\Adminhtml\System\Config;

use FollowTheSun\Connector\Service\Module\GetVersionInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends Field
{
    public function __construct(
        Context $context,
        private GetVersionInterface $getModuleVersionService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:disable Squiz.NamingConventions.ValidFunctionName.PublicUnderscore
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<strong>' . $this->getModuleVersionService->resolve() . '</strong>';
    }
}
