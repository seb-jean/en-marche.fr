<?php

namespace App\Admin;

use App\Entity\Adherent;
use App\Entity\Unregistration;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UnregistrationAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        parent::configureDefaultSortValues($sortValues);

        $sortValues[DatagridInterface::SORT_BY] = 'unregisteredAt';
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::PER_PAGE] = 64;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('uuid', null, [
                'label' => 'UUID',
            ])
            ->add('postalCode', null, [
                'label' => 'Code postal',
            ])
            ->add('reasons', null, [
                'label' => 'Raisons',
                'template' => 'admin/adherent/list_reasons.html.twig',
            ])
            ->add('comment', null, [
                'label' => 'Commentaire',
            ])
            ->add('adherent', null, [
                'label' => 'Type',
                'template' => 'admin/unregistration/user_type.html.twig',
            ])
            ->add('registeredAt', null, [
                'label' => 'Date d\'inscription',
            ])
            ->add('unregisteredAt', null, [
                'label' => 'Date de désinscription',
            ])
            ->add('excludedBy', null, [
                'label' => 'Exclu(e) par',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'virtual_field' => true,
                'actions' => [
                    'show' => [],
                ],
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $reasonsList = array_merge(Unregistration::REASONS_LIST_ADHERENT, Unregistration::REASONS_LIST_USER);

        $datagridMapper
            ->add('reasons', CallbackFilter::class, [
                'label' => 'Raisons',
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_combine($reasonsList, $reasonsList),
                    'choice_translation_domain' => 'forms',
                ],
                'callback' => function (ProxyQuery $qb, string $alias, string $field, FilterData $value) {
                    if (!$value->hasValue()) {
                        return false;
                    }

                    $qb->andWhere($qb->expr()->eq(sprintf('json_contains(%s.reasons, :reason)', $alias), 1));
                    $qb->setParameter(':reason', sprintf('"%s"', $value->getValue()));

                    return true;
                },
            ])
            ->add('uuid', CallbackFilter::class, [
                'label' => 'E-mail',
                'show_filter' => true,
                'field_type' => TextType::class,
                'callback' => function (ProxyQuery $qb, string $alias, string $field, FilterData $value) {
                    if (!$value->hasValue()) {
                        return false;
                    }

                    $uuid = Adherent::createUuid($value->getValue());
                    $qb->andWhere(sprintf('%s.uuid = :uuid', $alias));
                    $qb->setParameter('uuid', $uuid->toString());

                    return true;
                },
            ])
            ->add('unregisteredAt', DateRangeFilter::class, [
                'label' => 'Date de désinscription',
                'field_type' => DateRangePickerType::class,
            ])
            ->add('excludedBy', null, [
                'label' => 'Exclu(e) par',
                'show_filter' => true,
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('uuid', null, [
                'label' => 'UUID',
            ])
            ->add('postalCode', null, [
                'label' => 'Code postal',
            ])
            ->add('reasons', null, [
                'label' => 'Raisons',
                'template' => 'admin/adherent/show_reasons.html.twig',
            ])
            ->add('comment', null, [
                'label' => 'Commentaire',
            ])
            ->add('registeredAt', null, [
                'label' => 'Date d\'inscription',
            ])
            ->add('unregisteredAt', null, [
                'label' => 'Date de désinscription',
            ])
            ->add('excludedBy', null, [
                'label' => 'Exclu(e) par',
            ])
        ;
    }
}
