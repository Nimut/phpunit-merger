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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder->files()
            ->in(realpath($input->getArgument('directory')));

        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;

        $root = $this->document->createElement('testsuites');
        $this->document->appendChild($root);

        foreach ($finder as $file) {
            try {
                $xml = new \SimpleXMLElement(file_get_contents($file->getRealPath()));
                $xmlArray = json_decode(json_encode($xml), true);
                if (!empty($xmlArray)) {
                    $this->addTestSuites($root, $xmlArray);
                }
            } catch (\Exception $exception) {
                // Initial fallthrough
            }
        }

        foreach ($this->domElements as $domElement) {
            if ($domElement->hasAttribute('parent')) {
                $domElement->removeAttribute('parent');
            }
        }

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
                    $children = isset($testSuite['testsuite']['@attributes']) ? [$testSuite['testsuite']] : $testSuite['testsuite'];
                    $this->addTestSuites($parent, $children);
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
                    $value = $key === 'name' || $key === 'file' ? $value : 0;
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
        $statusTags = [
            'error' => 'errors',
            'failure' => 'failures',
            'skipped' => 'skipped',
            'warning' => 'warnings',
        ];

        foreach ($testCases as $testCase) {
            $attributes = $testCase['@attributes'] ?? [];
            if (empty($testCase['@attributes']['name'])) {
                continue;
            }
            $name = $testCase['@attributes']['name'];
            $class = $testCase['@attributes']['class'];
            if (isset($this->domElements[$class . '::' . $name])) {
                $previusTestCase = $this->domElements[$class . '::' . $name];
                $previousTime = (float) ($previusTestCase->getAttribute('time') ?? 0);
                $newTime = (float) ($testCase['@attributes']['time'] ?? 0);
                $hasActualTestCaseAStatusTag = !empty(array_intersect(array_keys($testCase), array_keys($statusTags)));
                $hasPreviusTestCaseAStatusTag = $previusTestCase->childNodes->length > 0;

                if ($hasActualTestCaseAStatusTag ||
                    !$hasPreviusTestCaseAStatusTag &&
                    $newTime < $previousTime) {
                    continue;
                }

                $this->addAttributeValueToTestSuite($parent, 'tests', -1);
                foreach ($previusTestCase->childNodes as $child) {
                    $this->addAttributeValueToTestSuite($parent, $statusTags[$child->nodeName], -1);
                }
                foreach ($previusTestCase->attributes as $attribute) {
                    if (!is_numeric($attribute->nodeValue) || $attribute->nodeName === 'line') {
                        continue;
                    }
                    $this->addAttributeValueToTestSuite($parent, $attribute->nodeName, -$attribute->nodeValue);
                }

                $parent->removeChild($previusTestCase);
                unset($this->domElements[$class . '::' . $name]);
            }

            $element = $this->document->createElement('testcase');
            foreach ($attributes as $key => $value) {
                $element->setAttribute($key, (string)$value);
                if (!is_numeric($value) || $key === 'line') {
                    continue;
                }
                $this->addAttributeValueToTestSuite($parent, $key, $value);
            }

            $this->addAttributeValueToTestSuite($parent, 'tests', 1);
            foreach ($statusTags as $key => $value) {
                if (isset($testCase[$key])) {
                    $this->addAttributeValueToTestSuite($parent, $value, 1);
                    $element->appendChild($this->document->createElement($key));
                }
            }

            $parent->appendChild($element);
            $this->domElements[$class . '::' . $name] = $element;
        }
    }

    private function addAttributeValueToTestSuite(\DOMElement $element, $key, $value)
    {
        $currentValue = $element->hasAttribute($key) ? $element->getAttribute($key) : 0;
        $element->setAttribute($key, (string)($currentValue + $value));

        if ($element->hasAttribute('parent')) {
            $parent = $element->getAttribute('parent');
            if (isset($this->domElements[$parent])) {
                $this->addAttributeValueToTestSuite($this->domElements[$parent], $key, $value);
            }
        }
    }
}
