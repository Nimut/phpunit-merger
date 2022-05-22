<?php

declare(strict_types=1);

namespace Nimut\PhpunitMerger\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class LogCommand extends Command
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \DOMElement[]
     */
    private $domElements = [];

    private $keysToCalculate = ['assertions', 'time', 'tests', 'errors', 'failures', 'skipped'];

    protected function configure()
    {
        $this->setName('log')
            ->setDescription('Merges multiple PHPUnit JUnit xml files into one')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'The directory containing PHPUnit JUnit xml files'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The file where to write the merged result'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()
            ->in(realpath($input->getArgument('directory')))->sortByName(true);

        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;

        $root = $this->document->createElement('testsuites');
        $baseSuite = $this->document->createElement('testsuite');
        $baseSuite->setAttribute('name', 'All Suites');
        $baseSuite->setAttribute('tests', '0');
        $baseSuite->setAttribute('assertions', '0');
        $baseSuite->setAttribute('errors', '0');
        $baseSuite->setAttribute('failures', '0');
        $baseSuite->setAttribute('skipped', '0');
        $baseSuite->setAttribute('time', '0');

        $this->domElements['All Suites'] = $baseSuite;

        $root->appendChild($baseSuite);
        $this->document->appendChild($root);

        foreach ($finder as $file) {
            try {
                $xml = new \SimpleXMLElement(file_get_contents($file->getRealPath()));
                $xmlArray = json_decode(json_encode($xml), true);
                if (!empty($xmlArray)) {
                    $this->addTestSuites($baseSuite, $xmlArray);
                }
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>Error in file %s: %s</error>', $file->getRealPath(), $exception->getMessage()));
            }
        }

        foreach ($this->domElements as $domElement) {
            if ($domElement->hasAttribute('parent')) {
                $domElement->removeAttribute('parent');
            }
        }
        $this->calculateTopLevelStats();
        $file = $input->getArgument('file');
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0777, true);
        }
        $this->document->save($input->getArgument('file'));

        return 0;
    }

    private function addTestSuites(\DOMElement $parent, array $testSuites)
    {
        foreach ($testSuites as $testSuite) {
            if (empty($testSuite['@attributes']['name'])) {
                if (!empty($testSuite['testsuite'])) {
                    $this->addTestSuites($parent, $testSuite);
                }
                continue;
            }
            $name = $testSuite['@attributes']['name'];

            if (isset($this->domElements[$name])) {
                $element = $this->domElements[$name];
            } else {
                $element = $this->document->createElement('testsuite');
                $element->setAttribute('parent', $parent->getAttribute('name'));
                $attributes = $testSuite['@attributes'] ?? [];
                foreach ($attributes as $key => $value) {
                    $element->setAttribute($key, (string)$value);
                }
                $parent->appendChild($element);
                $this->domElements[$name] = $element;
            }

            if (!empty($testSuite['testsuite'])) {
                $children = isset($testSuite['testsuite']['@attributes']) ? [$testSuite['testsuite']] : $testSuite['testsuite'];
                $this->addTestSuites($element, $children);
            }

            if (!empty($testSuite['testcase'])) {
                $children = isset($testSuite['testcase']['@attributes']) ? [$testSuite['testcase']] : $testSuite['testcase'];
                $this->addTestCases($element, $children);
            }
        }
    }

    private function addTestCases(\DOMElement $parent, array $testCases)
    {
        foreach ($testCases as $testCase) {
            $attributes = $testCase['@attributes'] ?? [];
            if (empty($testCase['@attributes']['name'])) {
                continue;
            }
            $name = $testCase['@attributes']['name'];

            if (isset($this->domElements[$name])) {
                continue;
            }
            $element = $this->document->createElement('testcase');
            foreach ($attributes as $key => $value) {
                $element->setAttribute($key, (string)$value);
            }
            if (isset($testCase['failure']) || isset($testCase['warning']) || isset($testCase['error'])) {
                $this->addChildElements($testCase, $element);
            }
            $parent->appendChild($element);
            $this->domElements[$name] = $element;
        }
    }

    private function addChildElements(array $tree, \DOMElement $element)
    {
        foreach ($tree as $key => $value) {
            if ($key == '@attributes') {
                continue;
            }
            $child = $this->document->createElement($key);
            $child->nodeValue = $value;
            $element->appendChild($child);
        }
    }

    private function calculateTopLevelStats()
    {
        /** @var \DOMElement $topNode */
        $suites = $this->document->getElementsByTagName('testsuites')->item(0);
        $topNode = $suites->firstChild;
        if ($topNode->hasChildNodes()) {
            $stats = array_flip($this->keysToCalculate);
            $stats = array_map(function ($_value) {
                return 0;
            }, $stats);
            foreach ($topNode->childNodes as $child) {
                $attributes = $child->attributes;
                foreach ($attributes as $key => $value) {
                    if (in_array($key, $this->keysToCalculate)) {
                        $stats[$key] += $value->nodeValue;
                    }
                }
            }
            foreach ($stats as $key => $value) {
                $topNode->setAttribute($key, (string)$value);
            }
        }
    }
}
