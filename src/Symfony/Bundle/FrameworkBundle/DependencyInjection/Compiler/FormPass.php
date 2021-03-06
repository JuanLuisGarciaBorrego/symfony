<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds all services with the tags "form.type" and "form.type_guesser" as
 * arguments of the "form.extension" service.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('form.extension')) {
            return;
        }

        $definition = $container->getDefinition('form.extension');

        // Builds an array with service IDs as keys and tag aliases as values
        $types = array();

        foreach ($container->findTaggedServiceIds('form.type') as $serviceId => $tag) {
            // The following if-else block is deprecated and will be removed
            // in Symfony 3.0
            // Deprecation errors are triggered in the form registry
            if (isset($tag[0]['alias'])) {
                $types[$tag[0]['alias']] = $serviceId;
            } else {
                $types[$serviceId] = $serviceId;
            }

            // Support type access by FQCN
            $serviceDefinition = $container->getDefinition($serviceId);
            $types[$serviceDefinition->getClass()] = $serviceId;
        }

        $definition->replaceArgument(1, $types);

        $typeExtensions = array();

        foreach ($container->findTaggedServiceIds('form.type_extension') as $serviceId => $tag) {
            if (isset($tag[0]['extended_type'])) {
                $extendedType = $tag[0]['extended_type'];
            } elseif (isset($tag[0]['alias'])) {
                @trigger_error('The alias option of the form.type_extension tag is deprecated since version 2.8 and will be removed in 3.0. Use the extended_type option instead.', E_USER_DEPRECATED);
                $extendedType = $tag[0]['alias'];
            } else {
                @trigger_error('The extended_type option of the form.type_extension tag is required since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);
                $extendedType = $serviceId;
            }

            $typeExtensions[$extendedType][] = $serviceId;
        }

        $definition->replaceArgument(2, $typeExtensions);

        // Find all services annotated with "form.type_guesser"
        $guessers = array_keys($container->findTaggedServiceIds('form.type_guesser'));

        $definition->replaceArgument(3, $guessers);
    }
}
