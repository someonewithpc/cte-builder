<?php declare(strict_types=1);

namespace Somnambulist\CTEBuilder\Behaviours;

use BadMethodCallException;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use function implode;
use function in_array;
use function sprintf;

/**
 * Trait CanPassThroughToQuery
 *
 * Allow passing through certain methods to an underlying QueryBuilder instance.
 *
 * @package    Somnambulist\CTEBuilder\Behaviours
 * @subpackage Somnambulist\CTEBuilder\Behaviours\CanPassThroughToQuery
 *
 * @property QueryBuilder $query
 */
trait CanPassThroughToQuery
{

    public function __call($name, $arguments)
    {
        $allowed = [
            'addGroupBy', 'addOrderBy', 'addSelect', 'andHaving', 'andWhere',
            'createNamedParameter', 'createPositionalParameter', 'expr', 'from', 'groupBy', 'having',
            'innerJoin', 'join', 'leftJoin', 'orderBy', 'orHaving', 'orWhere', 'rightJoin', 'select',
            'setFirstResult', 'setMaxResults', 'setParameter', 'setParameters', 'where',
        ];

        if (in_array($name, $allowed)) {
            if (($ret = $this->query->{$name}(...$arguments)) instanceof ExpressionBuilder) {
                return $ret;
            }

            return $this;
        }

        throw new BadMethodCallException(sprintf(
            'Method "%s" is not supported for pass through on "%s"; expected one of (%s)',
            $name, static::class, implode(', ', $allowed)
        ));
    }
}
