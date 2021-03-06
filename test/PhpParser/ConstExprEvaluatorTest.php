<?php declare(strict_types=1);

namespace PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PHPUnit\Framework\TestCase;

class ConstExprEvaluatorTest extends TestCase
{
    /** @dataProvider provideTestEvaluate */
    public function testEvaluate($exprString, $expected) {
        $parser = new Parser\Php7(new Lexer());
        $expr = $parser->parse('<?php ' . $exprString . ';')[0]->expr;
        $evaluator = new ConstExprEvaluator();
        $this->assertSame($expected, $evaluator->evaluate($expr));
    }

    public function provideTestEvaluate() {
        return [
            ['1', 1],
            ['1.0', 1.0],
            ['"foo"', "foo"],
            ['[0, 1]', [0, 1]],
            ['["foo" => "bar"]', ["foo" => "bar"]],
            ['NULL', null],
            ['False', false],
            ['true', true],
            ['+1', 1],
            ['-1', -1],
            ['~0', -1],
            ['!true', false],
            ['[0][0]', 0],
            ['"a"[0]', "a"],
            ['true ? 1 : (1/0)', 1],
            ['false ? (1/0) : 1', 1],
            ['42 ?: (1/0)', 42],
            ['false ?: 42', 42],
            ['false ?? 42', false],
            ['null ?? 42', 42],
            ['[0][0] ?? 42', 0],
            ['[][0] ?? 42', 42],
            ['0b11 & 0b10', 0b10],
            ['0b11 | 0b10', 0b11],
            ['0b11 ^ 0b10', 0b01],
            ['1 << 2', 4],
            ['4 >> 2', 1],
            ['"a" . "b"', "ab"],
            ['4 + 2', 6],
            ['4 - 2', 2],
            ['4 * 2', 8],
            ['4 / 2', 2],
            ['4 % 2', 0],
            ['4 ** 2', 16],
            ['1 == 1.0', true],
            ['1 != 1.0', false],
            ['1 < 2.0', true],
            ['1 <= 2.0', true],
            ['1 > 2.0', false],
            ['1 >= 2.0', false],
            ['1 <=> 2.0', -1],
            ['1 === 1.0', false],
            ['1 !== 1.0', true],
            ['true && true', true],
            ['true and true', true],
            ['false && (1/0)', false],
            ['false and (1/0)', false],
            ['false || false', false],
            ['false or false', false],
            ['true || (1/0)', true],
            ['true or (1/0)', true],
            ['true xor false', true],
        ];
    }

    /**
     * @expectedException \PhpParser\ConstExprEvaluationException
     * @expectedExceptionMessage Expression of type Expr_Variable cannot be evaluated
     */
    public function testEvaluateFails() {
        $evaluator = new ConstExprEvaluator();
        $evaluator->evaluate(new Expr\Variable('a'));
    }

    public function testEvaluateFallback() {
        $evaluator = new ConstExprEvaluator(function(Expr $expr) {
            if ($expr instanceof Scalar\MagicConst\Line) {
                return 42;
            }
            throw new ConstExprEvaluationException();
        });
        $expr = new Expr\BinaryOp\Plus(
            new Scalar\LNumber(8),
            new Scalar\MagicConst\Line()
        );
        $this->assertSame(50, $evaluator->evaluate($expr));
    }
}
