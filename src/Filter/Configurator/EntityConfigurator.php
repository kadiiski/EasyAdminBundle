<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return EntityFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyName = $filterDto->getProperty();
        if (!$entityDto->isAssociation($propertyName)) {
            return;
        }

        $doctrineMetadata = $entityDto->getPropertyMetadata($propertyName);
        // TODO: add the 'em' form type option too?
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.class', $doctrineMetadata['targetEntity']);
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.multiple', $entityDto->isToManyAssociation($propertyName));
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.attr.data-widget', 'select2');

        if ($entityDto->isToOneAssociation($propertyName)) {
            // don't show the 'empty value' placeholder when all join columns are required,
            // because an empty filter value would always returns no result
            $numberOfRequiredJoinColumns = \count(array_filter($doctrineMetadata['joinColumns'], function (array $joinColumnMapping): bool {
                return false === ($joinColumnMapping['nullable'] ?? false);
            }));

            $someJoinColumnsAreNullable = \count($doctrineMetadata['joinColumns']) !== $numberOfRequiredJoinColumns;

            if ($someJoinColumnsAreNullable) {
                $filterDto->setFormTypeOptionIfNotSet('value_type_options.placeholder', 'label.form.empty_value');
            }
        }
    }
}