<?php

namespace AppBundle\DoctrineExtension\ORM\Query\AST\Functions\Mysql;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "JSON_MERGE" "(" StringPrimary "," StringPrimary { "," StringPrimary }* ")".
 */
class JsonMerge extends FunctionNode
{
    const FUNCTION_NAME = 'JSON_MERGE';

    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    protected $firstJsonDocExpr;

    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    protected $secondJsonDocExpr;

    /**
     * @var \Doctrine\ORM\Query\AST\Node[]
     */
    protected $jsonDocs = [];

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     *
     * @throws DBALException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $jsonDocs = [];
        foreach ($this->jsonDocs as $doc) {
            $jsonDocs[] = $sqlWalker->walkStringPrimary($doc);
        }

        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return sprintf('%s(%s)', static::FUNCTION_NAME, implode(', ', $jsonDocs));
        }

        throw DBALException::notSupported(static::FUNCTION_NAME);
    }

    /**
     * @param Parser $parser
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstJsonDocExpr = $parser->StringPrimary();
        $this->jsonDocs[] = $this->firstJsonDocExpr;

        $parser->match(Lexer::T_COMMA);

        $this->secondJsonDocExpr = $parser->StringPrimary();
        $this->jsonDocs[] = $this->secondJsonDocExpr;

        while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->jsonDocs[] = $parser->StringPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
