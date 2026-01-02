<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

final class DummyStringFunction extends FunctionNode
{

	public function getSql(SqlWalker $sqlWalker): string
	{
		return 'DUMMY()';
	}

	public function parse(Parser $parser): void
	{
		// No-op for testing
	}

}
