<?php
/**
 * @see       https://github.com/zendframework/zend-problem-details for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\ProblemDetails;

use Psr\Container\ContainerInterface;

class ProblemDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : ProblemDetailsMiddleware
    {
        return $container->has(ProblemDetailsResponseFactory::class)
            ? new ProblemDetailsMiddleware($container->get(ProblemDetailsResponseFactory::class))
            : new ProblemDetailsMiddleware();
    }
}