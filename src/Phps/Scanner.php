<?php
/*
 * PHPS is a tool that generates an index of classes found in PHP source files
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Phps;

use Doctrine\DBAL\Connection;
use PhpParser;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scanner
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Scanner
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;
    
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $parser;
    protected $traverser;

    protected $resolve;
    protected $analyze;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    public function scan($dir)
    {
        $factory = new PhpParser\ParserFactory();

        $this->resolve = new Scanner\ResolveVisitor();
        $this->analyze = new Scanner\AnalyzeVisitor($this->connection, $this->resolve);

        $this->parser    = $factory->create(PhpParser\ParserFactory::PREFER_PHP7);
        $this->traverser = new PhpParser\NodeTraverser();
        $this->traverser->addVisitor($this->resolve);
        $this->traverser->addVisitor($this->analyze);

        $this->createDefinition($dir);
    }

    protected function createDefinition($srcPath)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                // ignore dirs
            } elseif ($path->getExtension() == 'php') {
                $file   = $path->getRealPath();
                $source = file_get_contents($file);

                $this->logger->info('Parse ' . $file);

                try {
                    $this->analyze->setFile($file);

                    $this->traverser->traverse($this->parser->parse($source));
                } catch (PhpParser\Error $e) {
                    throw $e;
                    $this->logger->error('Parse error in ' . $path->getRealPath());
                } catch (\Exception $e) {
                    throw $e;
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }
}
