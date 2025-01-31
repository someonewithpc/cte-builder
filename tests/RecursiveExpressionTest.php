<?php declare(strict_types=1);

namespace Somnambulist\Components\CTEBuilder\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\CTEBuilder\RecursiveExpression;

/**
 * Class RecursiveExpressionTest
 *
 * @package    Somnambulist\Components\CTEBuilder\Tests
 * @subpackage Somnambulist\Components\CTEBuilder\Tests\RecursiveExpressionTest
 */
class RecursiveExpressionTest extends TestCase
{

    public function testCanSpecifyFields()
    {
        $cte = new RecursiveExpression('recursive', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withFields('id', 'name', 'category');

        $this->assertEquals(['id', 'name', 'category'], $cte->getFields());
    }

    public function testCanChangeUnionType()
    {
        $cte = new RecursiveExpression('recursive', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withUniqueRows(true);

        $this->assertTrue($cte->fetchUniqueRows());
    }

    public function testCanSetInitialSelectAsString()
    {
        $cte = new RecursiveExpression('recursive', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withInitialSelect('VALUES(1)');

        $this->assertEquals('VALUES(1)', $cte->getInitialSelect());
    }

    public function testCanSetInitialSelectAsQueryBuilder()
    {
        $cte = new RecursiveExpression('recursive', $q = new QueryBuilder($this->createMock(Connection::class)));

        $init = new QueryBuilder($this->createMock(Connection::class));
        $init->select('id', 'name', 'category')->from('users')->where('group_id = :group_id')->setParameter('group_id', 4);

        $cte->withInitialSelect($init);

        $this->assertEquals('SELECT id, name, category FROM users WHERE group_id = :group_id', $cte->getInitialSelect());
        $this->assertCount(1, $cte->getParameters());
    }

    public function testCanInlineCTEExpression()
    {
        $cte = new RecursiveExpression('cnt', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withFields('x')->withInitialSelect('VALUES(1)')->query()->select('x+1')->from('cnt')->where('x<1000000');

        $expected = 'cnt (x) AS (VALUES(1) UNION ALL SELECT x+1 FROM cnt WHERE x<1000000)';

        $this->assertEquals($expected, $cte->getInlineSQL());
    }

    public function testCanInlineCTEExpression2()
    {
        $cte = new RecursiveExpression('cnt', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withInitialSelect('VALUES(1)')->query()->select('x+1')->from('cnt')->where('x<1000000');

        $expected = 'cnt AS (VALUES(1) UNION ALL SELECT x+1 FROM cnt WHERE x<1000000)';

        $this->assertEquals($expected, $cte->getInlineSQL());
    }

    public function testCanInlineCTEExpression3()
    {
        $cte = new RecursiveExpression('cnt', $q = new QueryBuilder($this->createMock(Connection::class)));
        $cte->withUniqueRows(true)->withInitialSelect('VALUES(1)')->query()->select('x+1')->from('cnt')->where('x<1000000');

        $expected = 'cnt AS (VALUES(1) UNION SELECT x+1 FROM cnt WHERE x<1000000)';

        $this->assertEquals($expected, $cte->getInlineSQL());
    }
}
