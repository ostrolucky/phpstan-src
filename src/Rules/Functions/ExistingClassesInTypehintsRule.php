<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Node;
use PHPStan\Rules\FunctionDefinitionCheck;

class ExistingClassesInTypehintsRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Rules\FunctionDefinitionCheck */
	private $check;

	public function __construct(FunctionDefinitionCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return Function_::class;
	}

	/**
	 * @param \PHPStan\Analyser\Node $node
	 * @return string[]
	 */
	public function processNode(Node $node): array
	{
		return $this->check->checkFunction(
			$node->getParserNode(),
			sprintf(
				'Parameter $%%s of function %s() has invalid typehint type %%s.',
				$node->getScope()->getFunction()
			),
			sprintf(
				'Return typehint of function %s() has invalid type %%s.',
				$node->getScope()->getFunction()
			)
		);
	}

}
